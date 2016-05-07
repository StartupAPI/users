<?php
require_once(__DIR__ . '/facebook.php');

/**
 * Facebook Authentication Module
 *
 * @package StartupAPI
 * @subpackage Authentication\Facebook
 */
class FacebookAuthenticationModule extends AuthenticationModule {

	/**
	 * @var type Stores Facebook SDK object
	 */
	private $sdk;

	/**
	 * @var string Facebook Application ID
	 */
	private $appID;

	/**
	 * @var string Facebook application secret
	 */
	private $secret;

	/**
	 * @var array List of permissions required by application
	 */
	private $permissions;

	/**
	 * @var boolean Remember user between browser sessions
	 */
	private $remember;

	/**
	 * @var boolean Display Facepile social plugin on login/registration forms
	 */
	private $show_facepile = true;

	/**
	 * Instantiates Facebook authentication module and registers it with the system
	 *
	 * @param string $appID Facebook application ID
	 * @param string $secret Facebook application secret (not key)
	 * @param array $permissions Array of additional permissions (e.g. email) full list can be found here: http://developers.facebook.com/docs/authentication/permissions/
	 * @param boolean $remember Remember user between browser sessions?
	 * @param array $options Options array, set "facepile" key to true to enable Facepile
	 */
	public function __construct($appID, $secret, $permissions = array(), $remember = true, $options = null) {
		parent::__construct();

		$this->appID = $appID;
		$this->secret = $secret;
		$this->permissions = $permissions;

		$this->remember = $remember;

		if (is_array($options)) {
			if (array_key_exists('facepile', $options)) {
				$this->show_facepile = $options['facepile'] ? true : false;
			}
		}

		$config = array(
			'appId' => $this->appID,
			'secret' => $this->secret
		);

		$this->sdk = new Facebook($config);
	}

	public function getID() {
		return "facebook";
	}

	public function getLegendColor() {
		return "3b5999";
	}

	public static function getModulesTitle() {
		return "Facebook";
	}

	public static function getModulesDescription() {
		return '<p>Facebook Connect and API access module</p>';
	}

	public function getDescription() {
		return self::getModulesDescription();
	}

	public static function getSignupURL() {
		return 'https://developers.facebook.com/apps';
	}

	public static function getModulesLogo($size = 100) {
		if ($size == 100) {
			return UserConfig::$USERSROOTURL . '/modules/facebook/images/logo_100x.png';
		}
	}

	/**
	 * Returns FacebookUserCredentials object for the user
	 *
	 * @param User $user User object
	 *
	 * @return FacebookUserCredentials|null FacebookUserCredentials object or null if user is not connected to Facebook
	 *
	 * @throws DBException
	 */
	public function getUserCredentials($user) {
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('SELECT fb_id FROM u_users WHERE id = ?')) {
			if (!$stmt->bind_param('i', $userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($fb_id)) {
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();

			if (!is_null($fb_id)) {
				return new FacebookUserCredentials($fb_id);
			}
		} else {
			throw new DBPrepareStmtException($db);
		}

		return null;
	}

	public function getTotalConnectedUsers() {
		$db = UserConfig::getDB();

		$conns = 0;

		if ($stmt = $db->prepare('SELECT count(*) AS conns FROM u_users WHERE fb_id IS NOT NULL')) {
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($conns)) {
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $conns;
	}

	public function getDailyRegistrations() {
		$db = UserConfig::getDB();

		$dailyregs = array();

		if ($stmt = $db->prepare('SELECT CAST(regtime AS DATE) AS regdate, count(*) AS regs FROM u_users WHERE fb_id IS NOT NULL GROUP BY regdate')) {
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

	public function renderLoginForm($template_info, $action) {
		if (is_null($action)) {
			$action = UserConfig::$USERSROOTURL . '/login.php?module=' . $this->getID();
		}

		return $this->renderForm($template_info, $action, 'login');
	}

	public function renderAutoLogoutForm($template_info) {
		$template_info['slug'] = $this->getID();
		$template_info['appID'] = $this->appID;

		return StartupAPI::$template->display("@startupapi/modules/facebook/auto_logout_form.html.twig", $template_info);
	}

	/**
	 * Renders Facebook connect/login/registration forms
	 *
	 * This method is called from renderLoginForm, renderRegistrationForm and renderEditUserForm
	 *
	 * @param string $action Submit URL to be used by the form
	 * @param string $form ID strgin of the form to be rendered
	 */
	private function renderForm($template_info, $action, $form) {
		$template_info['slug'] = $this->getID();
		$template_info['action'] = $action;
		$template_info['form'] = $form;
		$template_info['show_facepile'] = $this->show_facepile;
		$template_info['appID'] = $this->appID;
		$template_info['permissions'] = $this->permissions;
		$template_info['permissions_string'] = implode(',', $this->permissions);

		return StartupAPI::$template->render("@startupapi/modules/facebook/forms.html.twig", $template_info);
	}

	public function renderRegistrationForm($template_info, $full = false, $action = null, $errors = null, $data = null) {
		if (is_null($action)) {
			$action = UserConfig::$USERSROOTURL . '/register.php?module=' . $this->getID();
		}

		return $this->renderForm($template_info, $action, 'register');
	}

	/**
	 * Renders user editing form
	 *
	 * @param array[] $template_info Array of base information for Twig template
	 * @param string $action Form action URL to post back to
	 * @param array $errors Array of error messages to display
	 * @param User $user User object for current user that is being edited
	 * @param array $data Data submitted to the form
	 *
	 * @return string Rendered user ediging form for this module
	 */
	public function renderEditUserForm($template_info, $action, $errors, $user, $data) {
		$fb_id = $user->getFacebookID();

		if (is_null($fb_id)) {
			return $this->renderForm($template_info, $action, 'connect');
		} else {
			try {
				$me = $this->api('/me?fields=id,name,email,link');
			} catch (Exception $e) {
				UserTools::debug("Can't get /me API data: " . $e);
				return;
			}

			$template_info['slug'] = $this->getID();
			$template_info['action'] = $action;
			$template_info['errors'] = $errors;
			$template_info['data'] = $data;
			
			$template_info['me'] = $me;
			$template_info['fb_id'] = $fb_id;

			return StartupAPI::$template->render("@startupapi/modules/facebook/edit_user_form.html.twig", $template_info);
		}
	}

	public function processAutoLogin() {
		UserTools::debug('Automatic login start');

		$remember = false;

		return $this->processLogin(null, $remember, true);
	}

	public function getAutoLogoutURL($return) {
		return UserConfig::$USERSROOTFULLURL . '/modules/facebook/logout.php';
	}

	/**
	 * Processes user login form submission
	 *
	 * Logs user in or registers them if no user with this Facebook connection exists
	 *
	 * @param array $post_data Form data array
	 * @param boolean $remember Whatever or not to remember a user
	 * @param boolean $auto Set to true if this is used in auto-login scenario
	 *
	 * @return User|null User object or null if login didn't succeed
	 *
	 * @throws InputValidationException
	 */
	public function processLogin($post_data, &$remember, $auto = false) {
		UserTools::debug('processLogin start');
		$remember = $this->remember;

		try {
			$fbuser = $this->sdk->getUser();
		} catch (FacebookApiException $e) {
			UserTools::debug("Can't get Facebook user");
			return null;
		}

		UserTools::debug('Facebook user id: ' . $fbuser);

		if ($fbuser == 0) {
			// if we're trying to auto-login, just return null
			if ($auto) {
				return null;
			}

			$errors['fbuserid'][] = 'No Facebook id is passed';
			throw new InputValidationException('No facebook user id', 0, $errors);
		}

		try {
			$permissions = $this->api('/me/permissions');
			UserTools::debug('User permissions: ' . var_export($permissions, true));
			foreach ($this->permissions as $perm) {
				if (!array_key_exists($perm, $permissions['data'][0]) || $permissions['data'][0][$perm] !== 1) {
					// looks like not all required permissions were granted
					UserTools::debug("Can't login - not enough permissions granted");
					return null;
				}
			}
		} catch (Exception $e) {
			UserTools::debug("Can't get FB /me/permissions API data: " . $e);
			return null;
		}

		$user = User::getUserByFacebookID($fbuser);

		if (!is_null($user)) {
			$user->recordActivity(USERBASE_ACTIVITY_LOGIN_FB);
			return $user;
		} else if ($auto) {
			// do not auto-register if auto-logging-in
			UserTools::debug('Auto-logged-in, not registering');
			return null;
		} else {
			return $this->processRegistration($post_data, $remember);
		}
	}

	public function processRegistration($post_data, &$remember) {
		UserTools::debug('processRegistration start');
		$remember = $this->remember;

		try {
			$fbuser = $this->sdk->getUser();
		} catch (FacebookApiException $e) {
			UserTools::debug("Can't get Facebook user");
			return null;
		}

		$errors = array();
		if ($fbuser == 0) {
			$errors['fbuserid'][] = 'No Facebook id is passed';
			throw new InputValidationException('No facebook user id', 0, $errors);
		}

		// checking if user with this Facebook ID already exists and if so, then logs them in
		$existing_user = User::getUserByFacebookID($fbuser);
		if (!is_null($existing_user)) {
			UserTools::debug('processRegistration - existing user: ' . $existing_user->getID());
			$existing_user->recordActivity(USERBASE_ACTIVITY_LOGIN_FB);
			return $existing_user;
		}

		try {
			$me = $this->api('/me?fields=id,name,email,link');
		} catch (Exception $e) {
			UserTools::debug("Can't get /me API data: " . $e);
			return null;
		}

		if (array_key_exists('name', $me)) {
			$name = $me['name'];
		} else {
			$errors['username'][] = "User doesn't have a name";
		}

		// ok, let's create a user
		try {
			$user = User::createNewFacebookUser($name, $fbuser, $me);
		} catch (UserCreationException $e) {
			$errors[$e->getField()][] = $e->getMessage();
		}

		if (count($errors) > 0) {
			throw new ExistingUserException('User already exists', 0, $errors);
		}

		$user->recordActivity(USERBASE_ACTIVITY_REGISTER_FB);
		return $user;
	}

	public function processEditUser($user, $data) {
		if (array_key_exists('remove', $data)) {
			$user->setFacebookID(null);
			$user->save();

			$user->recordActivity(USERBASE_ACTIVITY_REMOVED_FB);

			$this->api('/me/permissions', 'DELETE');

			return true;
		}

		try {
			$fbuser = $this->sdk->getUser();
		} catch (FacebookApiException $e) {
			UserTools::debug("Can't get Facebook user");
			return null;
		}

		$errors = array();
		if ($fbuser == 0) {
			$errors['fbuserid'][] = 'No Facebook id is passed';
			throw new InputValidationException('No facebook user id', 0, $errors);
		}

		if (!is_null(User::getUserByFacebookID($fbuser))) {
			$errors['fbuserid'][] = 'Another user is already associated with your Facebook account.';
		}

		if (count($errors) > 0) {
			throw new InputValidationException('Validation failed', 0, $errors);
		}

		$user->setFacebookID($fbuser);

		// if user has email address and we required it for Facebook connection, let's save it
		if (!$user->getEmail()) {
			try {
				$me = $this->api('/me?fields=email');
			} catch (Exception $e) {
				UserTools::debug("Can't get /me API data: " . $e);
				return null;
			}

			if (array_key_exists('email', $me)) {
				$user->setEmail($me['email']);
			}
		}

		$user->save();

		$user->recordActivity(USERBASE_ACTIVITY_ADDED_FB);

		return true;
	}

	/**
	 * Wrapper to native SDK api call that will recover in case of expired tokens
	 *
	 * @return mixed API call results
	 *
	 * @throws FacebookApiException
	 */
	public function api(/* polymorphic */) {
		$args = func_get_args();

		$result = null;

		try {
			$result = call_user_func_array(array($this->sdk, 'api'), $args);
		} catch (FacebookApiException $fb_ex) {
			$message = $fb_ex->getMessage();

			if (strpos($message, 'An active access token must be used') !== false ||
					strpos($message, 'Session has expired at unix time') !== false
			) {
				UserTools::debug('Facebook access token has expired, redirecting to login');

				// looks like we have a problem with token, let's redirect to login
				$url = $this->sdk->getLoginUrl(array(
					'scope' => $this->permissions
						));
				header('Location: ' . $url);
				exit;
			}

			throw $fb_ex;
		}

		return $result;
	}

	/**
	 * Facebook forms (buttons) have very small footprint
	 *
	 * @return boolean Always returns true
	 */
	public function isCompact() {
		return true;
	}

}

/**
 * User credentials for Facebook users
 *
 * @package StartupAPI
 * @subpackage Authentication\Facebook
 */
class FacebookUserCredentials extends UserCredentials {

	/**
	 * @var int Facebook user id
	 */
	private $fb_id;

	/**
	 * Creates credemtials onject
	 *
	 * @param int $fb_id Facebook user ID
	 */
	public function __construct($fb_id) {
		$this->fb_id = $fb_id;
	}

	/**
	 * Returns Facebook user ID
	 *
	 * @return int Facebook user ID
	 */
	public function getFacebookID() {
		return $this->fb_id;
	}

	public function getHTML() {
		return StartupAPI::$template->render("@startupapi/modules/facebook/credentials.html.twig", array('fb_id' => $this->fb_id));
	}

}
