<?php
include_once(dirname(__FILE__).'/oauth-php/library/OAuthStore.php');
include_once(dirname(__FILE__).'/oauth-php/library/OAuthRequester.php');

abstract class OAuthAuthenticationModule extends AuthenticationModule
{
	protected $serviceName;

	protected $userCredentialsClass;

	// oauth parameters
	protected $oAuthAPIRootURL;
	protected $oAuthConsumerKey;
	protected $oAuthConsumerSecret;
	protected $oAuthAuthorizeURL;
	protected $oAuthRequestTokenURL;
	protected $oAuthAccessTokenURL;
	protected $oAuthSignatureMethods = array();
	protected $oAuthScope;

	// OAuth store instance - using MySQLi store as the rest of the app uses MySQLi
	protected $oAuthStore;

	// Look and feel
	protected $signUpButtonURL;
	protected $logInButtonURL;
	protected $connectButtonURL;

	// Subclasses must assign unique integer IDs
	protected $USERBASE_ACTIVITY_OAUTH_MODULE_LOGIN;
	protected $USERBASE_ACTIVITY_OAUTH_MODULE_ADDED;
	protected $USERBASE_ACTIVITY_OAUTH_MODULE_REMOVED;
	protected $USERBASE_ACTIVITY_OAUTH_MODULE_REGISTER;

	public function __construct($serviceName,
		$oAuthAPIRootURL,
		$oAuthConsumerKey, $oAuthConsumerSecret,
		$oAuthRequestTokenURL, $oAuthAccessTokenURL, $oAuthAuthorizeURL,
		$oAuthSignatureMethods,
		$oAuthScope,
		$signUpButtonURL = null,
		$logInButtonURL = null,
		$connectButtonURL = null,
		$activities = null)
	{
		parent::__construct();

		$this->serviceName = $serviceName;
		$this->oAuthAPIRootURL = $oAuthAPIRootURL;
		$this->oAuthConsumerKey = $oAuthConsumerKey;
		$this->oAuthConsumerSecret= $oAuthConsumerSecret;
		$this->oAuthRequestTokenURL = $oAuthRequestTokenURL;
		$this->oAuthAccessTokenURL = $oAuthAccessTokenURL;
		$this->oAuthAuthorizeURL = $oAuthAuthorizeURL;
		$this->oAuthSignatureMethods = $oAuthSignatureMethods;
		$this->oAuthScope = $oAuthScope;

		$this->signUpButtonURL = $signUpButtonURL;
		$this->logInButtonURL = $logInButtonURL;
		$this->connectButtonURL = $connectButtonURL;

		if (!is_null($activities)) {
			$this->USERBASE_ACTIVITY_OAUTH_MODULE_LOGIN = $activities[0][0];
			$this->USERBASE_ACTIVITY_OAUTH_MODULE_ADDED = $activities[1][0];
			$this->USERBASE_ACTIVITY_OAUTH_MODULE_REMOVED = $activities[2][0];
			$this->USERBASE_ACTIVITY_OAUTH_MODULE_REGISTER = $activities[3][0];

			UserConfig::$activities[$activities[0][0]] = array($activities[0][1], $activities[0][2]);
			UserConfig::$activities[$activities[1][0]] = array($activities[1][1], $activities[1][2]);
			UserConfig::$activities[$activities[2][0]] = array($activities[2][1], $activities[2][2]);
			UserConfig::$activities[$activities[3][0]] = array($activities[3][1], $activities[3][2]);
		}

		$this->oAuthStore = OAuthStore::instance('MySQLi', array(
			'conn' => UserConfig::getDB(),
			'table_prefix' => UserConfig::$mysql_prefix
		));
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

	protected function startOAuthFlow() {
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

			$params = array(
				'oauth_callback' => $callback
			);

			if (!is_null($this->oAuthScope)) {
				$params['scope'] = $this->oAuthScope;
			}

			if (!is_null(UserConfig::$OAuthAppName)) {
				$params['xoauth_displayname'] = UserConfig::$OAuthAppName;
			}

			// STEP 1: get a request token
			$tokenResultParams = OAuthRequester::requestRequestToken(
				$this->oAuthConsumerKey,
				$oauth_user_id,
				$params
			);

			//  redirect to the authorization page, they will redirect back
			header("Location: " . $this->oAuthAuthorizeURL . "?oauth_token=" . $tokenResultParams['token']);
			exit;
		} catch(OAuthException2 $e) {
			error_log(var_export($e, true));
			return null;
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
		<?php if (is_null($this->logInButtonURL)) { ?>
		<input type="submit" name="login" value="Log in using <?php echo UserTools::escape($this->serviceName)?> &gt;&gt;&gt;"/>
		<?php } else { ?>
		<input type="image" name="login" src="<?php echo UserTools::escape($this->logInButtonURL) ?>" value="login"/>
		<?php } ?>
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
		<?php if (is_null($this->signUpButtonURL)) { ?>
		<input type="submit" name="register" value="Register using <?php echo UserTools::escape($this->serviceName)?>&gt;&gt;&gt;"/>
		<?php } else { ?>
		<input type="image" name="register" src="<?php echo UserTools::escape($this->signUpButtonURL) ?>" value="register"/>
		<?php } ?>
		</form>
		<?php
	}

	public function getUserCredentials($user)
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

		if (is_null($serialized_userinfo)) {
			return null;
		}

		return new $this->userCredentialsClass($oauth_user_id, unserialize($serialized_userinfo));
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
			if (is_null($this->connectButtonURL)) {
				?><input type="submit" name="add" value="Connect existing <?php echo $this->getTitle() ?> account &gt;&gt;&gt;"/><?php
			} else {
				?><input type="image" name="add" src="<?php echo UserTools::escape($this->connectButtonURL) ?>" value="add"/><?php
			}
		} else {
			?>
			<div><?php $this->renderUserInfo($serialized_userinfo) ?></div>
			<input type="hidden" name="oauth_user_id" value="<?php echo htmlentities($oauth_user_id) ?>"/>
			<input type="submit" name="remove" value="remove" style="font-size: xx-small"/>
			<?php
		}
		?>
		<input type="hidden" name="save" value="Save &gt;&gt;&gt;"/>
		<?php UserTools::renderCSRFNonce(); ?>
		</form>
		<?php
	}

	protected function renderUserInfo($serialized_userinfo) {
		$user_info = unserialize($serialized_userinfo);
		echo $user_info['name'];
	}

	public function processLogin($data, &$remember)
	{
		$this->startOAuthFlow();
	}

	public function processRegistration($data, &$remember)
	{
		$this->startOAuthFlow();
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

			if (!is_null($this->USERBASE_ACTIVITY_OAUTH_MODULE_REMOVED)) {
				$user->recordActivity($this->USERBASE_ACTIVITY_OAUTH_MODULE_REMOVED);
			}

			return true;
		}

		if (array_key_exists('add', $data)) {
			$this->startOAuthFlow();
		}
	}

	public function recordLoginActivity($user) {
		if (!is_null($this->USERBASE_ACTIVITY_OAUTH_MODULE_LOGIN)) {
			$user->recordActivity($this->USERBASE_ACTIVITY_OAUTH_MODULE_LOGIN);
		}
	}
	public function recordRegistrationActivity($user) {
		if (!is_null($this->USERBASE_ACTIVITY_OAUTH_MODULE_REGISTER)) {
			$user->recordActivity($this->USERBASE_ACTIVITY_OAUTH_MODULE_REGISTER);
		}
	}
	public function recordAddActivity($user) {
		if (!is_null($this->USERBASE_ACTIVITY_OAUTH_MODULE_ADDED)) {
			$user->recordActivity($this->USERBASE_ACTIVITY_OAUTH_MODULE_ADDED);
		}
	}

	public function getTotalConnectedUsers()
	{
		$db = UserConfig::getDB();

		$module_id = $this->getID();

		$conns = 0;

		if ($stmt = $db->prepare('SELECT count(*) AS conns FROM '.UserConfig::$mysql_prefix.'users u LEFT JOIN '.UserConfig::$mysql_prefix.'user_oauth_identity oa ON u.id = oa.user_id WHERE oa.oauth_user_id IS NOT NULL AND oa.module = ?'))
		{
			if (!$stmt->bind_param('s', $module_id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($conns))
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

		return $conns;
	}
}

abstract class OAuthUserCredentials extends UserCredentials {
	// OAuth user id
	protected $oauth_user_id;

	// User info object specific to a subclass
	protected $userinfo;

	public function __construct($oauth_user_id, $userinfo) {
		$this->oauth_user_id = $oauth_user_id;
		$this->userinfo = $userinfo;
	}

	public function getOAuthUserID() {
		return $this->oauth_user_id;
	}

	/**
	 * @return array Array of user-specific information
	 */
	public function getUserInfo() {
		return $this->userinfo;
	}

	/**
	 * This method will most likely be implemented by a subclass using $this->userinfo object
	 *
	 * @return string
	 */
	public function getHTML() {
		return $this->userinfo['name'];
	}

	public function makeOAuthRequest($request, $method = null, $params = null, $body = null, $files = null) {
		$request = new OAuthRequester($request, $method, $params, $body, $files);
		return $request->doRequest($this->oauth_user_id);
	}
}
