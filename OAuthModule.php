<?php
include_once(dirname(__FILE__).'/oauth-php/library/OAuthStore.php');
include_once(dirname(__FILE__).'/oauth-php/library/OAuthRequester.php');

abstract class OAuthAuthenticationModule implements IAuthenticationModule
{
	protected $serviceName;

	// oauth parameters
	protected $oAuthAPIRootURL;
	protected $oAuthConsumerKey;
	protected $oAuthConsumerSecret;
	protected $oAuthAuthorizeURL;
	protected $oAuthRequestTokenURL;
	protected $oAuthAccessTokenURL;
	protected $oAuthSignatureMethods = array();

	// OAuth store instance - using MySQLi store as the rest of the app uses MySQLi
	protected $oAuthStore;

	// support for immediate mode (when server redirects back if user is already connected)
	protected $supportsImmediate = false;

	protected $remember;

	public function __construct($serviceName,
		$oAuthAPIRootURL,
		$oAuthConsumerKey, $oAuthConsumerSecret,
		$oAuthRequestTokenURL, $oAuthAccessTokenURL, $oAuthAuthorizeURL,
		$oAuthSignatureMethods,
		$remember = true)
	{
		$this->serviceName = $serviceName;
		$this->oAuthAPIRootURL = $oAuthAPIRootURL;
		$this->oAuthConsumerKey = $oAuthConsumerKey;
		$this->oAuthConsumerSecret= $oAuthConsumerSecret;
		$this->oAuthRequestTokenURL = $oAuthRequestTokenURL;
		$this->oAuthAccessTokenURL = $oAuthAccessTokenURL;
		$this->oAuthAuthorizeURL = $oAuthAuthorizeURL;
		$this->oAuthSignatureMethods = $oAuthSignatureMethods;

		$this->oAuthStore = OAuthStore::instance('MySQLi', array(
			'conn' => UserConfig::getDB(),
			'table_prefix' => UserConfig::$mysql_prefix
		));

		$this->remember = $remember;
	}

###########################################################################################
# Methods related to OAuth handling
###########################################################################################

	/**
	 * Each module is supposed to implement this service to retrieve identity info from the server.
	 *
	 * Relying on the key only is not enough as some servers might not return same access_token
	 * for the same user. Also, access_tokens can expire or be revoked eventually, but this doesn't
	 * mean that the system user identity has changed.
	 *
	 * @param array $oauth_user_id OAuth user id to get identity for
	 * @return array $identity Identity array that includes user info including 'id' column which
	 *                         uniquely identifies the user on server and 'name' value that can be
	 *                         used as user's name upon registration
	 */
	abstract public function getIdentity($oauth_user_id);

	/**
	 * Initializes the server entry in the database if it wasn't initialized beforehand
	 */
	protected function initOAuthServer() {
		// check if server is already registered in our database, otherwise, create the entry
		try {
			$this->oAuthStore->getServerForUri($this->oAuthAPIRootURL, null);
		} catch (OAuthException2 $e) {
			$this->oAuthStore->updateServer(array(
				'server_uri' => $this->oAuthAPIRootURL,
				'consumer_key' => $this->oAuthConsumerKey,
				'consumer_secret' => $this->oAuthConsumerSecret,
				'authorize_uri' => $this->oAuthAuthorizeURL,
				'request_token_uri' => $this->oAuthRequestTokenURL,
				'access_token_uri' => $this->oAuthAccessTokenURL,
				'signature_methods' => $this->oAuthSignatureMethods,
				'user_id' => null
			), null, true);
		}
	}

	/**
	 * When we don't know current user, we need to create a new OAuth User ID to use for new connection.
	 * If we know the user when OAuth comes through, we'll replace current OAuth User ID with the new one.
	 */
	protected function getNewOAuthUserID() {
		// TODO add a way to skip this step if server was initialized
		$this->initOAuthServer();

		$db = UserConfig::getDB();

		$module = $this->getID();

		if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'user_oauth_identity (module) VALUES (?)'))
		{
			if (!$stmt->bind_param('s', $module))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			$oauth_user_id = $stmt->insert_id;

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $oauth_user_id;
	}

	/**
	 *
	 */
	public function addUserOAuthIdentity($user, $identity, $oauth_user_id) {
		$db = UserConfig::getDB();

		$user_id = $user->getID();
		$old_oauth_user_id = null;

		$server_unique_id = $identity['id'];
		$serialized_userinfo = serialize($identity);

		$module = $this->getID();

		// updating new recently created entry
		if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'user_oauth_identity SET user_id = ?, identity = ?, userinfo = ? WHERE oauth_user_id = ? AND module = ?'))
		{
			if (!$stmt->bind_param('issis', $user_id, $server_unique_id, $serialized_userinfo, $oauth_user_id, $module))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}
	}

	public function deleteOAuthUser($oauth_user_id) {
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('DELETE FROM '.UserConfig::$mysql_prefix.'user_oauth_identity WHERE oauth_user_id = ?'))
		{
			if (!$stmt->bind_param('i', $oauth_user_id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}
	}

	/**
	 * Get UserBase user by server identity and reset user_id -> oauth_user_id if necessary
	 */
	public function getUserByOAuthIdentity($identity, $oauth_user_id) {
		$db = UserConfig::getDB();

		$user_id = null;
		$old_oauth_user_id = null;

		$server_unique_id = $identity['id'];

		$module = $this->getID();

		if ($stmt = $db->prepare('SELECT oauth_user_id, user_id FROM '.UserConfig::$mysql_prefix.'user_oauth_identity WHERE module = ? AND identity = ?'))
		{
			if (!$stmt->bind_param('ss', $module, $server_unique_id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($old_oauth_user_id, $user_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		// nobody registered with this identity yet
		if (is_null($user_id)) {
			return null;
		}

		if ($old_oauth_user_id != $oauth_user_id) {
			// let's re-map from old oauth_user_id to new one
			// deleting old one first
			$this->deleteOAuthUser($old_oauth_user_id);

			$serialized_userinfo = serialize($identity);

			// updating new recently created entry
			if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'user_oauth_identity SET user_id = ?, identity = ?, userinfo = ? WHERE oauth_user_id = ?'))
			{
				if (!$stmt->bind_param('issi', $user_id, $server_unique_id, $serialized_userinfo, $oauth_user_id))
				{
					 throw new Exception("Can't bind parameter".$stmt->error);
				}
				if (!$stmt->execute())
				{
					throw new Exception("Can't execute statement: ".$stmt->error);
				}

				$stmt->close();
			}
			else
			{
				throw new Exception("Can't prepare statement: ".$db->error);
			}
		}

		return User::getUser($user_id);
	}

	public function getAccessToken($oauth_user_id) {
		//  STEP 2:  Get an access token
		$oauthToken = $_GET["oauth_token"];

		// echo "oauth_verifier = '" . $oauthVerifier . "'<br/>";
		$tokenResultParams = $_GET;

		OAuthRequester::requestAccessToken(
			$this->oAuthConsumerKey,
			$oauthToken,
			$oauth_user_id,
			'POST',
			$_GET
		);
	}

###########################################################################################
# Methods related to UserBase mechanics
###########################################################################################
	public function renderLoginForm($action)
	{
		?>
		<p>Sign in using your existing account with <b><?php echo UserTools::escape($this->serviceName)?></b>.</p>
		<form action="<?php echo $action?>" method="POST">
		<input type="submit" name="login" value="Log in using <?php echo UserTools::escape($this->serviceName)?> &gt;&gt;&gt;"/>
		</form>
		<?php
	}

	public function renderRegistrationForm($full = false, $action = null, $errors = null , $data = null)
	{
		if (is_null($action))
		{
			$action = UserConfig::$USERSROOTURL.'/register.php?module='.$this->getID();
		}

		if ($full)
		{
		?>
			<p>Sign in using your existing account with <b><?php echo UserTools::escape($this->serviceName)?></b>.</p>
		<?php
		}
		?>
		<form action="<?php echo $action?>" method="POST">
		<input type="submit" name="register" value="Register using <?php echo UserTools::escape($this->serviceName)?>&gt;&gt;&gt;"/>
		</form>
		<?php
	}

	/*
	 * Renders user editing form
	 *
	 * Parameters:
	 * $action - form action to post back to
	 * $errors - error messages to display
	 * $user - user object for current user that is being edited
	 * $data - data submitted to the form
	 */
	public function renderEditUserForm($action, $errors, $user, $data)
	{
		$db = UserConfig::getDB();

		$user_id = $user->getID();
		$module = $this->getID();

		$oauth_user_id = null;
		$serialized_userinfo = null;

		if ($stmt = $db->prepare('SELECT oauth_user_id, userinfo FROM '.UserConfig::$mysql_prefix.'user_oauth_identity WHERE user_id = ? AND module = ?'))
		{
			if (!$stmt->bind_param('is', $user_id, $module))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($oauth_user_id, $serialized_userinfo))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		?>
		<form action="<?php echo $action?>" method="POST">
		<?php
		if (is_null($oauth_user_id)) {
			?><input type="submit" name="add" value="Connect existing <?php echo $this->getTitle() ?> account &gt;&gt;&gt;"/><?php
		} else {
			?>
			<div><?php $this->renderUserInfo($serialized_userinfo) ?></div>
			<input type="hidden" name="oauth_user_id" value="<?php echo htmlentities($oauth_user_id) ?>"/>
			<input type="submit" name="remove" value="remove" style="font-size: xx-small"/>
			<?php
		}
		?>
		<input type="hidden" name="save" value="Save &gt;&gt;&gt;"/>
		</form>
		<?php
	}

	protected function renderUserInfo($serialized_userinfo) {
		$user_info = unserialize($serialized_userinfo);
		echo $user_info['id'];
	}

	public function processLogin($data, &$remember)
	{
		// generate new user id since we're logging in and have no idea who the user is
		$oauth_user_id = $this->getNewOAuthUserID();

		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
			'path' => UserConfig::$SITEROOTURL,
			'httponly' => true
		));

		if (!$storage->store(UserConfig::$oauth_user_id_key, $oauth_user_id)) {
			throw new Exception(implode('; ', $storage->errors));
		}

		try
		{
			$callback = UserConfig::$USERSROOTFULLURL.'/oauth_callback.php?module='.$this->getID();

			// TODO add a way to skip this step if server was initialized
			$this->initOAuthServer();

			// STEP 1: get a request token
			$tokenResultParams = OAuthRequester::requestRequestToken(
				$this->oAuthConsumerKey,
				$oauth_user_id,
				array(
					'scope' => $this->oAuthAPIRootURL,
					'xoauth_displayname' => UserConfig::$appName,
					'oauth_callback' => $callback
				)
			);

			//  redirect to the authorization page, they will redirect back
			header("Location: " . $this->oAuthAuthorizeURL . "?oauth_token=" . $tokenResultParams['token']);
			exit;
		} catch(OAuthException2 $e) {
			error_log(var_export($e, true));
			return null;
		}
	}

	public function processRegistration($data, &$remember)
	{
		// generate new user id since we're logging in and have no idea who the user is
		$oauth_user_id = $this->getNewOAuthUserID();

		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
			'path' => UserConfig::$SITEROOTURL,
			'httponly' => true
		));

		if (!$storage->store(UserConfig::$oauth_user_id_key, $oauth_user_id)) {
			throw new Exception(implode('; ', $storage->errors));
		}

		try
		{
			$callback = UserConfig::$USERSROOTFULLURL.'/oauth_callback.php?module='.$this->getID();

			// TODO add a way to skip this step if server was initialized
			$this->initOAuthServer();

			// STEP 1: get a request token
			$tokenResultParams = OAuthRequester::requestRequestToken(
				$this->oAuthConsumerKey,
				$oauth_user_id,
				array(
					'scope' => $this->oAuthAPIRootURL,
					'xoauth_displayname' => UserConfig::$appName,
					'oauth_callback' => $callback
				)
			);

			//  redirect to the authorization page, they will redirect back
			header("Location: " . $this->oAuthAuthorizeURL . "?oauth_token=" . $tokenResultParams['token']);
			exit;
		} catch(OAuthException2 $e) {
			error_log(var_export($e, true));
			return null;
		}
	}

	/*
	 * Updates user information
	 *
	 * returns true if successful and false if unsuccessful
	 *
	 * throws InputValidationException if there are problems with input data
	 */
	public function processEditUser($user, $data)
	{
		if (array_key_exists('remove', $data) && array_key_exists('oauth_user_id', $data)) {
			$db = UserConfig::getDB();

			$oauth_user_id = $data['oauth_user_id'];
			$user_id = $user->getID();

			if ($stmt = $db->prepare('DELETE FROM '.UserConfig::$mysql_prefix.'user_oauth_identity WHERE oauth_user_id = ? AND user_id = ?'))
			{
				if (!$stmt->bind_param('ii', $oauth_user_id, $user_id))
				{
					 throw new Exception("Can't bind parameter".$stmt->error);
				}
				if (!$stmt->execute())
				{
					throw new Exception("Can't execute statement: ".$stmt->error);
				}

				$stmt->close();
			}
			else
			{
				throw new Exception("Can't prepare statement: ".$db->error);
			}

			return true;
		}

		if (array_key_exists('add', $data)) {
			// generate new user id since we're logging in and have no idea who the user is
			$oauth_user_id = $this->getNewOAuthUserID();

			$storage = new MrClay_CookieStorage(array(
				'secret' => UserConfig::$SESSION_SECRET,
				'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
				'path' => UserConfig::$SITEROOTURL,
				'httponly' => true
			));

			if (!$storage->store(UserConfig::$oauth_user_id_key, $oauth_user_id)) {
				throw new Exception(implode('; ', $storage->errors));
			}

			try
			{
				$callback = UserConfig::$USERSROOTFULLURL.'/oauth_callback.php?module='.$this->getID();

				// TODO add a way to skip this step if server was initialized
				$this->initOAuthServer();

				// STEP 1: get a request token
				$tokenResultParams = OAuthRequester::requestRequestToken(
					$this->oAuthConsumerKey,
					$oauth_user_id,
					array(
						'scope' => $this->oAuthAPIRootURL,
						'xoauth_displayname' => UserConfig::$appName,
						'oauth_callback' => $callback
					)
				);

				//  redirect to the authorization page, they will redirect back
				header("Location: " . $this->oAuthAuthorizeURL . "?oauth_token=" . $tokenResultParams['token']);
				exit;
			} catch(OAuthException2 $e) {
				error_log(var_export($e, true));
				return null;
			}
		}
	}

	/*
	 * retrieves recent aggregated registrations numbers
	 */
	public function getRecentRegistrations()
	{
# TODO Implement getting recent registrations

#		$db = UserConfig::getDB();
#
#		$regs = 0;
#
#		if ($stmt = $db->prepare('SELECT count(*) AS reqs FROM (SELECT u.id FROM '.UserConfig::$mysql_prefix.'users u LEFT JOIN '.UserConfig::$mysql_prefix.'googlefriendconnect g ON u.id = g.user_id WHERE regtime > DATE_SUB(NOW(), INTERVAL 30 DAY) AND g.google_id IS NOT NULL GROUP BY id) AS agg'))
#		{
#			if (!$stmt->execute())
#			{
#				throw new Exception("Can't execute statement: ".$stmt->error);
#			}
#			if (!$stmt->bind_result($regs))
#			{
#				throw new Exception("Can't bind result: ".$stmt->error);
#			}
#
#			$stmt->fetch();
#			$stmt->close();
#		}
#		else
#		{
#			throw new Exception("Can't prepare statement: ".$db->error);
#		}
#
#		return $regs;
	}

	/*
	 * retrieves aggregated registrations numbers
	 */
	public function getDailyRegistrations()
	{
# TODO Implement getting registrations

#		$db = UserConfig::getDB();
#
#		$dailyregs = array();
#
#		if ($stmt = $db->prepare('SELECT regdate, count(*) AS reqs FROM (SELECT CAST(regtime AS DATE) AS regdate, id AS regs FROM '.UserConfig::$mysql_prefix.'users u LEFT JOIN '.UserConfig::$mysql_prefix.'googlefriendconnect g ON u.id = g.user_id WHERE g.google_id IS NOT NULL GROUP BY id) agg group by agg.regdate'))
#		{
#			if (!$stmt->execute())
#			{
#				throw new Exception("Can't execute statement: ".$stmt->error);
#			}
#			if (!$stmt->bind_result($regdate, $regs))
#			{
#				throw new Exception("Can't bind result: ".$stmt->error);
#			}
#
#			while($stmt->fetch() === TRUE)
#			{
#				$dailyregs[] = array('regdate' => $regdate, 'regs' => $regs);
#			}
#
#			$stmt->close();
#		}
#		else
#		{
#			throw new Exception("Can't prepare statement: ".$db->error);
#		}
#
#		return $dailyregs;
	}
}
