<?php
include_once(dirname(__FILE__).'/oauth-php/library/OAuthStore.php');
include_once(dirname(__FILE__).'/oauth-php/library/OAuthRequester.php');

/**
 * Abstract class, implements OAuth flow and data storage for specific modules to subclass
 *
 * @package StartupAPI
 * @subpackage Authentication
 */
abstract class OAuthAuthenticationModule extends AuthenticationModule
{
	/**
	 * @var string Service display name
	 */
	protected $serviceName;

	/**
	 * @var string Name of user credentials class for this module, created and overriden by subclass
	 */
	protected $userCredentialsClass;

	/**
	 * @var OAuthStoreMySQLi OAuth store instance - using MySQLi store as the rest of the app uses MySQLi
	 */
	protected $oAuthStore;

	/**************************************************************************
	 *
	 * OAuth parameters
	 *
	 **************************************************************************/

	/**
	 * @var string Root URL for all API calls using this provider
	 */
	protected $oAuthAPIRootURL;

	/**
	 * @var string OAuth consumer key
	 */
	protected $oAuthConsumerKey;

	/**
	 * @var string OAuth consumer secret
	 */
	protected $oAuthConsumerSecret;

	/**
	 * @var string URL of OAuth endpoint for getting request tokens
	 */
	protected $oAuthRequestTokenURL;

	/**
	 * @var string URL of OAuth endpoint for getting access tokens
	 */
	protected $oAuthAccessTokenURL;

	/**
	 * @var string Authorization/authentication dialog URL to redirect user to
	 */
	protected $oAuthAuthorizeURL;

	/**
	 * @var array Array of signature methods supported by the service, e.g. 'HMAC-SHA1'
	 */
	protected $oAuthSignatureMethods = array();

	/**
	 * @var string Requested permission scopes (zero or more scope strings, usually URLs, separated by spaces)
	 */
	protected $oAuthScope;


	/**************************************************************************
	 *
	 * Look and feel
	 *
	 **************************************************************************/

	/**
	 * @var type Sign-up button image URL
	 */
	protected $signUpButtonURL;

	/**
	 * @var type Login button image URL
	 */
	protected $logInButtonURL;

	/**
	 * @var type Connect button image URL
	 */
	protected $connectButtonURL;


	/**************************************************************************
	 *
	 * Activities. Subclasses must assign unique integer IDs
	 *
	 **************************************************************************/

	/**
	 * @var array Login activity configuration array
	 */
	protected $USERBASE_ACTIVITY_OAUTH_MODULE_LOGIN;

	/**
	 * @var array Connection added activity configuration array
	 */
	protected $USERBASE_ACTIVITY_OAUTH_MODULE_ADDED;

	/**
	 * @var array Connection removed activity configuration array
	 */
	protected $USERBASE_ACTIVITY_OAUTH_MODULE_REMOVED;

	/**
	 * @var array Registration activity configuration array
	 */
	protected $USERBASE_ACTIVITY_OAUTH_MODULE_REGISTER;

	/**
	 * Instantiates a new OAuth-based module and registers it in the system
	 *
	 * @param string $serviceName Service display name
	 * @param string $oAuthAPIRootURL Root URL for all API calls using this provider
	 * @param string $oAuthConsumerKey OAuth consumer key
	 * @param string $oAuthConsumerSecret OAuth consumer secret
	 * @param string $oAuthRequestTokenURL URL of OAuth endpoint for getting request tokens
	 * @param string $oAuthAccessTokenURL URL of OAuth endpoint for getting access tokens
	 * @param string $oAuthAuthorizeURL Authorization/authentication dialog URL to redirect user to
	 * @param array $oAuthSignatureMethods Array of signature methods supported by the service, e.g. 'HMAC-SHA1'
	 * @param string $oAuthScope Requested permission scopes (zero or more scope strings, usually URLs, separated by spaces)
	 * @param string $signUpButtonURL Sign-up button image URL
	 * @param string $logInButtonURL Login button image URL
	 * @param string $connectButtonURL Connect button image URL
	 * @param array $activities Array of user activity configuration entries (see UserConfig::$activities)
	 *
	 * @todo Refactor to move the initialization into separate method and only do
	 *       construction with parameters that end users will most likely have to
	 *       provide in subclasses, e.g. consumer key, secret and scopes
	 */
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

	/**************************************************************************
	 *
	 * Methods related to OAuth handling
	 *
	 **************************************************************************/

	/**
	 * Each module is supposed to implement this service to retrieve identity info from the server.
	 *
	 * Relying on the key only is not enough as some servers might not return same access_token
	 * for the same user. Also, access_tokens can expire or be revoked eventually, but this doesn't
	 * mean that the system user identity has changed.
	 *
	 * @param int $oauth_user_id OAuth user id to get identity for
	 *
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
	 * Starts OAuth flow
	 *
	 * @throws StartupAPIException
	 */
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
			throw new StartupAPIException(implode('; ', $storage->errors));
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

			$authorize_url = $this->getAuthorizeURL($tokenResultParams);

			//  redirect to the authorization page, they will redirect back
			header("Location: " . $authorize_url . "?oauth_token=" . rawurlencode($tokenResultParams['token']));
			exit;
		} catch(OAuthException2 $e) {
			error_log(var_export($e, true));
		}
	}

	/**
	 * By default returns pre-defined authorization URL, but subclasses can override
	 * this function to get authorization URL from token parameters passed back.
	 *
	 * @param array $tokenResultParams array of OAuth token information passed in by provider
	 *
	 * @return string Returns OAuth Authorization URL
	 */
	protected function getAuthorizeURL($tokenResultParams) {
		return $this->oAuthAuthorizeURL;
	}

	/**
	 * When we don't know current user, we need to create a new OAuth User ID to use for new connection.
	 * If we know the user when OAuth comes through, we'll replace current OAuth User ID with the new one.
	 *
	 * @return string New/temporary OAuth user ID
	 *
	 * @throws DBException
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
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			$oauth_user_id = $stmt->insert_id;

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return $oauth_user_id;
	}

	/**
	 * Adds user identity to OAuth user
	 *
	 * @param User $user User who this OAuth credentials belong
	 * @param array $identity Identity array provided for this OAuth user
	 * @param int $oauth_user_id OAuth user id
	 *
	 * @throws DBException
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
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}
	}

	/**
	 * Deletes OAuth user credentials
	 *
	 * @param int $oauth_user_id OAuth user ID
	 *
	 * @throws DBException
	 */
	public function deleteOAuthUser($oauth_user_id) {
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('DELETE FROM '.UserConfig::$mysql_prefix.'user_oauth_identity WHERE oauth_user_id = ?'))
		{
			if (!$stmt->bind_param('i', $oauth_user_id))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}
	}

	/**
	 * Returns a user for OAuth credentials
	 *
	 * Gets StartupAPI user by server identity and resets user_id -> oauth_user_id if necessary
	 *
	 * @param array $identity Identity array provided for this OAuth user
	 * @param int $oauth_user_id OAuth user id
	 *
	 * @return User|null User object or null if these credentials are not connected to any user
	 *
	 * @throws DBException
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
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($old_oauth_user_id, $user_id))
			{
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
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
					throw new DBBindParamException($db, $stmt);
				}
				if (!$stmt->execute())
				{
					throw new DBExecuteStmtException($db, $stmt);
				}

				$stmt->close();
			}
			else
			{
				throw new DBPrepareStmtException($db);
			}
		}

		return User::getUser($user_id);
	}

	/**
	 * Retrieves OAuth access token from the service
	 *
	 * @param int $oauth_user_id OAuth user ID
	 */
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


	/**************************************************************************
	 *
	 * Methods related to Startup API mechanics
	 *
	 **************************************************************************/

	/**
	 * Renders login form HTML with a single button
	 *
	 * Uses self::$loginButtonURL for a button image if supplied
	 *
	 * @param string $action Action URL the form should submit data to
	 */
	public function renderLoginForm($action)
	{
		?>
		<p>Sign in using your existing account with <b><?php echo UserTools::escape($this->serviceName)?></b>.</p>
		<form action="<?php echo $action?>" method="POST">
		<input type="hidden" name="login" value="login"/>
		<?php if (is_null($this->logInButtonURL)) { ?>
		<input type="submit" value="Log in using <?php echo UserTools::escape($this->serviceName)?>"/>
		<?php } else { ?>
		<input type="image" src="<?php echo UserTools::escape($this->logInButtonURL) ?>" value="login"/>
		<?php } ?>
		</form>
		<?php
	}

	/**
	 * Renders registration form HTML with a single button
	 *
	 * Uses self::$signUpButtonURL for a button image if supplied
	 *
	 * @param boolean $full Whatever or not to display a short version of the form or full
	 * @param string $action Action URL the form should submit data to
	 * @param array $errors An array of error messages to be displayed to the user on error
	 * @param array $data An array of data passed by a form on previous submission to display back to user
	 */
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
		<input type="hidden" name="register" value="register"/>
		<?php if (is_null($this->signUpButtonURL)) { ?>
		<input type="submit" value="Register using <?php echo UserTools::escape($this->serviceName)?>"/>
		<?php } else { ?>
		<input type="image" src="<?php echo UserTools::escape($this->signUpButtonURL) ?>"/>
		<?php } ?>
		</form>
		<?php
	}

	/**
	 * Renders user editing form
	 *
	 * Uses self::$connectButtonURL for a button image if supplied
	 *
	 * @param string $action Form action URL to post back to
	 * @param array $errors Array of error messages to display
	 * @param User $user User object for current user that is being edited
	 * @param array $data Data submitted to the form
	 *
	 * @throws DBException
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
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($oauth_user_id, $serialized_userinfo))
			{
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		?>
		<form action="<?php echo $action?>" method="POST">
		<?php
		if (is_null($oauth_user_id)) {
			if (is_null($this->connectButtonURL)) {
				?><input type="submit" value="Connect existing <?php echo $this->getTitle() ?> account"/><?php
			} else {
				?><input type="image" src="<?php echo UserTools::escape($this->connectButtonURL) ?>"/><?php
			}
			?><input type="hidden" name="add" value="add"/><?php
		} else {
			?>
			<div><?php $this->renderUserInfo($serialized_userinfo) ?></div>
			<input type="hidden" name="oauth_user_id" value="<?php echo htmlentities($oauth_user_id) ?>"/>
			<input type="submit" name="remove" value="remove" style="font-size: xx-small"/>
			<?php
		}
		?>
		<input type="hidden" name="save" value="Save"/>
		<?php UserTools::renderCSRFNonce(); ?>
		</form>
		<?php
	}

	/**
	 * Returns user credentials
	 *
	 * Creates a new object of self::$userCredentialsClass class and returns it
	 *
	 * @param User $user User to get credentials for
	 *
	 * @return OAuthUserCredentials User credentials object specific to the module
	 *
	 * @throws DBException
	 */
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
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($oauth_user_id, $serialized_userinfo))
			{
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		if (is_null($serialized_userinfo)) {
			return null;
		}

		return new $this->userCredentialsClass($oauth_user_id, unserialize($serialized_userinfo));
	}

	/**
	 * Renders user information as HTML
	 *
	 * Subclasses can override to add links to user profiles on destination sites, userpics and etc.
	 *
	 * @param array $serialized_userinfo Array of user info parameters from provider with "id" and "name" required
	 */
	protected function renderUserInfo($serialized_userinfo) {
		$user_info = unserialize($serialized_userinfo);
		echo $user_info['name'];
	}

	/**
	 * Called when login form is submitted
	 *
	 * This method never returns user information, it redirects to OAuth flow
	 * ending on oauth_callback.php which implements the logic instead of login.php
	 *
	 * @param array $data Form data
	 * @param boolean $remember whatever or not to remember the user
	 *
	 * @return null This method never returns user information
	 *
	 * @todo Figure out if we need $remember parameter at all for these modules
	 */
	public function processLogin($data, &$remember)
	{
		$this->startOAuthFlow();

		return null; // it never reaches this point
	}

	/**
	 * Called when registration form is submitted
	 *
	 * This method never returns user information, it redirects to OAuth flow
	 * ending on oauth_callback.php which implements the logic instead of register.php
	 *
	 * @param array $data Form data
	 * @param boolean $remember whatever or not to remember the user
	 *
	 * @return null This method never returns user information
	 *
	 * @todo Figure out if we need $remember parameter at all for these modules
	 */
	public function processRegistration($data, &$remember)
	{
		$this->startOAuthFlow();

		return null; // it never reaches this point
	}

	/**
	 * Updates user connection information
	 *
	 * @param User $user User object
	 * @param array $data Form data
	 *
	 * @return boolean True if successful and false if unsuccessful
	 * @throws DBException
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
					throw new DBBindParamException($db, $stmt);
				}
				if (!$stmt->execute())
				{
					throw new DBExecuteStmtException($db, $stmt);
				}

				$stmt->close();
			}
			else
			{
				throw new DBPrepareStmtException($db);
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

	/**
	 * Records login activity for a user
	 *
	 * @param User $user User object
	 */
	public function recordLoginActivity($user) {
		if (!is_null($this->USERBASE_ACTIVITY_OAUTH_MODULE_LOGIN)) {
			$user->recordActivity($this->USERBASE_ACTIVITY_OAUTH_MODULE_LOGIN);
		}
	}

	/**
	 * Records registration activity for a user
	 *
	 * @param User $user User object
	 */
	public function recordRegistrationActivity($user) {
		if (!is_null($this->USERBASE_ACTIVITY_OAUTH_MODULE_REGISTER)) {
			$user->recordActivity($this->USERBASE_ACTIVITY_OAUTH_MODULE_REGISTER);
		}
	}

	/**
	 * Records connection added activity for a user
	 *
	 * @param User $user User object
	 */
	public function recordAddActivity($user) {
		if (!is_null($this->USERBASE_ACTIVITY_OAUTH_MODULE_ADDED)) {
			$user->recordActivity($this->USERBASE_ACTIVITY_OAUTH_MODULE_ADDED);
		}
	}

	/**
	 * Returns totsal number of users connected using the module
	 *
	 * @return int Number of users connected using the module
	 *
	 * @throws DBException
	 */
	public function getTotalConnectedUsers()
	{
		$db = UserConfig::getDB();

		$module_id = $this->getID();

		$conns = 0;

		if ($stmt = $db->prepare('SELECT count(*) AS conns FROM '.UserConfig::$mysql_prefix.'users u LEFT JOIN '.UserConfig::$mysql_prefix.'user_oauth_identity oa ON u.id = oa.user_id WHERE oa.oauth_user_id IS NOT NULL AND oa.module = ?'))
		{
			if (!$stmt->bind_param('s', $module_id))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($conns))
			{
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return $conns;
	}
}

/**
 * Abstract class representing user credentials for making OAuth API calls
 *
 * Should be extended by subclasses to implement additional features and customize
 * formatting for user information, e.g. add links to user profiles, userpics and so on.
 *
 * @package StartupAPI
 * @subpackage Authentication
 */
abstract class OAuthUserCredentials extends UserCredentials {
	/**
	 * @var int OAuth user id
	 */
	protected $oauth_user_id;

	/**
	 * @var array User info object specific to a subclass
	 */
	protected $userinfo;

	/**
	 * Creates new OAuth credentials
	 *
	 * @param int $oauth_user_id OAuth user ID
	 * @param array $userinfo User info array as retrieved from provider
	 */
	public function __construct($oauth_user_id, $userinfo) {
		$this->oauth_user_id = $oauth_user_id;
		$this->userinfo = $userinfo;
	}

	/**
	 * Returns OAuth user ID
	 *
	 * @return string OAuth user ID
	 */
	public function getOAuthUserID() {
		return $this->oauth_user_id;
	}

	/**
	 * Returns an array of user information key-value pairs
	 *
	 * @return array Array of user-specific information
	 */
	public function getUserInfo() {
		return $this->userinfo;
	}

	/**
	 * Returns a chunk of HTML to display user's credentials
	 *
	 * This method will most likely be implemented by a subclass using $this->userinfo object.
	 * For some providers it can be returning a code to include a JavaScript widget.
	 *
	 * @return string HTML to display user information
	 */
	public function getHTML() {
		return $this->userinfo['name'];
	}

	/**
	 * Makes HTTP request with OAuth authentication
	 *
	 * This method allows requesting information on behalf of the user from a 3rd party provider.
	 * Possibly the most important feature of the whole system.
	 *
	 * @param string $request Request URL
	 * @param string $method HTTP method (e.g. GET, POST, PUT, etc)
	 * @param array $params Request parameters key->value array
	 * @param string $body Request body
	 * @param array $files Array of file names (currently supports 1 max untilmultipart/form-data is supported)
	 *
	 * @return array Response data (code=>int, headers=>array(), body=>string)
	 */
	public function makeOAuthRequest($request, $method = null, $params = null, $body = null, $files = null) {
		$request = new OAuthRequester($request, $method, $params, $body, $files);
		return $request->doRequest($this->oauth_user_id);
	}
}
