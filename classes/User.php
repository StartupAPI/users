<?php

require_once(dirname(__DIR__) . '/global.php');
require_once(__DIR__ . '/Account.php');
require_once(__DIR__ . '/Badge.php');
require_once(__DIR__ . '/CookieStorage.php');
require_once(__DIR__ . '/CampaignTracker.php');
require_once(__DIR__ . '/Invitation.php');
require_once(dirname(__DIR__) . '/swiftmailer/lib/swift_required.php');

/**
 * This class represents a registerd user in the system
 *
 * Usage:
 * <code>
 * // Getting currently logged in user
 * // Returns User object, same as User::require_login()
 * $user = StartupAPI::requireLogin();
 * echo 'Welcome, ' . $user->getName() . '!';
 * </code>
 *
 * Note that unless you are absolutely sure that data in your application is only
 * specific to one individual, you might want to use Accounts to connect your data
 * instead of Users - this way you will be able to add multi-user accounts in the
 * future when you're ready.
 *
 * <code>
 * // Getting currently selected account
 * $account = $user->getCurrentAccount();
 * </code>
 *
 * Each user gets a personal account created for them out of the box to make it
 * easier for you to transition to accounts in the future.
 *
 * @see Account
 *
 * @package StartupAPI
 */
class User {

	/**
	 * Checks if user is logged in and returns use object or redirects to login page
	 *
	 * This is the easiest way to protect a page from public viewing
	 *
	 * Usage:
	 * <code>
	 * // Getting currently logged in user
	 * $user = StartupAPI::requireLogin();
	 * </code>
	 *
	 * Although preferred method is to call a method on StartupAPI object
	 * <code>
	 * $user = StartupAPI::requireLogin();
	 * </code>
	 *
	 * @param boolean $allow_impersonation Set to false if you do not want to allow impersonation
	 *
	 * @return User Current user
	 */
	public static function require_login($allow_impersonation = true) {
		$user = self::get($allow_impersonation);

		if (!is_null($user)) {
			return $user;
		} else {
			self::redirectToLogin();
		}
	}

	/**
	 * Checks if user is logged in and returns use object or null if user is not logged in
	 * Disabled users are not allowed to login unless they are being impersonated.
	 *
	 * Usage:
	 * <code>
	 * // Getting currently logged in user
	 * $user = User::get();
	 *
	 * if (!is_null($user)) {
	 * 		echo 'Welcome, ' . $user->getName() . '!';
	 * }
	 * </code>
	 *
	 * Although preferred method is to call a method on StartupAPI object
	 * <code>
	 * $user = StartupAPI::getUser();
	 * </code>
	 *
	 * @param boolean $allow_impersonation Set to false if you do not want to allow impersonation
	 *
	 * @return User|null Current user or null if user is not logged in
	 */
	public static function get($allow_impersonation = true) {
		$storage = new MrClay_CookieStorage(array(
					'secret' => UserConfig::$SESSION_SECRET,
					'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
					'path' => UserConfig::$SITEROOTURL,
					'httponly' => true
				));

		$userid = $storage->fetch(UserConfig::$session_userid_key);

		if (is_numeric($userid)) {
			$user = self::getUser($userid);

			if (is_null($user)) {
				return null;
			}

			// if email verification is required, force users to verify it
			if (!$user->is_email_verified && UserConfig::$requireVerifiedEmail && !UserConfig::$IGNORE_REQUIRED_EMAIL_VERIFICATION) {
				self::redirectToEmailVerification();
			}

			// only forsing password reset on non-impersonated users
			if ($user->requiresPasswordReset() &&
					!UsernamePasswordAuthenticationModule::$IGNORE_PASSWORD_RESET) {
				self::redirectToPasswordReset();
			}

			// don't even try impersonating if not admin
			if (!$allow_impersonation || !$user->isAdmin()) {
				if ($user->isDisabled()) {
					return null;
				}

				return $user;
			}

			// now, let's check impersonation
			$impersonated_userid = $storage->fetch(UserConfig::$impersonation_userid_key);
			$impersonated_user = self::getUser($impersonated_userid);

			// do not impersonate unknown user or the same user
			if (is_null($impersonated_user) || $user->isTheSameAs($impersonated_user)) {
				if ($user->isDisabled()) {
					return null;
				}

				return $user;
			}

			$impersonated_user->impersonator = $user;

			return $impersonated_user;
		} else {
			return null;
		}
	}

	/**
	 * Updates user activity when user returns to the site more then a day after last access
	 *
	 * @throws StartupAPIException
	 *
	 * @internal
	 */
	public static function updateReturnActivity() {
		$storage = new MrClay_CookieStorage(array(
					'secret' => UserConfig::$SESSION_SECRET,
					'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
					'path' => UserConfig::$SITEROOTURL,
					'httponly' => true
				));

		$last = $storage->fetch(UserConfig::$last_login_key);
		if (!$storage->store(UserConfig::$last_login_key, time())) {
			throw new StartupAPIException(implode('; ', $storage->errors));
		}

		$user = self::get();

		if (!is_null($user) && $last > 0
				&& $last < time() - UserConfig::$last_login_session_length * 60) {
			if ($last > time() - 86400) {
				$user->recordActivity(USERBASE_ACTIVITY_RETURN_DAILY);
			} else if ($last > time() - 7 * 86400) {
				$user->recordActivity(USERBASE_ACTIVITY_RETURN_WEEKLY);
			} else if ($last > time() - 30 * 86400) {
				$user->recordActivity(USERBASE_ACTIVITY_RETURN_MONTHLY);
			}
		}
	}

	/**
	 * Sets user's referrer based on CampaignTracker's information
	 *
	 * @throws DBException
	 *
	 * @internal Should not be used other then by login methods
	 */
	private function setReferer() {
		$referer = CampaignTracker::getReferer();
		if (is_null($referer)) {
			return;
		}

		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('UPDATE u_users SET referer = ? WHERE id = ?')) {
			if (!$stmt->bind_param('si', $referer, $this->userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}
	}

	/**
	 * Return the URL this user came from when registered on the site.
	 *
	 * @return string Referer URL
	 *
	 * @throws DBException
	 */
	public function getReferer() {
		$db = UserConfig::getDB();

		$referer = null;

		if ($stmt = $db->prepare('SELECT referer FROM u_users WHERE id = ?')) {
			if (!$stmt->bind_param('i', $this->userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->bind_result($referer)) {
				throw new DBBindResultException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $referer;
	}

	/**
	 * Returns a list of users by referrer
	 *
	 * @param int $days Number of days to look back for
	 *
	 * @return array Array with URLs as keys and values are arrays of users
	 *
	 * @throws DBException
	 */
	public static function getReferers($days = 30) {
		$db = UserConfig::getDB();

		$sources = array();

		if ($stmt = $db->prepare('SELECT referer, id, status, name, username, email, requirespassreset, fb_id, fb_link, UNIX_TIMESTAMP(regtime), points, email_verified FROM u_users WHERE referer IS NOT NULL AND regtime > DATE_SUB(NOW(), INTERVAL ? DAY) ORDER BY regtime DESC')) {
			if (!$stmt->bind_param('i', $days)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($referer, $userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$sources[$referer][] = new self($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $sources;
	}

	/**
	 * Sets user's campaign they came from
	 *
	 * @throws DBException
	 *
	 * @internal Used by login/registration scripts to record user's campaign
	 */
	private function setRegCampaign() {
		$campaign = CampaignTracker::getCampaign();
		if (is_null($campaign) || !$campaign) {
			return;
		}

		$db = UserConfig::getDB();

		$cmp_source_id = null;
		if (array_key_exists('cmp_source', $campaign)) {
			$cmp_source_id = CampaignTracker::getCampaignSourceID($campaign['cmp_source']);
		}

		$cmp_medium_id = null;
		if (array_key_exists('cmp_medium', $campaign)) {
			$cmp_medium_id = CampaignTracker::getCampaignMediumID($campaign['cmp_medium']);
		}

		$cmp_keywords_id = null;
		if (array_key_exists('cmp_keywords', $campaign)) {
			$cmp_keywords_id = CampaignTracker::getCampaignKeywordsID($campaign['cmp_keywords']);
		}

		$cmp_content_id = null;
		if (array_key_exists('cmp_content', $campaign)) {
			$cmp_content_id = CampaignTracker::getCampaignContentID($campaign['cmp_content']);
			;
		}

		$cmp_name_id = null;
		if (array_key_exists('cmp_name', $campaign)) {
			$cmp_name_id = CampaignTracker::getCampaignNameID($campaign['cmp_name']);
		}

		// update user record with compaign IDs
		if ($stmt = $db->prepare('UPDATE u_users SET
			reg_cmp_source_id = ?,
			reg_cmp_medium_id = ?,
			reg_cmp_keywords_id = ?,
			reg_cmp_content_id = ?,
			reg_cmp_name_id = ?
			WHERE id = ?')) {
			if (!$stmt->bind_param('sssssi', $cmp_source_id, $cmp_medium_id, $cmp_keywords_id, $cmp_content_id, $cmp_name_id, $this->userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}
	}

	/**
	 * Returns campaign they came from when registerd
	 *
	 * @return array Array of campaign parameters
	 *
	 * @throws DBException
	 */
	public function getCampaign() {
		$db = UserConfig::getDB();

		$campaign = array();

		if ($stmt = $db->prepare('SELECT cmp.name, cmp_content.content, cmp_keywords.keywords, cmp_medium.medium, cmp_source.source
			FROM u_users AS users
				LEFT JOIN u_cmp AS cmp ON users.reg_cmp_name_id = cmp.id
				LEFT JOIN u_cmp_content AS cmp_content ON users.reg_cmp_content_id = cmp_content.id
				LEFT JOIN u_cmp_keywords AS cmp_keywords ON users.reg_cmp_keywords_id = cmp_keywords.id
				LEFT JOIN u_cmp_medium AS cmp_medium ON users.reg_cmp_medium_id = cmp_medium.id
				LEFT JOIN u_cmp_source AS cmp_source ON users.reg_cmp_source_id = cmp_source.id
			WHERE users.id = ?')) {
			if (!$stmt->bind_param('i', $this->userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($cmp_name, $cmp_content, $cmp_keywords, $cmp_medium, $cmp_source)) {
				throw new DBBindResultException($db, $stmt);
			}

			if ($stmt->fetch() === TRUE) {
				$campaign['cmp_name'] = $cmp_name;
				$campaign['cmp_content'] = $cmp_content;
				$campaign['cmp_keywords'] = $cmp_keywords;
				$campaign['cmp_medium'] = $cmp_medium;
				$campaign['cmp_source'] = $cmp_source;
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $campaign;
	}

	/**
	 * Returns 2-dimensional array with first keys for each campaign param and second keys for each value of that parameter, values of the array are arrays of users.
	 *
	 * @param int $days A number of days to look back for
	 *
	 * @return array Array of campaign users data
	 *
	 * @throws DBException
	 */
	public static function getCampaigns($days = 30) {
		$db = UserConfig::getDB();

		$campaigns = array();

		if ($stmt = $db->prepare('SELECT cmp.name, cmp_content.content, cmp_keywords.keywords, cmp_medium.medium, cmp_source.source,
			users.id, status, users.name, username, email, requirespassreset, fb_id, fb_link, UNIX_TIMESTAMP(regtime), points, email_verified
			FROM u_users AS users
				LEFT JOIN u_cmp AS cmp ON users.reg_cmp_name_id = cmp.id
				LEFT JOIN u_cmp_content AS cmp_content ON users.reg_cmp_content_id = cmp_content.id
				LEFT JOIN u_cmp_keywords AS cmp_keywords ON users.reg_cmp_keywords_id = cmp_keywords.id
				LEFT JOIN u_cmp_medium AS cmp_medium ON users.reg_cmp_medium_id = cmp_medium.id
				LEFT JOIN u_cmp_source AS cmp_source ON users.reg_cmp_source_id = cmp_source.id
			WHERE regtime > DATE_SUB(NOW(), INTERVAL ? DAY)
				AND (reg_cmp_name_id IS NOT NULL
					OR reg_cmp_content_id IS NOT NULL
					OR reg_cmp_keywords_id IS NOT NULL
					OR reg_cmp_medium_id IS NOT NULL
					OR reg_cmp_source_id IS NOT NULL
				)
				ORDER BY regtime DESC')) {
			if (!$stmt->bind_param('i', $days)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($cmp_name, $cmp_content, $cmp_keywords, $cmp_medium, $cmp_source, $userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$user = new self($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified);

				if (!is_null($cmp_name)) {
					$campaigns['cmp_name'][$cmp_name][] = $user;
				}
				if (!is_null($cmp_content)) {
					$campaigns['cmp_content'][$cmp_content][] = $user;
				}
				if (!is_null($cmp_keywords)) {
					$campaigns['cmp_keywords'][$cmp_keywords][] = $user;
				}
				if (!is_null($cmp_medium)) {
					$campaigns['cmp_medium'][$cmp_medium][] = $user;
				}
				if (!is_null($cmp_source)) {
					$campaigns['cmp_source'][$cmp_source][] = $user;
				}
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $campaigns;
	}

	/**
	 * Creates a personal account for the user meking them an admin
	 *
	 * @param bool $set_as_current To set as current account (default) or not
	 *
	 * @return Account
	 */
	public function createPersonalAccount($set_as_current = true) {
		$personal = Account::createPersonalAccount($this);
		if ($set_as_current) {
			$personal->setAsCurrent($this);
		}
		return $personal;
	}

	/**
	 * Returns invitation that was used to invite this user
	 *
	 * @return Invitation
	 *
	 * @throws DBException
	 */
	public function getInvitation() {
		return Invitation::getUserInvitation($this);
	}

	/**
	 * This method is called when new user is created
	 *
	 * @throws DBException
	 */
	private function init() {
		$invitation_code = null;
		if (array_key_exists(UserConfig::$invitation_code_key, $_SESSION)) {
			$invitation_code = $_SESSION[UserConfig::$invitation_code_key];
			unset($_SESSION[UserConfig::$invitation_code_key]);
		}

		$invitation = null;
		if (!is_null($invitation_code)) {
			$invitation = Invitation::getByCode($invitation_code);
		}

		if (!is_null($invitation)) {
			$invitation->setUser($this);
			$invitation->save();
		}

		$db = UserConfig::getDB();

		$userid = $this->getID();

		if ($stmt = $db->prepare('INSERT INTO u_user_preferences (user_id) VALUES (?)')) {
			if (!$stmt->bind_param('i', $userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt, "Can't initialize user preferences");
			}
			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db, "Can't prepare DB statement to initialize user preferences");
		}

		$invitation_account = null;
		$plan = null;
		if (!is_null($invitation)) {
			$invitation_account = $invitation->getAccount();

			// only add to account if invited by the admin of a non-individual account
			if ($invitation_account !== NULL &&
					!$invitation_account->isIndividual() &&
					$invitation_account->getUserRole($invitation->getIssuer()) === Account::ROLE_ADMIN
			) {
				$invitation_account->addUser($this);
			}

			$plan = $invitation->getPlan();
		}

		$new_user_account = null;
		if (is_null($invitation_account) || UserConfig::$createPersonalAccountsIfInvitedToGroupAccount) {
			if ($plan) {
				$new_user_account = Account::createAccount($this->name, $plan->getSlug(), null, $this, Account::ROLE_ADMIN);
			} else {
				$new_user_account = $this->createPersonalAccount(false);
			}
		}

		$current_account = is_null($invitation_account) ? $new_user_account : $invitation_account;
		$current_account->setAsCurrent($this);

		if (!is_null(UserConfig::$onCreate)) {
			call_user_func_array(UserConfig::$onCreate, array($this));
		}

		if (!is_null(UserConfig::$email_module)) {
			UserConfig::$email_module->registerSubscriber($this);
		}
	}

	/**
	 * Verifies email link code and marks user's email as verified.
	 *
	 * If optional User object is passed, will only verify code for this user,
	 * otherwise will search among all users in the system.
	 *
	 * Will also reset the code if successful
	 *
	 * @param string $code Code to verify
	 * @param User $user Optional user object if user is logged in
	 *
	 * @return boolean Is code associated with a user or not
	 *
	 * @throws DBException
	 */
	public static function verifyEmailLinkCode($code, User $user = null) {
		$db = UserConfig::getDB();

		$verified = false;

		$code = trim($code);

		/*
		 * If code is empty, fail silently
		 */
		if (strlen($code) == 0) {
			return false;
		}

		if (is_null($user)) {
			$query = 'UPDATE u_users
						SET email_verified = 1,
							email_verification_code = null,
							email_verification_code_time = null
						WHERE email_verification_code = ?
							AND email_verification_code_time > DATE_SUB(NOW(), INTERVAL ? DAY)';
		} else {
			$query = 'UPDATE u_users
						SET email_verified = 1,
							email_verification_code = null,
							email_verification_code_time = null
						WHERE id = ?
							AND email_verification_code = ?
							AND email_verification_code_time > DATE_SUB(NOW(), INTERVAL ? DAY)';
		}

		if ($stmt = $db->prepare($query)) {
			if (is_null($user)) {
				if (!$stmt->bind_param('si', $code, UserConfig::$emailVerificationCodeExpiresInDays)) {
					throw new DBBindParamException($db, $stmt);
				}
			} else {
				$user_id = $user->getID();

				if (!$stmt->bind_param('isi', $user_id, $code, UserConfig::$emailVerificationCodeExpiresInDays)) {
					throw new DBBindParamException($db, $stmt);
				}
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$verified = ($db->affected_rows == 1);

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $verified;
	}

	public function getEmailVerificationCode() {
		$db = UserConfig::getDB();

		$code = substr(base64_encode(UserTools::randomBytes(50)), 0, 10);

		if ($stmt = $db->prepare('UPDATE u_users SET
										email_verification_code = ?,
										email_verification_code_time = now()
									WHERE id = ?')
		) {
			if (!$stmt->bind_param('si', $code, $this->userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $code;
	}

	/**
	 * Sends email verification emai to user's email address
	 *
	 * Generates a new verification code and sends it to the user's email address
	 *
	 * @throws DBException
	 */
	public function sendEmailVerificationCode() {
		$email = $this->getEmail();
		$name = $this->getName();

		// Silently fail to avoid email discovery
		if (is_null($email)) {
			return;
		}

		$code = $this->getEmailVerificationCode();

		$verification_link = UserConfig::$USERSROOTFULLURL . '/verify_email.php?code=' . urlencode($code);

		$message_body = call_user_func_array(UserConfig::$onRenderVerificationCodeEmail, array($verification_link, $code));

		$subject = UserConfig::$emailVerificationSubject;

		$message = new Swift_Message($subject, $message_body);
		$message->setFrom(array(UserConfig::$supportEmailFromEmail => UserConfig::$supportEmailFromName));
		$message->setTo(array($email => $name));
		$message->setReplyTo(array(UserConfig::$supportEmailReplyTo));

		$headers = $message->getHeaders();
		$headers->addTextHeader('X-Mailer', UserConfig::$supportEmailXMailer);

		try {
			$result = UserConfig::getMailer()->send($message);
		} catch (Exception $e) {
			UserTools::debug($e->getMessage());
		}
	}

	/**
	 * Sends email message inviting another person to join the system
	 *
	 * @param string $name Name of the receipient
	 * @param string $email Email of reciepient
	 * @param string $note (optional) Invitation message
	 * @param Account $account (optional) Account object if user is invited to join an account
	 */
	public function sendInvitation($name, $email, $account = null) {
		Invitation::sendUserInvitation($this, $name, $email, $account);
	}

	/**
	 * Returns invitations initiated by a user
	 *
	 * @return Invitation[] Invitations sent, but not accepted yet
	 */
	public function getSentInvitations() {
		return Invitation::getSent(false, $this);
	}

	/**
	 * Returns an array of invitations that were accepted
	 *
	 * @return Invitation[] Accepted invitations
	 */
	public function getAcceptedInvitations() {
		return Invitation::getAccepted(false, $this);
	}

	/**
	 * Create new user based on facebook info
	 *
	 * Used by FacebookAuthenticationModule
	 *
	 * @param string $name User's display name
	 * @param int $fb_id Facebook user ID
	 * @param array $me Extra user info key/value pairs from /me Graph API call
	 *
	 * @return User Newly created user object
	 *
	 * @throws DBException
	 */
	public static function createNewFacebookUser($name, $fb_id, $fb_link = null, $me = null) {
		$name = mb_convert_encoding($name, 'UTF-8');

		$db = UserConfig::getDB();

		$email = null;
		if (array_key_exists('email', $me)) {
			$email = $me['email'];
		}

		$existing_users = User::getUsersByEmailOrUsername($email);
		if (count($existing_users) > 0) {
			throw new ExistingUserException($existing_users[0]);
		}

		$user = null;

		if ($stmt = $db->prepare("INSERT INTO u_users (name, regmodule, tos_version, email, fb_id, fb_link) VALUES (?, 'facebook', ?, ?, ?, ?)")) {
			if (!$stmt->bind_param('sisis', $name, UserConfig::$currentTOSVersion, $email, $fb_id, $fb_link)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			$id = $stmt->insert_id;


			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		$user = self::getUser($id);
		$user->setReferer();
		$user->setRegCampaign();
		$user->init();
		$user->sendEmailVerificationCode();

		return $user;
	}

	/*
	 */

	/**
	 * Create new user without credentials
	 *
	 * Used primarily by modules that will store credentials separately from user table
	 * Can also be used directly to create "shallow" accounts
	 *
	 * @param StartupAPIModule $module Registratin module used when registering the user
	 * @param string $name User's display name
	 * @param string $email User's emaol or null if no email is known
	 * @param boolean $send_verification_code Whatever to send verification email or not
	 *
	 * @return User Newly created user object
	 *
	 * @throws DBException
	 */
	public static function createNewWithoutCredentials(StartupAPIModule $module, $name, $email = null, $send_verification_code = TRUE) {
		$module_id = $module->getID();

		$name = mb_convert_encoding($name, 'UTF-8');

		$db = UserConfig::getDB();

		$user = null;

		$email = filter_var($email, FILTER_VALIDATE_EMAIL);
		if ($email === FALSE) {
			$email = null;
		}

		if ($email) {
			$existing_users = User::getUsersByEmailOrUsername($email);

			if (count($existing_users) > 0) {
				throw new ExistingUserException($existing_users[0]);
			}
		}

		if ($stmt = $db->prepare('INSERT INTO u_users (name, email, regmodule, tos_version) VALUES (?, ?, ?, ?)')) {
			if (!$stmt->bind_param('sssi', $name, $email, $module_id, UserConfig::$currentTOSVersion)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			$id = $stmt->insert_id;

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		$user = self::getUser($id);
		$user->setReferer();
		$user->setRegCampaign();
		$user->init();

		if ($send_verification_code) {
			$user->sendEmailVerificationCode();
		}

		return $user;
	}

	/**
	 * Create new user with username and password
	 *
	 * Used by UsernamePasswordAuthenticationModule
	 *
	 * @param string $name User's display name
	 * @param string $username User's login name/username
	 * @param string $email User's email
	 * @param string $password User's password
	 *
	 * @return User Newly created user object
	 *
	 * @throws DBException
	 */
	public static function createNew($name, $username, $email, $password) {
		$name = mb_convert_encoding($name, 'UTF-8');
		$username = mb_convert_encoding($username, 'UTF-8');

		$db = UserConfig::getDB();

		$user = null;

		$salt = substr(base64_encode(UserTools::randomBytes(50)), 0, 13);

		$pass = sha1($salt . $password);

		if ($stmt = $db->prepare("INSERT INTO u_users (regmodule, tos_version, name, username, email, pass, salt) VALUES ('userpass', ?, ?, ?, ?, ?, ?)")) {
			if (!$stmt->bind_param('isssss', UserConfig::$currentTOSVersion, $name, $username, $email, $pass, $salt)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			$id = $stmt->insert_id;

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		$user = self::getUser($id);
		$user->setReferer();
		$user->setRegCampaign();
		$user->init();
		$user->sendEmailVerificationCode();

		return $user;
	}

	/**
	 * Deletes user from the system
	 *
	 * @throws DBException
	 */
	public function delete() {

		$username = mb_convert_encoding($this->username, 'UTF-8');

		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('DELETE FROM u_users WHERE username = ?')) {
			if (!$stmt->bind_param('s', $username)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}
	}

	/**
	 * Returns total number of users in the system
	 *
	 * @return int Total number of users (including disabled users)
	 *
	 * @throws DBException
	 */
	public static function getTotalUsers() {
		$db = UserConfig::getDB();

		$total = 0;

		if ($stmt = $db->prepare('SELECT COUNT(*) FROM u_users')) {
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($total)) {
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $total;
	}

	/**
	 * Returns a number of active users (with activity after one day from registration)
	 *
	 * If date is passed, will calculate active users as of particular day (used for charts).
	 * Relatively data-intensive task, try to cache this data when produced (it would not change for the past dates)
	 *
	 * @param string $date MySQL-formatted date to get the statistics for
	 *
	 * @return int Number of active users
	 *
	 * @throws DBException
	 */
	public static function getActiveUsers($date = null) {
		$db = UserConfig::getDB();

		$total = 0;

		if (UserConfig::$adminActiveOnlyWithPoints) {
			$activities_with_points = array();

			foreach (UserConfig::$activities as $id => $activity) {
				if ($activity[1] > 0) {
					$activities_with_points[] = $id;
				}
			}

			// if there are no activities that can earn points, no users are active
			if (count($activities_with_points) == 0) {
				return 0;
			}

			$in = implode(', ', $activities_with_points);

			$query = 'SELECT count(*) AS total FROM (
					SELECT user_id, count(*)
					FROM u_activity a
					INNER JOIN u_users u
						ON a.user_id = u.id
					WHERE a.time > DATE_ADD(u.regtime, INTERVAL 1 DAY)
						AND a.time > DATE_SUB(' .
					(is_null($date) ? 'NOW()' : '?') .
					', INTERVAL 30 DAY)' .
					(is_null($date) ? '' : ' AND a.time < ?') . '
						AND a.activity_id IN (' . $in . ')
					GROUP BY user_id
				) AS active';
		} else {
			$query = 'SELECT count(*) AS total FROM (
					SELECT user_id, count(*)
					FROM u_activity a
					INNER JOIN u_users u
						ON a.user_id = u.id
					WHERE a.time > DATE_ADD(u.regtime, INTERVAL 1 DAY)
						AND a.time > DATE_SUB(' .
					(is_null($date) ? 'NOW()' : '?') .
					', INTERVAL 30 DAY)' .
					(is_null($date) ? '' : ' AND a.time < ?') . '
					GROUP BY user_id
				) AS active';
		}

		if ($stmt = $db->prepare($query)) {
			if (!is_null($date)) {
				if (!$stmt->bind_param('ss', $date, $date)) {
					throw new DBBindParamException($db, $stmt);
				}
			}

			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($total)) {
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $total;
	}

	/**
	 * Retrieves daily active users based on algorythm defined in getActiveUsers($date)
	 *
	 * @param int $lastndays Number of days to look back for
	 *
	 * @return array Array of active users numbers for the requested period
	 *
	 * @throws DBException
	 */
	public static function getDailyActiveUsers($lastndays = null) {
		$db = UserConfig::getDB();

		$daily_activity = array();

		$start_date = null;
		$start_day = null;
		$start_month = null;
		$start_year = null;

		// getting start date
		if ($stmt = $db->prepare('SELECT CAST(MIN(time) AS DATE) AS activity_date,
			DAYOFMONTH(MIN(time)) as day,
			MONTH(MIN(time)) as month,
			YEAR(MIN(time)) as year
			FROM u_activity' .
				((!is_null($lastndays) && is_int($lastndays)) ?
						' WHERE time > DATE_SUB(NOW(), INTERVAL ' . $lastndays . ' DAY)' : '')
		)) {
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($start_date, $start_day, $start_month, $start_year)) {
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		// no activities recorded yet
		if (is_null($start_date)) {
			return array();
		}

		// now getting all cached numbers
		if ($stmt = $db->prepare('SELECT day, active_users
			FROM u_admin_daily_stats_cache' .
				((!is_null($lastndays) && is_int($lastndays)) ?
						' WHERE day > DATE_SUB(NOW(), INTERVAL ' . $lastndays . ' DAY)' : ''))) {
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($date, $active_users)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$daily_activity[$date] = $active_users;
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		$timestamp = mktime(0, 0, 1, $start_month, $start_day, $start_year);
		$current_timestamp = time();

		$updates = array();

		while ($timestamp < $current_timestamp) {
			$date = date('Y-m-d', $timestamp);

			if (!array_key_exists($date, $daily_activity)) {
				$active_users = self::getActiveUsers($date);

				$daily_activity[$date] = $active_users;
				$updates[$date] = $active_users;
			}

			$timestamp = strtotime("+1 day", $timestamp);
		}

		// saving newly calculated values into cache
		$totalupdates = count($updates);

		if ($totalupdates > 0) {
			$query = 'REPLACE INTO u_admin_daily_stats_cache
				(day, active_users) VALUES';

			$first = true;
			foreach ($updates as $date => $active_users) {
				if (!$first) {
					$query .= ',';
				}
				$query .= " ('$date', $active_users)";

				$first = false;
			}

			if ($stmt = $db->prepare($query)) {
				if (!$stmt->execute()) {
					throw new DBExecuteStmtException($db, $stmt);
				}

				$stmt->close();
			} else {
				throw new DBPrepareStmtException($db);
			}
		}

		return $daily_activity;
	}

	/**
	 * Retrieves daily active users for activity
	 *
	 * @param int $activityid Activity ID
	 *
	 * @return array Array of activity counters for all dates when activity was recorded
	 *
	 * @throws DBException
	 *
	 * @internal Used in admin dashboard
	 */
	public static function getDailyPointsByActivity($activityid) {
		$db = UserConfig::getDB();

		$daily_activity = array();

		if ($stmt = $db->prepare('SELECT CAST(time AS DATE) AS activity_date, count(*) AS cnt FROM u_activity WHERE activity_id = ? GROUP BY activity_date')) {
			if (!$stmt->bind_param('i', $activityid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($date, $cnt)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$daily_activity[$date] = $cnt;
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $daily_activity;
	}

	/**
	 * Retrieves daily activity counters all or one user
	 *
	 * If $user parameter is passed, return activities only for that user, otherwise return for all users.
	 *
	 * @param User $user User object
	 *
	 * @return array Array of arrays (data, activity, total) for all dates when activities were recorded
	 *
	 * @throws DBException
	 *
	 * @internal Used in admin dashboard
	 */
	public static function getDailyActivityPoints(User $user = null) {
		$db = UserConfig::getDB();

		$daily_activity = array();

		if (!is_null($user)) {
			$user_id = $user->getID();

			$stmt = $db->prepare(
				"SELECT CAST(time AS DATE) AS activity_date, activity_id, count(*) AS total
					FROM u_activity
					WHERE user_id = ?
					GROUP BY activity_date, activity_id");

			if ($stmt) {
				if (!$stmt->bind_param('i', $user_id)) {
					throw new DBBindParamException($db, $stmt);
				}
			}
		} else if (count(UserConfig::$dont_display_activity_for) > 0) {
			$stmt = $db->prepare('SELECT CAST(time AS DATE) AS activity_date, activity_id, count(*) AS total FROM u_activity WHERE user_id NOT IN(' . join(', ', UserConfig::$dont_display_activity_for) . ') GROUP BY activity_date, activity_id');
		} else {
			$stmt = $db->prepare('SELECT CAST(time AS DATE) AS activity_date, activity_id, count(*) AS total FROM u_activity GROUP BY activity_date, activity_id');
		}

		if ($stmt) {
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($date, $id, $total)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$daily_activity[] = array('date' => $date, 'activity' => $id, 'total' => $total);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $daily_activity;
	}

	/**
	 * Aggregates activity points for users, should be ran within cron job
	 * on a daily basis or more often if needed.
	 *
	 * @throws DBException
	 */
	public static function aggregateActivityPoints() {
		$db = UserConfig::getDB();

		if ($db->query('CREATE TEMPORARY TABLE activity_points (
		     activity_id int(2) UNSIGNED NOT NULL,
		     points int(4) UNSIGNED NOT NULL)') === TRUE)
		{
			$query = 'INSERT INTO activity_points VALUES';
			$pairs = array();
			foreach (UserConfig::$activities as $id => $activity) {
				$pairs[] = "($id, ".$activity[1].')';
			}
			$query.=' '.implode(', ', $pairs);

			if ($db->query($query) === TRUE)
			{
				if ($db->query('CREATE TEMPORARY TABLE user_activity_points
						SELECT u.id AS user_id, SUM(p.points) AS points
						FROM u_users u
						INNER JOIN u_activity a ON u.id = a.user_id
						INNER JOIN activity_points p ON a.activity_id = p.activity_id
						GROUP BY u.id'))
				{
					if ($stmt = $db->prepare('UPDATE u_users u
							INNER JOIN user_activity_points up ON u.id = up.user_id
							SET u.points = up.points'))
					{
						if (!$stmt->execute())
						{
							throw new DBExecuteStmtException($db, $stmt);
						}

						$stmt->close();
					} else {
						throw new DBException($db);
					}

				} else {
					throw new DBException($db);
				}
			} else {
				throw new DBException($db);
			}
		}
		else
		{
			throw new DBException($db);
		}
	}

	/**
	 * Returns a number of users registered for each date
	 *
	 * @return array Array of registration numbers for each date
	 *
	 * @throws DBException
	 *
	 * @internal Used in admin dashboard
	 */
	public static function getDailyRegistrations() {
		$db = UserConfig::getDB();

		$dailyregs = array();

		if ($stmt = $db->prepare('SELECT CAST(regtime AS DATE) AS regdate, count(*) AS regs FROM u_users GROUP BY regdate')) {
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($regdate, $regs)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$dailyregs[] = array('regdate' => $regdate, 'regs' => $regs);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $dailyregs;
	}

	/**
	 * Returns a number of users registered using each moduled for each date
	 *
	 * @return array Array of arrays of registration numbers for each module for each date
	 *
	 * @throws DBException
	 *
	 * @internal Used in admin dashboard
	 */
	public static function getDailyRegistrationsByModule() {
		$db = UserConfig::getDB();

		$dailyregs = array();

		if ($stmt = $db->prepare('SELECT CAST(regtime AS DATE) AS regdate, regmodule, count(*) AS reg FROM u_users GROUP BY regdate, regmodule')) {
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($date, $module, $regs)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch()) {
				$dailyregs[$date][$module] = $regs;
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $dailyregs;
	}

	/*
	 * retrieves aggregated recent registrations numbers by module
	 */

	/**
	 * Returns recent registrations by module
	 *
	 * Returns recent (last 30 days) registration numbers for each module,
	 * used on admin dashboard to display registration by module breakdown
	 *
	 * @return array Array of recent registration counters for each authentication module
	 *
	 * @throws DBException
	 *
	 * @internal Used on admin dashboard
	 */
	public static function getRecentRegistrationsByModule() {
		$db = UserConfig::getDB();

		$regs = array();

		if ($stmt = $db->prepare('SELECT regmodule, count(*) AS reg FROM u_users u WHERE regtime > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY regmodule')) {
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($module, $reg)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch()) {
				$regs[$module] = $reg;
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $regs;
	}

	/**
	 * Returns user credentials object
	 *
	 * If module ID is passed, return credentials only for that module, otherwise return an array of credential objects
	 *
	 * Example
	 * <code>
	 * $user = User::require_login();
	 *
	 * $creds = $user->getUserCredentials('twitter');
	 *
	 * $result = $creds->makeOAuthRequest('https://api.twitter.com/1/statuses/home_timeline.json', 'GET');
	 * </code>
	 *
	 * @param string $requested_module_id Authentication module ID (e.g. 'facebook', 'twitter', 'google' and etc)
	 *
	 * @return UserCredentials|array User credentials object for the module or array of credentials for all modules
	 */
	public function getUserCredentials($requested_module_id = null) {
		$credentials = array();

		foreach (UserConfig::$authentication_modules as $module) {
			if (is_null($requested_module_id)) {
				$credentials[$module][] = $module->getUserCredentials($this);
			} else {
				if ($requested_module_id == $module->getID()) {
					return $module->getUserCredentials($this);
				}
			}
		}

		return $credentials;
	}

	/*
	 * retrieves paged list of users
	 */

	/**
	 * Returns paged list of basic user info
	 *
	 * @param int $pagenumber Page number
	 * @param int $perpage Number of rows per page
	 * @param string $sort String indicating the way to sort the list (either 'registration' or 'activity')
	 * @param string $date_from Registration date range start string in YYYYMMDD format
	 * @param string $date_to Registration date range end string in YYYYMMDD format
	 *
	 * @return User[] Array of user objects
	 *
	 * @throws DBException
	 *
	 * @internal Used in admin dashboard to show users
	 */
	public static function getUsers($pagenumber = 0, $perpage = 20, $sort = 'registration', $date_from = null, $date_to = null, $sort_order = false) {
		$db = UserConfig::getDB();

		$users = array();

		$first = $perpage * $pagenumber;

		$orderby = 'regtime';
		if ($sort == 'activity') {
			$orderby = 'points';
		}

		$where_conditions = array();

		if (!is_null($date_from)) {
			$where_conditions[] = 'regtime >= ?';
		}

		if (!is_null($date_to)) {
			$where_conditions[] = 'regtime <= DATE_ADD(?, INTERVAL 1 DAY)';
		}

		$where = '';
		if (count($where_conditions) > 0) {
			$where = ' WHERE ' . implode(' AND ', $where_conditions);
		}

		$query = 'SELECT id, status, name, username, email, requirespassreset, fb_id, fb_link, UNIX_TIMESTAMP(regtime), points, email_verified
			FROM u_users ' .
				$where . '
			ORDER BY ' . $orderby . ' ' . ($sort_order ? 'ASC' : 'DESC') . '
			LIMIT ?, ?';

		if ($stmt = $db->prepare($query)) {
			if (!is_null($date_from) && !is_null($date_to)) {
				$result = $stmt->bind_param('ssii', $date_from, $date_to, $first, $perpage);
			} else if (!is_null($date_from)) {
				$result = $stmt->bind_param('sii', $date_from, $first, $perpage);
			} else if (!is_null($date_to)) {
				$result = $stmt->bind_param('sii', $date_to, $first, $perpage);
			} else {
				$result = $stmt->bind_param('ii', $first, $perpage);
			}

			if (!$result) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$users[] = new self($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $users;
	}

	/**
	 * Returns a paged list of users based on search query
	 *
	 * Query is matched (substring) against user's display name, username or email address
	 *
	 * @param string $search Search query
	 * @param int $pagenumber Page number
	 * @param int $perpage Number of rows per page
	 * @param string $sort String indicating the way to sort the list (either 'registration' or 'activity')
	 *
	 * @return User[] Array of user objects
	 *
	 * @throws DBException
	 *
	 * @internal Used in admin dashboard
	 */
	public static function searchUsers($search, $pagenumber = 0, $perpage = 20, $sort = 'registration') {
		$db = UserConfig::getDB();

		$users = array();

		$first = $perpage * $pagenumber;

		$orderby = 'regtime';
		if ($sort == 'activity') {
			$orderby = 'points';
		}

		if ($stmt = $db->prepare('SELECT id, status, name, username, email, requirespassreset, fb_id, fb_link, UNIX_TIMESTAMP(regtime), points, email_verified FROM u_users WHERE INSTR(name, ?) > 0 OR INSTR(username, ?) > 0 OR INSTR(email, ?) > 0 ORDER BY ' . $orderby . ' DESC LIMIT ?, ?')) {
			if (!$stmt->bind_param('sssii', $search, $search, $search, $first, $perpage)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$users[] = new self($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $users;
	}

	/*
	 * retrieves a list of latest activities
	 */

	/**
	 * Returns a paged list of user's activities
	 *
	 * @param boolean $all Set to false if you want to return only acttivities with non-zero value
	 * @param int $pagenumber Page number
	 * @param int $perpage Number of rows per page
	 *
	 * @return array Array of (time, user_id, activity_id) records
	 *
	 * @throws DBException
	 *
	 * @internal Used in admin dashboard
	 */
	public static function getUsersActivity($all, $pagenumber = 0, $perpage = 20) {
		$activities = array();

		$exclude = '';
		if (count(UserConfig::$dont_display_activity_for) > 0) {
			$exclude = ' user_id NOT IN(' . join(', ', UserConfig::$dont_display_activity_for) . ') ';
		}

		if ($all) {
			$query = 'SELECT UNIX_TIMESTAMP(time) as time, user_id, activity_id FROM u_activity ' . ($exclude != '' ? 'WHERE ' . $exclude : '') . ' ORDER BY time DESC LIMIT ?, ?';
		} else {
			$ids = array();

			foreach (UserConfig::$activities as $id => $activity) {
				if ($activity[1] > 0) {
					$ids[] = $id;
				}
			}

			if (count($ids) == 0) {
				return $activities; // no activities are configured to be worthy
			}

			$query = 'SELECT UNIX_TIMESTAMP(time) as time, user_id, activity_id FROM u_activity WHERE activity_id IN (' . implode(', ', $ids) . ') ' . ($exclude != '' ? 'AND ' . $exclude : '') . 'ORDER BY time DESC LIMIT ?, ?';
		}

		$db = UserConfig::getDB();

		$first = $perpage * $pagenumber;

		if ($stmt = $db->prepare($query)) {
			if (!$stmt->bind_param('ii', $first, $perpage)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($time, $user_id, $activity_id)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$activities[] = array('time' => $time, 'user_id' => $user_id, 'activity_id' => $activity_id);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $activities;
	}

	/**
	 * Returns paged list users who performed particular activity
	 *
	 * @param int $activityid Activity ID
	 * @param int $pagenumber Page number
	 * @param int $perpage Number of rows per page
	 *
	 * @return array Array of (time, user_id) records
	 *
	 * @throws DBException
	 *
	 * @internal Used in admin dashboard
	 */
	public static function getUsersByActivity($activityid, $pagenumber = 0, $perpage = 20) {
		$activities = array();

		$exclude = '';
		if (count(UserConfig::$dont_display_activity_for) > 0) {
			$exclude = ' AND user_id NOT IN(' . join(', ', UserConfig::$dont_display_activity_for) . ') ';
		}

		$query = 'SELECT UNIX_TIMESTAMP(time) as time, user_id FROM u_activity WHERE activity_id = ? ' . $exclude . ' ORDER BY time DESC LIMIT ?, ?';

		$db = UserConfig::getDB();

		$first = $perpage * $pagenumber;

		if ($stmt = $db->prepare($query)) {
			if (!$stmt->bind_param('iii', $activityid, $first, $perpage)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($time, $user_id)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$activities[] = array('time' => $time, 'user_id' => $user_id);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $activities;
	}

	/**
	 * Return a list of users who match a username or email
	 *
	 * Used to retrieve user based on a login form.
	 * @TODO CONFIRM that this call can return multiple objects, including users with unverified emails.
	 *
	 * @param string $nameoremail String with username or email address
	 *
	 * @return array Array of User objects
	 *
	 * @throws DBException
	 */
	public static function getUsersByEmailOrUsername($nameoremail) {
		$db = UserConfig::getDB();

		$nameoremail = trim($nameoremail);

		$users = array();

		if ($stmt = $db->prepare('SELECT id, status, name, username, email, requirespassreset, fb_id, fb_link, UNIX_TIMESTAMP(regtime), points, email_verified FROM u_users WHERE username = ? OR email = ?')) {
			if (!$stmt->bind_param('ss', $nameoremail, $nameoremail)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$users[] = new self($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $users;
	}

	/*
	 * retrieve activity statistics
	 */

	/**
	 * Returns total activity counts for all activities
	 *
	 * @return array Array of activity_id/count pairs
	 *
	 * @throws DBException
	 *
	 * @internal Used in admin dashboard
	 */
	public static function getActivityStatistics() {
		$stats = array();

		$where = '';
		if (count(UserConfig::$dont_display_activity_for) > 0) {
			$where = ' WHERE user_id NOT IN(' . join(', ', UserConfig::$dont_display_activity_for) . ') ';
		}

		$query = "SELECT activity_id, count(*) as cnt FROM u_activity $where GROUP BY activity_id";

		$db = UserConfig::getDB();

		if ($stmt = $db->prepare($query)) {
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($activity_id, $cnt)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$stats[$activity_id] = $cnt;
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $stats;
	}

	/**
	 * Returns a paged list of activities
	 *
	 * @param boolean $all Set to false if you want to return only acttivities with non-zero value
	 * @param int $pagenumber Page number
	 * @param int $perpage Number of rows per page
	 *
	 * @return array Array of (time, user_id, activity_id) records
	 *
	 * @throws DBException
	 *
	 * @internal Used in admin dashboard
	 */
	public function getActivity($all, $pagenumber = 0, $perpage = 20) {
		$activities = array();

		if ($all) {
			$query = 'SELECT UNIX_TIMESTAMP(time) as time, user_id, activity_id FROM u_activity WHERE user_id = ? ORDER BY time DESC LIMIT ?, ?';
		} else {
			$ids = array();

			foreach (UserConfig::$activities as $id => $activity) {
				if ($activity[1] > 0) {
					$ids[] = $id;
				}
			}

			if (count($ids) == 0) {
				return $activities; // no activities are configured to be worthy
			}

			$query = 'SELECT UNIX_TIMESTAMP(time) as time, user_id, activity_id FROM u_activity WHERE user_id = ? AND activity_id IN (' . implode(', ', $ids) . ')  ORDER BY time DESC LIMIT ?, ?';
		}

		$db = UserConfig::getDB();

		$first = $perpage * $pagenumber;

		if ($stmt = $db->prepare($query)) {
			if (!$stmt->bind_param('iii', $this->userid, $first, $perpage)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($time, $user_id, $activity_id)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$activities[] = array('time' => $time, 'user_id' => $user_id, 'activity_id' => $activity_id);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $activities;
	}

	/**
	 * Generates password recovery code and saves it to the database for later matching
	 *
	 * Temporary password is only valid for a short period of time and also reset upon successful entry of temporary or current password
	 *
	 * @see resetTemporaryPassword
	 *
	 * @return string
	 *
	 * @throws DBException
	 */
	public function generateTemporaryPassword() {
		$db = UserConfig::getDB();

		$temppass = substr(base64_encode(UserTools::randomBytes(50)), 0, 13);

		if ($stmt = $db->prepare('UPDATE u_users SET temppass = ?, temppasstime = now() WHERE id = ?')) {
			if (!$stmt->bind_param('si', $temppass, $this->userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $temppass;
	}

	/**
	 * Resets temporary password
	 *
	 * @see generateTemporaryPassword
	 *
	 * @throws DBException
	 */
	public function resetTemporaryPassword() {
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('UPDATE u_users SET temppass = null, temppasstime = null WHERE id = ?')) {
			if (!$stmt->bind_param('s', $this->userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}
	}

	/**
	 * Records user registration module (should be used only once)
	 *
	 * Deprecated - module should be set right away upon user creation
	 *
	 * @param StartupAPIModule $module Startup API Module object
	 *
	 * @throws DBException
	 *
	 * @deprecated
	 */
	public function setRegistrationModule(StartupAPIModule $module) {
		$db = UserConfig::getDB();

		$module_id = $module->getID();

		if ($stmt = $db->prepare('UPDATE u_users SET regmodule = ? WHERE id = ?')) {
			if (!$stmt->bind_param('si', $module_id, $this->userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}
	}

	/*
	 * retrieves user information by array of IDs
	 */

	/**
	 * Returns a list of users by a list of user IDs
	 *
	 * Use this method if you need to retrieve multiple users - it only makes on DB request
	 *
	 * @param array $userids Array of integers representing user IDs
	 *
	 * @return User[] Array of User objects
	 *
	 * @throws DBException
	 */
	public static function getUsersByIDs($userids) {
		$db = UserConfig::getDB();

		$users = array();

		if (!is_array($userids) || count($userids) == 0) {
			return $users;
		}

		$ids = array();
		foreach ($userids as $userid) {
			if (is_int($userid)) {
				$ids[] = $userid;
			}
		}

		$idlist = join(', ', $ids);

		if ($stmt = $db->prepare('SELECT id, status, name, username, email, requirespassreset, fb_id, fb_link, UNIX_TIMESTAMP(regtime), points, email_verified FROM u_users WHERE id IN (' . $idlist . ')')) {
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$users[] = new self($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $users;
	}

	/**
	 * Returns a user by username and password
	 *
	 * Returns user object or null if username and password do not match.
	 * It also resets temporary password if user remembered their old password.
	 *
	 * @param string $entered_username Username
	 * @param string $entered_password Password
	 *
	 * @return User|null User object or null if username and password do not match
	 *
	 * @throws DBException
	 */
	public static function getUserByUsernamePassword($entered_username, $entered_password) {
		$db = UserConfig::getDB();

		$user = null;

		if ($stmt = $db->prepare('SELECT id, status, name, username, email, pass, salt, temppass, requirespassreset, fb_id, fb_link, UNIX_TIMESTAMP(regtime), points, email_verified FROM u_users WHERE username = ?')) {
			if (!$stmt->bind_param('s', $entered_username)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($id, $status, $name, $username, $email, $pass, $salt, $temppass, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified)) {
				throw new DBBindResultException($db, $stmt);
			}

			if ($stmt->fetch() === TRUE) {
				if (sha1($salt . $entered_password) == $pass) {
					$user = new self($id, $status, $name, $username, $email, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified);
				}
			}

			$stmt->close();

			// if user used password recovery and remembered his old password
			// then clean temporary password and password reset flag
			// (don't reset the flag if was was set for some other reasons)
			if (!is_null($user) && !$user->isDisabled() && !is_null($temppass) && $user->requiresPasswordReset()) {
				$user->setRequiresPasswordReset(false);
				$user->save();

				$user->resetTemporaryPassword();
			}
		} else {
			throw new DBPrepareStmtException($db);
		}

		if (is_null($user)) {
			if ($stmt = $db->prepare('SELECT id, status, name, username, email, fb_id, fb_link, UNIX_TIMESTAMP(regtime), points, email_verified FROM u_users WHERE username = ? AND temppass = ? AND temppasstime > DATE_SUB(NOW(), INTERVAL 1 DAY)')) {
				if (!$stmt->bind_param('ss', $entered_username, $entered_password)) {
					throw new DBBindParamException($db, $stmt);
				}
				if (!$stmt->execute()) {
					throw new DBExecuteStmtException($db, $stmt);
				}
				if (!$stmt->bind_result($id, $status, $name, $username, $email, $fb_id, $fb_link, $regtime, $points, $is_email_verified)) {
					throw new DBBindResultException($db, $stmt);
				}

				if ($stmt->fetch() === TRUE) {
					$user = new self($id, $status, $name, $username, $email, null, $fb_id, $fb_link, $regtime, $points, $is_email_verified);
				}

				$stmt->close();

				if (!is_null($user)) {
					$user->setRequiresPasswordReset(true);
					$user->save();
				}
			} else {
				throw new DBPrepareStmtException($db);
			}
		} else {
			$user->resetTemporaryPassword();
		}

		// do not let disabled users in
		if (!is_null($user) && $user->isDisabled()) {
			return null;
		}

		return $user;
	}

	/*
	 *
	 */

	/**
	 * Retrieves user information by Facebook ID
	 *
	 * @param int $fb_id Facebook user ID
	 *
	 * @return User|null User object if user with such ID does not exist
	 *
	 * @throws DBException
	 */
	public static function getUserByFacebookID($fb_id) {
		$db = UserConfig::getDB();

		$user = null;

		if ($stmt = $db->prepare('SELECT id, status, name, username, email, requirespassreset, fb_link, UNIX_TIMESTAMP(regtime), points, email_verified FROM u_users WHERE fb_id = ?')) {
			if (!$stmt->bind_param('i', $fb_id)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($userid, $status, $name, $username, $email, $requirespassreset, $fb_link, $regtime, $points, $is_email_verified)) {
				throw new DBBindResultException($db, $stmt);
			}

			if ($stmt->fetch() === TRUE) {
				$user = new self($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $user;
	}

	/**
	 * Returns user by ID or null if user with such ID does not exist
	 *
	 * @param int $userid User ID
	 *
	 * @return User|null User object
	 *
	 * @throws DBException
	 */
	public static function getUser($userid) {
		$db = UserConfig::getDB();

		$user = null;

		if ($stmt = $db->prepare('SELECT status, name, username, email, requirespassreset, fb_id, fb_link, UNIX_TIMESTAMP(regtime), points, email_verified FROM u_users WHERE id = ?')) {
			if (!$stmt->bind_param('i', $userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($status, $name, $username, $email, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified)) {
				throw new DBBindResultException($db, $stmt);
			}

			if ($stmt->fetch() === TRUE) {
				$user = new self($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $fb_link, $regtime, $points, $is_email_verified);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $user;
	}

	/**
	 * Set's user's return cookie to track where to go upon login/registration
	 *
	 * @param string $return Return URL
	 *
	 * @throws StartupAPIException
	 */
	private static function setReturn($return) {
		$storage = new MrClay_CookieStorage(array(
					'secret' => UserConfig::$SESSION_SECRET,
					'path' => UserConfig::$SITEROOTURL,
					'expire' => 0,
					'httponly' => true
				));

		if (!$storage->store(UserConfig::$session_return_key, $return)) {
			throw new StartupAPIException(implode('; ', $storage->errors));
		}
	}

	/**
	 * Retrieve return URL
	 *
	 * @return string|null Return URL or null if no return URL was stored
	 */
	public static function getReturn() {
		$storage = new MrClay_CookieStorage(array(
					'secret' => UserConfig::$SESSION_SECRET,
					'path' => UserConfig::$SITEROOTURL,
					'httponly' => true
				));

		$return = $storage->fetch(UserConfig::$session_return_key);

		if (is_string($return)) {
			return $return;
		} else {
			return null;
		}
	}

	/**
	 * Crears return URL
	 */
	public static function clearReturn() {
		$storage = new MrClay_CookieStorage(array(
					'secret' => UserConfig::$SESSION_SECRET,
					'path' => UserConfig::$SITEROOTURL,
					'httponly' => true
				));

		$storage->delete(UserConfig::$session_return_key);
	}

	/**
	 * Sends user to login page (remembering current page to be returned to upon success)
	 */
	public static function redirectToLogin() {
		self::setReturn($_SERVER['REQUEST_URI']);

		header('Location: ' . UserConfig::$USERSROOTURL . '/login.php');
		exit;
	}

	/**
	 * Sends user to password reset page (remembering current page to be returned to upon success)
	 */
	private static function redirectToPasswordReset() {
		self::setReturn($_SERVER['REQUEST_URI']);

		header('Location: ' . UserConfig::$USERSROOTURL . '/modules/usernamepass/passwordreset.php');
		exit;
	}

	/**
	 * Sends user to email verification page (remembering current page to be returned to upon success)
	 */
	private static function redirectToEmailVerification() {
		self::setReturn($_SERVER['REQUEST_URI']);

		header('Location: ' . UserConfig::$USERSROOTURL . '/verify_email.php');
		exit;
	}

	/**
	 * Numeric user ID used to uniquely identify user in the database
	 *
	 * @var int User ID
	 */
	private $userid;

	/**
	 * User status: true for active / false for disabled, stored in the database as 1 or 0
	 *
	 * @var boolean
	 */
	private $status;

	/**
	 * @var string User's display name
	 */
	private $name;

	/**
	 * @var string Username for logging in
	 */
	private $username;

	/**
	 * @var string User's email address
	 */
	private $email;

	/**
	 * @var boolean Is user's email verified or not
	 */
	private $is_email_verified;

	/**
	 * Whatever user is required to reset password on next login, stored in the database as 1 or 0
	 *
	 * @var boolean
	 */
	private $requirespassreset;

	/**
	 * @var int Facebook user ID
	 */
	private $fbid;

	/**
	 * @var string Facebook profile link
	 */
	private $fblink;

	/**
	 * @var int Unix timestamp indicating user's registration time
	 */
	private $regtime;

	/**
	 * @var int Total number of activity points user accumulated so far
	 *
	 * @see aggregatepoints.php
	 */
	private $points;

	/**
	 * @var User User currently impersonating this user or null if not being impersonated
	 */
	private $impersonator;

	/**
	 * Private constructor, creates new User object
	 *
	 * @param int $userid User ID
	 * @param boolean $status Status (enabled/disabled)
	 * @param string $name User's display name
	 * @param string $username Username
	 * @param string $email Email address
	 * @param boolean $requirespassreset Is this user required to reset their password
	 * @param int $fbid Facebook ID
	 * @param int $fblink Facebook profile link
	 * @param int $regtime Registration time
	 * @param int $points Total points user has
	 * @param boolean $is_email_verified Is user's email verified or not
	 */
	private function __construct($userid, $status = true, $name, $username = null, $email = null, $requirespassreset = false, $fbid = null, $fblink = null, $regtime = null, $points = 0, $is_email_verified = false) {
		$this->userid = $userid;
		$this->status = $status ? true : false;
		$this->name = $name;
		$this->username = $username;
		$this->email = $email;
		$this->requirespassreset = $requirespassreset ? true : false;
		$this->fbid = $fbid;
		$this->fblink = $fblink;
		$this->regtime = $regtime;
		$this->points = $points;
		$this->is_email_verified = $is_email_verified ? true : false;
	}

	/**
	 * Returns true if this user is required to reset the password
	 *
	 * @return boolean
	 */
	public function requiresPasswordReset() {
		return $this->requirespassreset;
	}

	/**
	 * Sets whatever user is required to reset their password
	 *
	 * You have to call save() method to persist to the database
	 *
	 * @param boolean $requires Set to true to require user to reset their password
	 */
	public function setRequiresPasswordReset($requires) {
		$this->requirespassreset = $requires ? true : false;
	}

	/**
	 * Returns numeric user ID
	 *
	 * @return int User ID
	 */
	public function getID() {
		return $this->userid;
	}

	/**
	 * Returns user's display name
	 *
	 * @return string User's display name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets user's display name
	 *
	 * You have to call save() method to persist to the database
	 *
	 * @param string $name User's display name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Return user's login name or null if none is set for the user
	 *
	 * @return string|null User's login name
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * Sets user's login name
	 *
	 * You have to call save() method to persist to the database
	 *
	 * @param string $username User's login name
	 *
	 * @throws StartupAPIException
	 */
	public function setUsername($username) {
		if (is_null($this->username)) {
			$this->username = $username;
		} else {
			throw new StartupAPIException('This user already has username set.');
		}
	}

	/**
	 * Returns user's email address or null if none is set for the user
	 *
	 * @return string|null User's email address
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * Sets user's email address
	 *
	 * You have to call save() method to persist to the database
	 *
	 * @param string $email User's email address
	 */
	public function setEmail($email) {
		if ($this->email != $email) {
			// Checking if there are other users with this email
			$existing_users = User::getUsersByEmailOrUsername($email);
			if (count($existing_users) > 0) {
				throw new ExistingUserException($existing_users[0]);
			}

			$this->setEmailVerified(false);
		}

		$this->email = $email;
	}

	/**
	 * Sets email verification flag
	 *
	 * You have to call save() method to persist to the database
	 *
	 * @param boolean $verified True if email address is verified, false otherwise
	 */
	private function setEmailVerified($verified) {
		$this->is_email_verified = $verified ? true : false;
	}

	/**
	 * Returns true if user's email address is verified
	 *
	 * @return boolean True if user's email is verified
	 */
	public function isEmailVerified() {
		return $this->is_email_verified;
	}

	/**
	 * Returns user's Facebook account ID or null if user doesn't have a facebook account associated
	 *
	 * @return int|null User's Facebook account ID
	 */
	public function getFacebookID() {
		return $this->fbid;
	}

	/**
	 * Sets user's Facebook account ID
	 *
	 * You have to call save() method to persist to the database
	 *
	 * @param int $fbid User's Facebook account ID
	 */
	public function setFacebookID($fbid) {
		$this->fbid = $fbid;
	}

	/**
	 * Sets user's Facebook profile link
	 *
	 * You have to call save() method to persist to the database
	 *
	 * @param string $fblink User's Facebook profile link
	 */
	public function setFacebookProfileLink($fblink) {
		$this->fblink = $fblink;
	}

	/**
	 * Returns user's Facebook profile link or null if user doesn't have a facebook account associated or link is not known
	 *
	 * @return string|null User's Facebook profile link
	 */
	public function getFacebookProfileLink() {
		return $this->fblink;
	}

	/**
	 * Sets user's status - true if active and false if disabled
	 *
	 * You have to call save() method to persist to the database
	 *
	 * @param boolean $status User's status
	 */
	public function setStatus($status) {
		$this->status = $status ? TRUE : FALSE;
	}

	/**
	 * Returns Unix timestamp for the time when user registered in the system
	 *
	 * @return int Unix timestamp representing user's registration time
	 */
	public function getRegTime() {
		return $this->regtime;
	}

	/**
	 * Returns aggregated user's activity points
	 *
	 * @return int Aggregated activity points
	 */
	public function getPoints() {
		return $this->points;
	}

	/**
	 * Compares this user object to another user object
	 *
	 * @param User $user User object to compare to
	 *
	 * @return boolean Returns whatever this is the same user or not
	 */
	public function isTheSameAs(User $user) {
		return $this->getID() == $user->getID();
	}

	/**
	 * Returns true if user is disabled (not active)
	 *
	 * @return boolean True if user is disabled
	 */
	public function isDisabled() {
		return (!$this->status ? true : false);
	}

	/**
	 * Checks user's password
	 *
	 * @param string $password User's password
	 *
	 * @return boolean True if password matches
	 *
	 * @throws DBException
	 */
	public function checkPass($password) {
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('SELECT pass, salt FROM u_users WHERE id = ?')) {
			if (!$stmt->bind_param('i', $this->userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($pass, $salt)) {
				throw new DBBindResultException($db, $stmt);
			}

			if ($stmt->fetch() === TRUE) {
				return ($pass == sha1($salt . $password));
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return false;
	}

	/**
	 * Sets user's password
	 *
	 * @param string $password New password
	 *
	 * @throws DBException
	 */
	public function setPass($password) {
		$db = UserConfig::getDB();

		$salt = substr(base64_encode(UserTools::randomBytes(50)), 0, 13);
		$pass = sha1($salt . $password);

		if ($stmt = $db->prepare('UPDATE u_users SET pass = ?, salt = ? WHERE id = ?')) {
			if (!$stmt->bind_param('ssi', $pass, $salt, $this->userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}
	}

	public function getSettings() {
		$db = UserConfig::getDB();

		$json = null;

		$userid = $this->userid;

		if ($stmt = $db->prepare('SELECT app_settings_json
				FROM u_user_preferences
				WHERE user_id = ?')
		) {
			if (!$stmt->bind_param('i', $userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->bind_result($json)) {
				throw new DBBindResultException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		$settings = json_decode($json, true);
		if (!is_array($settings)) {
			$settings = array();
		}

		return $settings;
	}

	public function saveSettings($settings) {
		$db = UserConfig::getDB();

		$json = json_encode($settings);

		if ($stmt = $db->prepare('UPDATE u_user_preferences
			SET app_settings_json = ? WHERE user_id = ?')
		) {
			if (!$stmt->bind_param('si', $json, $this->userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}
	}

	/**
	 * Persists user's data into the database
	 *
	 * @throws DBException
	 */
	public function save() {
		$db = UserConfig::getDB();

		$passresetnum = $this->requirespassreset ? 1 : 0;
		$status = $this->status ? 1 : 0;
		$email_verifiednum = $this->is_email_verified ? 1 : 0;

		// creating a copy of the user in case we need to update their email subscription
		// !!!WARNING!!! it's not safe to do anything with this user except reading it's built-in properties
		$old_user = self::getUser($this->getID());

		$username = is_null($this->username) || $this->username == '' ? null : mb_convert_encoding($this->username, 'UTF-8');
		$name = is_null($this->name) || $this->name == '' ? null : mb_convert_encoding($this->name, 'UTF-8');
		$email = is_null($this->email) || $this->email == '' ? null : mb_convert_encoding($this->email, 'UTF-8');

		if ($stmt = $db->prepare('UPDATE u_users SET status = ?, username = ?, name = ?, email = ?, email_verified = ?, requirespassreset = ?, fb_id = ?, fb_link = ? WHERE id = ?')) {
			if (!$stmt->bind_param('isssiiisi', $status, $username, $name, $email, $email_verifiednum, $passresetnum, $this->fbid, $this->fblink, $this->userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		$old_email = $old_user->getEmail();
		$new_email = $this->getEmail();

		if ($old_email != $new_email) {
			$this->sendEmailVerificationCode();
		}

		if (!is_null(UserConfig::$email_module)) {
			// it's up to email module to decide what to do
			UserConfig::$email_module->userChanged($old_user, $this);
		}
	}

	/**
	 * Creates user session cookie
	 *
	 * @param boolean $remember Set to true to remember user beyond browser session (if globally enabled)
	 *
	 * @throws StartupAPIException
	 */
	public function setSession($remember) {
		$storage = new MrClay_CookieStorage(array(
					'secret' => UserConfig::$SESSION_SECRET,
					'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
					'path' => UserConfig::$SITEROOTURL,
					'expire' => UserConfig::$allowRememberMe && $remember ? time() + UserConfig::$rememberMeTime : 0,
					'httponly' => true
				));

		if (!$storage->store(UserConfig::$session_userid_key, $this->userid)) {
			throw new StartupAPIException(implode('; ', $storage->errors));
		}
	}

	/**
	 * Clears user session cookie
	 */
	public static function clearSession() {
		self::stopImpersonation();

		$storage = new MrClay_CookieStorage(array(
					'secret' => UserConfig::$SESSION_SECRET,
					'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
					'path' => UserConfig::$SITEROOTURL
				));

		$storage->delete(UserConfig::$session_userid_key);
	}

	/**
	 * This method turns on impersonation of particular user (instead of just becoming one)
	 *
	 * Should only be used for administrative purpuses.
	 *
	 * @param User $user User to impersonate
	 *
	 * @return User|null New user object with current user associated with it as impersonator
	 *
	 * @throws StartupAPIException
	 */
	public function impersonate($user) {
		if (is_null($user) || $user->isTheSameAs($this)) {
			return null;
		}

		$storage = new MrClay_CookieStorage(array(
					'secret' => UserConfig::$SESSION_SECRET,
					'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
					'path' => UserConfig::$SITEROOTURL,
					'httponly' => true
				));

		if (!$this->isAdmin()) {
			throw new StartupAPIException('Not admin (userid: ' . $this->userid . ') is trying to impersonate another user (userid: ' . $user->userid . ')');
		}

		if (!$storage->store(UserConfig::$impersonation_userid_key, $user->userid)) {
			throw new StartupAPIException(implode('; ', $storage->errors));
		}

		$user->impersonator = $this;

		error_log('[Impersonation log] ' . $this->getName() . ' (User ID: ' . $this->getID() .
				') started impersonating ' . $user->getName() . ' (User ID: ' . $user->getID() . ')');

		return $user;
	}

	/**
	 * Stops impersonation
	 */
	public static function stopImpersonation() {
		$user = self::get();

		$storage = new MrClay_CookieStorage(array(
					'secret' => UserConfig::$SESSION_SECRET,
					'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
					'path' => UserConfig::$SITEROOTURL
				));

		$storage->delete(UserConfig::$impersonation_userid_key);

		if (!is_null($user) && !is_null($user->impersonator)) {
			error_log('[Impersonation log] ' . $user->impersonator->getName() . ' (User ID: ' . $user->impersonator->getID() .
					') stopped impersonating ' . $user->getName() . ' (User ID: ' . $user->getID() . ')');
		}
	}

	/**
	 * Records user activity
	 * @param int $activity_id ID of activity performed by the user
	 *
	 * @throws DBException
	 */
	public function recordActivity($activity_id) {
		if ($this->isImpersonated()) {
			error_log('[Impersonation log] Activity "' . UserConfig::$activities[$activity_id][0] . '" (User ID: ' . $activity_id . ') by ' .
					$this->impersonator->getName() . ' (Activity ID: ' . $this->impersonator->getID() .
					') on behalf of ' . $this->getName() . ' (User ID: ' . $this->getID() . ')');
			return;
		}

		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('INSERT INTO u_activity (user_id, activity_id) VALUES (?, ?)')) {
			if (!$stmt->bind_param('ii', $this->userid, $activity_id)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		if ($stmt = $db->prepare('UPDATE u_users SET points = points + ? WHERE id = ?')) {
			if (!$stmt->bind_param('ii', UserConfig::$activities[$activity_id][1], $this->userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		$this->triggerActivityBadge($activity_id);
	}

	/**
	 * Gives user a badge based on activity
	 *
	 * @param int $activity_id Activity ID
	 */
	public function triggerActivityBadge($activity_id) {
		Badge::triggerActivityBadge($this, $activity_id);
	}

	/**
	 * Returns activity count for one or more activities
	 *
	 * @param int[] $activity_ids Array of activity IDs
	 * @param int $period Number of days in activity window or null if all time
	 *
	 * @return int Number of times any of the activities requested have happened
	 *
	 * @throws DBException
	 */
	public function getActivitiesCount($activity_ids, $period = null) {
		$db = UserConfig::getDB();

		if (!is_array($activity_ids) || count($activity_ids) == 0) {
			return 0;
		}

		$activity_count = 0;

		$in = implode(', ', $activity_ids);

		if ($stmt = $db->prepare('SELECT count(*) as count FROM u_activity
									WHERE user_id = ? AND activity_id IN (' . $in . ')')) {
			if (!$stmt->bind_param('i', $this->userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($count)) {
				throw new DBBindResultException($db, $stmt);
			}

			if ($stmt->fetch() === TRUE) {
				$activity_count = $count;
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $activity_count;
	}

	/**
	 * Returns a list of user's accounts
	 *
	 * @return Account[] Array of Account objects
	 */
	public function getAccounts() {
		return Account::getUserAccounts($this);
	}

	/**
	 * Gets all accounts associated with the user
	 *
	 * @return array Array of Account, role pairs
	 *
	 * @throws DBException
	 * @throws StartupAPIException
	 */
	public function getAccountsAndRoles() {
		return Account::getUserAccountsAndRoles($this);
	}

	/**
	 * Returns user's current account
	 *
	 * @return Account Currently selected account
	 */
	public function getCurrentAccount() {
		return Account::getCurrentAccount($this);
	}

	/**
	 * Returns true if user has the feature enabled
	 *
	 * @param Feature $feature Feature to check
	 *
	 * @return boolean True if feature is enabled for this user
	 */
	public function hasFeature($feature) {
		// checking if we got feature ID instead of object for backwards compatibility
		if (is_int($feature)) {
			$feature = Feature::getByID($feature);
		}

		return $feature->isEnabledForUser($this);
	}

	/**
	 * Explicitly enable features on the list for the user and removes the rest of the features
	 * (account features and global roll-out will still apply)
	 *
	 * @param array $features Array of Feature objects)
	 */
	public function setFeatures($features) {
		$all_features = Feature::getAll();

		foreach ($all_features as $id => $feature) {
			if (in_array($feature, $features)) {
				$feature->enableForUser($this);
			} else {
				$feature->removeForUser($this);
			}
		}
	}

	/**
	 * Returns true if user is the admin of the instance
	 *
	 * @return boolean True if user is system administrator for this installation
	 */
	public function isAdmin() {
		return in_array($this->getID(), UserConfig::$admins);
	}

	/**
	 * Returns true if user is being impersonated by administrator
	 *
	 * @return booluesn True if user is being impersonated by administrator
	 */
	public function isImpersonated() {
		return !is_null($this->impersonator);
	}

	/**
	 * Returns impersonator object (not actual, but a copy to avoid fiddling with real object)
	 *
	 * @return User A copy of impersonator's User object
	 */
	public function getImpersonator() {
		// do not return actual user object
		return clone($this->impersonator);
	}

	/**
	 * Returns a list of badges user earned
	 *
	 * @return array Array of user badges with badge IDs as keys and arrays of Badge object and maximum level as value
	 */
	public function getBadges() {
		return Badge::getUserBadges($this);
	}

	/**
	 * Registers a badge for a user
	 *
	 * @param Badge $badge Badge to Register for a user
	 */
	public function registerBadge(Badge $badge) {
		$badge->registerForUser();
	}

}
