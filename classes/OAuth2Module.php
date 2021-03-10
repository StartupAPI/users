<?php
/**
 * Abstract class, implements OAuth2 flow and data storage for specific modules to subclass
 *
 * @package StartupAPI
 * @subpackage Authentication\OAuth2
 */
abstract class OAuth2AuthenticationModule extends AuthenticationModule
{
	/**
	 * @var string Service display name
	 */
	protected $serviceName;

	/**
	 * @var string Name of user credentials class for this module, created and overriden by subclass
	 */
	protected $userCredentialsClass;

	/**************************************************************************
	 *
	 * OAuth2 parameters
	 *
	 **************************************************************************/

	/**
	 * @var string Root URL for all API calls using this provider
	 */
	protected $oAuth2APIRootURL;

	/**
	 * @var string OAuth2 client ID
	 */
	protected $oAuth2ClientID;

	/**
	 * @var string OAuth2 client secret
	 */
	protected $oAuth2ClientSecret;

	/**
	 * @var string Authorization/authentication dialog URL to redirect user to
	 */
	protected $oAuth2LoginLink;

	/**
	 * @var string URL of OAuth2 endpoint for getting access tokens
	 */
	protected $oAuth2AccessTokenURL;

	/**
	 * @var string Requested permission scopes (zero or more scope strings, usually URLs, separated by spaces)
	 */
	protected $oAuth2Scope;

	/**
	 * @var string URL parameter to use for access_token, can be different in some implementations
	 */
	protected $oAuth2AccessTokenParamName = 'access_token';

	/**
	 * @var string Extra parameters array, implementations can send additional parameters required by server
	 */
	protected $oAuth2ExtraParameters = array();

	/**
	 * @var boolean Sets application/x-www-form-urlencoded for access token request, otherwise uses multipart/form-data
	 */
	protected $oAuth2AccessTokenRequestFormURLencoded = FALSE;

	/**
	 * @var boolean Send access token as HTTP header instead of query string parameter
	 */
	protected $oAuth2SendAccessTokenAsHeader = FALSE;

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
	protected $USERBASE_ACTIVITY_OAUTH2_MODULE_LOGIN;

	/**
	 * @var array Connection added activity configuration array
	 */
	protected $USERBASE_ACTIVITY_OAUTH2_MODULE_ADDED;

	/**
	 * @var array Connection removed activity configuration array
	 */
	protected $USERBASE_ACTIVITY_OAUTH2_MODULE_REMOVED;

	/**
	 * @var array Registration activity configuration array
	 */
	protected $USERBASE_ACTIVITY_OAUTH2_MODULE_REGISTER;

	/**
	 * Instantiates a new OAuth2-based module and registers it in the system
	 *
	 * @param string $serviceName Service display name
	 * @param string $oAuth2APIRootURL Root URL for all API calls using this provider
	 * @param string $oAuth2ClientID OAuth2 consumer key
	 * @param string $oAuth2ClientSecret OAuth2 consumer secret
	 * @param string $oAuth2LoginLink Authorization/authentication dialog URL to redirect user to
	 * @param string $oAuth2AccessTokenURL URL of OAuth2 endpoint for getting access tokens
	 * @param string $oAuth2Scope Requested permission scopes (zero or more scope strings, usually URLs, separated by spaces)
	 * @param string $signUpButtonURL Sign-up button image URL
	 * @param string $logInButtonURL Login button image URL
	 * @param string $connectButtonURL Connect button image URL
	 * @param array $activities Array of user activity configuration entries (see UserConfig::$activities)
	 */
	public function __construct($serviceName,
		$oAuth2APIRootURL,
		$oAuth2ClientID, $oAuth2ClientSecret,
		$oAuth2LoginLink, $oAuth2AccessTokenURL,
		$oAuth2Scope,
		$signUpButtonURL = null,
		$logInButtonURL = null,
		$connectButtonURL = null,
		$activities = null)
	{
		parent::__construct();

		$this->serviceName = $serviceName;
		$this->oAuth2APIRootURL = $oAuth2APIRootURL;
		$this->oAuth2ClientID = $oAuth2ClientID;
		$this->oAuth2ClientSecret = $oAuth2ClientSecret;
		$this->oAuth2LoginLink = $oAuth2LoginLink;
		$this->oAuth2AccessTokenURL = $oAuth2AccessTokenURL;
		$this->oAuth2Scope = $oAuth2Scope;

		$this->signUpButtonURL = $signUpButtonURL;
		$this->logInButtonURL = $logInButtonURL;
		$this->connectButtonURL = $connectButtonURL;

		if (!is_null($activities)) {
			$this->USERBASE_ACTIVITY_OAUTH2_MODULE_LOGIN = $activities[0][0];
			$this->USERBASE_ACTIVITY_OAUTH2_MODULE_ADDED = $activities[1][0];
			$this->USERBASE_ACTIVITY_OAUTH2_MODULE_REMOVED = $activities[2][0];
			$this->USERBASE_ACTIVITY_OAUTH2_MODULE_REGISTER = $activities[3][0];

			UserConfig::$activities[$activities[0][0]] = array($activities[0][1], $activities[0][2]);
			UserConfig::$activities[$activities[1][0]] = array($activities[1][1], $activities[1][2]);
			UserConfig::$activities[$activities[2][0]] = array($activities[2][1], $activities[2][2]);
			UserConfig::$activities[$activities[3][0]] = array($activities[3][1], $activities[3][2]);
		}
	}

	/**************************************************************************
	 *
	 * Methods related to OAuth2 handling
	 *
	 **************************************************************************/

	/**
	 * Each module is supposed to implement this service to retrieve identity info from the server.
	 *
	 * Relying on the key only is not enough as some servers might not return same access_token
	 * for the same user. Also, access_tokens can expire or be revoked eventually, but this doesn't
	 * mean that the system user identity has changed.
	 *
	 * @param int $oauth2_client_id OAuth client id to get identity for
	 *
	 * @return array $identity Identity array that includes user info including 'id' column which
	 *                         uniquely identifies the user on server and 'name' value that can be
	 *                         used as user's name upon registration
	 */
	abstract public function getIdentity($oauth2_client_id);

	/**
	 * Starts OAuth2 flow
	 *
	 * @throws StartupAPIException
	 */
	protected function startOAuth2Flow() {
		try
		{
			$callback = UserConfig::$USERSROOTFULLURL.'/oauth2_callback.php?module='.$this->getID();

			$params = array(
				'response_type' => 'code',
				'client_id' => $this->oAuth2ClientID,
				'redirect_uri' => $callback
			);

			if (!is_null($this->oAuth2Scope)) {
				if (is_array($this->oAuth2Scope)) {
					$params['scope'] = implode(' ', $this->oAuth2Scope);
				} else {
					$params['scope'] = $this->oAuth2Scope;
				}
			}

			$first_separator = strpos($this->oAuth2LoginLink, '?') === FALSE ? '?' : '&';

			$login_link = $this->oAuth2LoginLink . $first_separator . http_build_query($params);

			//  redirect to the authorization page, they will redirect back
			header('Location: ' . $login_link);
			exit;
		} catch(OAuthException2 $e) {
			error_log(var_export($e, true));
		}
	}

	/**
	 * When we don't know current user, we need to create a new OAuth2 Client ID to use for new connection.
	 * If we know the user when OAuth2 comes through, we'll replace current OAuth2 Client ID with the new one.
	 *
	 * @return string New/temporary OAuth2 Client ID
	 *
	 * @throws DBException
	 */
	protected function getNewOAuth2ClientID() {
		$db = UserConfig::getDB();

		$module_slug = $this->getID();

		$query = 'INSERT INTO u_oauth2_clients (module_slug) VALUES (?)';
		UserTools::debug($query);

		if ($stmt = $db->prepare($query))
		{
			if (!$stmt->bind_param('s', $module_slug))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			$oauth2_client_id = $stmt->insert_id;

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return $oauth2_client_id;
	}

	/**
	 * Adds user identity to OAuth2 client
	 *
	 * @param User $user User who this OAuth2 credentials belong
	 * @param array $identity Identity array provided for this OAuth user
	 * @param int $oauth2_client_id OAuth2 client id
	 *
	 * @throws DBException
	 */
	public function addUserOAuth2Identity($user, $identity, $oauth2_client_id) {
		$db = UserConfig::getDB();

		$user_id = $user->getID();

		$server_unique_id = $identity['id'];
		$serialized_userinfo = serialize($identity);

		// updating new recently created entry
		$query = 'UPDATE u_oauth2_clients
                        SET identity = ?, userinfo = ?
                        WHERE oauth2_client_id = ?';
		UserTools::debug($query);

		if ($stmt = $db->prepare($query))
		{
			if (!$stmt->bind_param('ssi', $server_unique_id, $serialized_userinfo, $oauth2_client_id))
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

		// Inserting new link between user and their oauth2
		$query = 'INSERT INTO u_user_oauth2_identity
                        (user_id, oauth2_client_id) VALUES (?, ?)';
		UserTools::debug($query);

		if ($stmt = $db->prepare($query))
		{
			if (!$stmt->bind_param('ii', $user_id, $oauth2_client_id))
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
	 * Deletes OAuth2 client credentials
	 *
	 * @param int $oauth2_client_id OAuth2 user ID
	 *
	 * @throws DBException
	 */
	public function deleteOAuth2Client($oauth2_client_id) {
		$db = UserConfig::getDB();

		$query = 'DELETE FROM u_oauth2_clients WHERE oauth2_client_id = ?';
		UserTools::debug($query);

		if ($stmt = $db->prepare($query))
		{
			if (!$stmt->bind_param('i', $oauth2_client_id))
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
	 * Returns a user for OAuth2 credentials
	 *
	 * Gets StartupAPI user by server identity and resets user_id -> oauth2_client_id relationship if necessary
	 *
	 * @param array $identity Identity array provided for this OAuth2 user
	 * @param int $oauth2_client_id OAuth2 client id
	 *
	 * @return User|null User object or null if these credentials are not connected to any user
	 *
	 * @throws DBException
	 */
	public function getUserByOAuth2Identity($identity, $oauth2_client_id) {
		$db = UserConfig::getDB();

		$server_unique_id = $identity['id'];

		$module_slug = $this->getID();

		$user_id = null;
		$old_oauth2_client_id = null;

		$query = 'SELECT i.oauth2_client_id, i.user_id
			FROM u_user_oauth2_identity i
			INNER JOIN u_oauth2_clients c
				on i.oauth2_client_id = c.oauth2_client_id
			WHERE c.module_slug = ? AND c.identity = ?';
		UserTools::debug($query);

		if ($stmt = $db->prepare($query))
		{
			if (!$stmt->bind_param('ss', $module_slug, $server_unique_id))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($old_oauth2_client_id, $user_id))
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
		if (!$user_id) {
			return null;
		}

		$user = User::getUser($user_id);

		if ($old_oauth2_client_id != $oauth2_client_id) {
			// let's re-map from old oauth2_client_id to new one
			// deleting old one first
			$this->deleteOAuth2Client($old_oauth2_client_id);

			$this->addUserOAuth2Identity($user, $identity, $oauth2_client_id);
		}

		return $user;
	}

	/**
	 * Retrieves OAuth2 access token from the service and creates new client entry
	 *
	 * @param string $code	OAuth2 code
	 * @return int			Internal OAuth2 client id
	 */
	public function getOAuth2ClientIDByCode($code) {
		//  STEP 2:  Get access token
		$params = array(
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' =>
				UserConfig::$USERSROOTFULLURL . '/oauth2_callback.php?module=' . rawurlencode($this->getID()),
			'client_id' => $this->oAuth2ClientID,
			'client_secret' => $this->oAuth2ClientSecret
		);

		return $this->updateOAuth2Tokens($params);
	}

	/**
	 * Refreshes access token for existing credentials
	 *
	 * @param  OAuth2UserCredentials $credentials Existing user credentials
	 */
	public function refreshAccessToken($credentials) {
		$params = array(
			'grant_type' => 'refresh_token',
			'refresh_token' => $credentials->getRefreshToken(),
			'client_id' => $this->oAuth2ClientID,
			'client_secret' => $this->oAuth2ClientSecret
		);

		UserTools::debug("Refreshing OAuth2 token");

		$this->updateOAuth2Tokens($params, $credentials);
	}

	/**
	 * Creates or updates OAuth2 tokens and database records
	 * @param  array						$params					Authorization request parameters
	 * @param  OAuth2UserCredentials|null	$current_credentials	Current user credentials
	 * @return int         					Internal OAuth2 client id
	 */
	private function updateOAuth2Tokens($params, $current_credentials = null) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->oAuth2AccessTokenURL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
		curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);

		if ($this->oAuth2AccessTokenRequestFormURLencoded) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		} else {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}

		$result = curl_exec($ch);
		UserTools::debug("Request: " . var_export(curl_getinfo($ch, CURLINFO_HEADER_OUT), true));
		UserTools::debug("Response: $result");

		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
			throw new OAuth2Exception("OAuth2 call failed: " . curl_error($ch) . ' (Code: ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . ')');
		}
		curl_close($ch);

		$result = json_decode($result, true);

		$access_token = null;
		if (array_key_exists('error', $result)) {
			// looks like we couldn't exchange the code for token
			throw new OAuth2Exception("OAuth2 error returned");
		} else if (array_key_exists('access_token', $result)) {
			$access_token = $result['access_token'];
		}

		if (is_null($access_token)) {
			// hmm. no error, but not token either
			throw new OAuth2Exception("OAuth2 access token is not returned");
		}

		UserTools::debug("Result: " . var_export($result, TRUE));

		UserTools::debug("Access token: " . $access_token);

		$refresh_token = array_key_exists('refresh_token', $result) ? $result['refresh_token'] : null;
		$expires_in = array_key_exists('expires_in', $result) ? $result['expires_in'] : null;
		$token_type = array_key_exists('token_type', $result) ? $result['token_type'] : 'bearer';

		UserTools::debug("Refresh token: " . $refresh_token);

		UserTools::debug("Token type: $token_type");

		if (strtolower($token_type) != 'bearer') {
			// we pnly support bearer tokens right now, MAC token support will come later
			return null;
		}

		$db = UserConfig::getDB();

		$module_slug = $this->getID();

		$access_token_expires = is_null($expires_in) ? null : time() + $expires_in;

		$oauth2_client_id = $current_credentials ? $current_credentials->getClientId() : null;
		$current_expires = null;
		$current_refresh = null;

		if (!$current_credentials) {
			/**
			 * Let's try reading client info for this access_token from the database
			 */
			$query = 'SELECT oauth2_client_id, UNIX_TIMESTAMP(access_token_expires), refresh_token
				FROM u_oauth2_clients
				WHERE module_slug = ? AND access_token = ?';
			UserTools::debug($query);

			if ($stmt = $db->prepare($query))
			{
				if (!$stmt->bind_param('ss', $module_slug, $access_token))
				{
					throw new DBBindParamException($db, $stmt);
				}
				if (!$stmt->execute())
				{
					throw new DBExecuteStmtException($db, $stmt);
				}
				if (!$stmt->bind_result($oauth2_client_id, $current_expires, $current_refresh))
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
		}

		/**
		 * If we don't have client_id (for new access_token), let's insert a new one into the database (without identity)
		 */
		if (!$oauth2_client_id) {
			$query = 'INSERT INTO u_oauth2_clients
                                (module_slug, access_token, access_token_expires, refresh_token)
                                VALUES (?, ?, FROM_UNIXTIME(?), ?)';
			UserTools::debug($query);
			UserTools::debug(var_export([
				"module_slug" => $module_slug,
				"access_token" => $access_token,
				"access_token_expires" => $access_token_expires,
				"refresh_token" => $refresh_token
			], true));

			if ($stmt = $db->prepare($query))
			{
				if (!$stmt->bind_param('ssis',
					$module_slug,
					$access_token,
					$access_token_expires,
					$refresh_token))
				{
					throw new DBBindParamException($db, $stmt);
				}
				if (!$stmt->execute())
				{
					throw new DBExecuteStmtException($db, $stmt);
				}
				$oauth2_client_id = $stmt->insert_id;

				$stmt->close();
			} else {
				throw new DBPrepareStmtException($db);
			}
		} else {
			/**
			 * Otherwise update the token if we a refreshing the token or expires timestamp / refresh token are updated
			 */
			if ($current_credentials
				|| $access_token_expires != $current_expires
				|| $refresh_token != $current_refresh) {

				// if refresh token is in fact passed, update it, otherwise keep the old one
				if ($refresh_token) {
					$query = 'UPDATE u_oauth2_clients
                      SET access_token = ?, access_token_expires = FROM_UNIXTIME(?), refresh_token = ?
                      WHERE oauth2_client_id = ?';
				} else {
					$query = 'UPDATE u_oauth2_clients
                      SET access_token = ?, access_token_expires = FROM_UNIXTIME(?)
                      WHERE oauth2_client_id = ?';
				}

				UserTools::debug($query);
				UserTools::debug(var_export([
					"module_slug" => $module_slug,
					"access_token" => $access_token,
					"access_token_expires" => $access_token_expires,
					"refresh_token" => $refresh_token,
					"oauth2_client_id" => $oauth2_client_id
				], true));

				if ($stmt = $db->prepare($query))
				{
					if ($refresh_token) {
						if (!$stmt->bind_param('sssi',
							$access_token,
							$access_token_expires,
							$refresh_token,
							$oauth2_client_id))
						{
							throw new DBBindParamException($db, $stmt);
						}
					} else {
						if (!$stmt->bind_param('ssi',
							$access_token,
							$access_token_expires,
							$oauth2_client_id))
						{
							throw new DBBindParamException($db, $stmt);
						}
					}
					if (!$stmt->execute())
					{
						throw new DBExecuteStmtException($db, $stmt);
					}

					$stmt->close();
				} else {
					throw new DBPrepareStmtException($db);
				}

				$current_credentials->updateCredentials($access_token, $access_token_expires, $refresh_token);
			}
		}

		return $oauth2_client_id;
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
	public function renderLoginForm($template_info, $action)
	{
		$slug = $this->getID();
		$template_info['slug'] = $slug;
		$template_info['action'] = $action;
		$template_info['serviceName'] = $this->serviceName;
		$template_info['logInButtonURL'] = $this->logInButtonURL;

		// using same template for OAuth and OAuth2
		return StartupAPI::$template->render("@startupapi/oauth_login_form.html.twig", $template_info);
	}

	/**
	 * Renders registration form HTML with a single button
	 *
	 * Uses self::$signUpButtonURL for a button image if supplied
	 *
	 * @param array[] $template_info Array of base information for Twig template
	 * @param boolean $full Whatever or not to display a short version of the form or full
	 * @param string $action Action URL the form should submit data to
	 * @param array $errors An array of error messages to be displayed to the user on error
	 * @param array $data An array of data passed by a form on previous submission to display back to user
	 */
	public function renderRegistrationForm($template_info, $full = false, $action = null, $errors = null , $data = null)
	{
		if (is_null($action))
		{
			$action = UserConfig::$USERSROOTURL.'/register.php?module='.$this->getID();
		}

		$template_info['slug'] = $this->getID();
		$template_info['action'] = $action;
		$template_info['full'] = $full ? TRUE : FALSE;
		$template_info['errors'] = $errors;
		$template_info['data'] = $data;

		$template_info['serviceName'] = $this->serviceName;
		$template_info['signUpButtonURL'] = $this->signUpButtonURL;

		// using same template for OAuth and OAuth2
		return StartupAPI::$template->render("@startupapi/oauth_registration_form.html.twig", $template_info);
	}

	/**
	 * Renders user editing form
	 *
	 * Uses self::$connectButtonURL for a button image if supplied
	 *
	 * @param array[] $template_info Array of base information for Twig template
	 * @param string $action Form action URL to post back to
	 * @param array $errors Array of error messages to display
	 * @param User $user User object for current user that is being edited
	 * @param array $data Data submitted to the form
	 *
	 * @return string Rendered user ediging form for this module
	 *
	 * @throws DBException
	 */
	public function renderEditUserForm($template_info, $action, $errors, $user, $data)
	{
		$db = UserConfig::getDB();

		$user_id = $user->getID();
		$module_slug = $this->getID();

		$oauth2_client_id = null;
		$serialized_userinfo = null;

		$query = 'SELECT c.oauth2_client_id, c.userinfo
			FROM u_user_oauth2_identity i
			INNER JOIN u_oauth2_clients c
				ON i.oauth2_client_id = c.oauth2_client_id
			WHERE i.user_id = ? AND c.module_slug = ?';
		UserTools::debug($query);

		if ($stmt = $db->prepare($query))
		{
			if (!$stmt->bind_param('is', $user_id, $module_slug))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($oauth2_client_id, $serialized_userinfo))
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

		$template_info['slug'] = $this->getID();
		$template_info['action'] = $action;
		$template_info['errors'] = $errors;
		$template_info['data'] = $data;

		$template_info['connectButtonURL'] = $this->connectButtonURL;
		$template_info['title'] = $this->getTitle();

		$template_info['oauth2_client_id'] = $oauth2_client_id;
		$template_info['rendered_userinfo'] = $this->renderUserInfo($serialized_userinfo);

		UserTools::debug("Template info: " . var_export($template_info, true));

		return StartupAPI::$template->render("@startupapi/oauth2_edit_user_form.html.twig", $template_info);
	}

	/**
	 * Returns OAuth2credentials
	 *
	 * Creates a new object of $userCredentialsClass class and returns it.
	 * Should only be used by implementing modules to make first identity requests
	 *
	 * @param User $user User to get credentials for
	 *
	 * @return OAuth2UserCredentials OAuth2 credentials object
	 *
	 * @throws DBException
	 */
	protected function getOAuth2Credentials($oauth2_client_id)
	{
		$db = UserConfig::getDB();

		$serialized_userinfo = null;

		$query = 'SELECT userinfo, access_token, UNIX_TIMESTAMP(access_token_expires), refresh_token
			FROM u_oauth2_clients
			WHERE oauth2_client_id = ?';
		UserTools::debug($query);

		if ($stmt = $db->prepare($query))
		{
			if (!$stmt->bind_param('i', $oauth2_client_id))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($serialized_userinfo, $access_token, $access_token_expires, $refresh_token))
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

		return new OAuth2UserCredentials($this, $oauth2_client_id, unserialize($serialized_userinfo),
			$access_token, $access_token_expires, $refresh_token);
	}

	/**
	 * Returns user credentials
	 *
	 * Creates a new object of self::$userCredentialsClass class and returns it
	 *
	 * @param User $user User to get credentials for
	 *
	 * @return OAuth2UserCredentials User credentials object specific to the module
	 *
	 * @throws DBException
	 */
	public function getUserCredentials($user)
	{
		$db = UserConfig::getDB();

		$user_id = $user->getID();
		$module_slug = $this->getID();

		$oauth2_client_id = null;
		$serialized_userinfo = null;

		$query = 'SELECT c.oauth2_client_id, c.userinfo,
			c.access_token, UNIX_TIMESTAMP(c.access_token_expires), c.refresh_token
			FROM u_user_oauth2_identity i
			INNER JOIN u_oauth2_clients c
				on i.oauth2_client_id = c.oauth2_client_id
			WHERE i.user_id = ? AND c.module_slug = ?';
		UserTools::debug($query);

		if ($stmt = $db->prepare($query))
		{
			if (!$stmt->bind_param('is', $user_id, $module_slug))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($oauth2_client_id, $serialized_userinfo,
				$access_token, $access_token_expires, $refresh_token))
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

		return new $this->userCredentialsClass($this, $oauth2_client_id, unserialize($serialized_userinfo),
			$access_token, $access_token_expires, $refresh_token);
	}

	/**
	 * Renders user information as HTML
	 *
	 * Subclasses can override to add links to user profiles on destination sites, userpics and etc.
	 *
	 * @param string $serialized_userinfo Serialized array of user info parameters from provider with "id" and "name" required
	 *
	 * @return string Rendered user information HTML
	 */
	protected function renderUserInfo($serialized_userinfo) {
		$user_info = unserialize($serialized_userinfo);

		if (array_key_exists('name', $user_info)) {
			return $user_info['name'];
		} else {
			return NULL;
		}
	}

	/**
	 * Called when login form is submitted
	 *
	 * This method never returns user information, it redirects to OAuth2 flow
	 * ending on oauth2_callback.php which implements the logic instead of login.php
	 *
	 * @param array $data Form data
	 * @param boolean $remember whatever or not to remember the user
	 *
	 * @return null This method never returns user information
	 */
	public function processLogin($data, &$remember)
	{
		$this->startOAuth2Flow();

		return null; // it never reaches this point
	}

	/**
	 * Called when registration form is submitted
	 *
	 * This method never returns user information, it redirects to OAuth2 flow
	 * ending on oauth2_callback.php which implements the logic instead of register.php
	 *
	 * @param array $data Form data
	 * @param boolean $remember whatever or not to remember the user
	 *
	 * @return null This method never returns user information
	 */
	public function processRegistration($data, &$remember)
	{
		$this->startOAuth2Flow();

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
		if (array_key_exists('remove', $data) && array_key_exists('oauth2_client_id', $data)) {
			$db = UserConfig::getDB();

			$oauth2_client_id = $data['oauth2_client_id'];

			$this->deleteOAuth2Client($oauth2_client_id);

			if (!is_null($this->USERBASE_ACTIVITY_OAUTH2_MODULE_REMOVED)) {
				$user->recordActivity($this->USERBASE_ACTIVITY_OAUTH2_MODULE_REMOVED);
			}

			return true;
		}

		if (array_key_exists('add', $data)) {
			$this->startOAuth2Flow();
		}
	}

	/**
	 * Records login activity for a user
	 *
	 * @param User $user User object
	 */
	public function recordLoginActivity($user) {
		if (!is_null($this->USERBASE_ACTIVITY_OAUTH2_MODULE_LOGIN)) {
			$user->recordActivity($this->USERBASE_ACTIVITY_OAUTH2_MODULE_LOGIN);
		}
	}

	/**
	 * Records registration activity for a user
	 *
	 * @param User $user User object
	 */
	public function recordRegistrationActivity($user) {
		if (!is_null($this->USERBASE_ACTIVITY_OAUTH2_MODULE_REGISTER)) {
			$user->recordActivity($this->USERBASE_ACTIVITY_OAUTH2_MODULE_REGISTER);
		}
	}

	/**
	 * Records connection added activity for a user
	 *
	 * @param User $user User object
	 */
	public function recordAddActivity($user) {
		if (!is_null($this->USERBASE_ACTIVITY_OAUTH2_MODULE_ADDED)) {
			$user->recordActivity($this->USERBASE_ACTIVITY_OAUTH2_MODULE_ADDED);
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

		$query = 'SELECT count(*) AS conns
			FROM u_users u
			LEFT JOIN u_user_oauth2_identity oa ON u.id = oa.user_id
			WHERE oa.oauth2_client_id IS NOT NULL AND oa.module = ?';
		UserTools::debug($query);

		if ($stmt = $db->prepare($query))
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

	/**
	 * Makes HTTP request with OAuth2 authentication
	 *
	 * This method allows requesting information on behalf of the user from a 3rd party provider.
	 * Possibly the most important feature of the whole system.
	 *
	 * @param OAuth2UserCredentials $credentials Credentials object representing OAuth2 user
	 * @param string $request Request URL
	 * @param string $method HTTP method (e.g. GET, POST, PUT, etc)
	 * @param array $params Request parameters key->value array
	 * @param boolean $refresh_if_unauthorized Whatever to attempt to refresh the token on failure (to avoid recursion)
	 *
	 * @return array Response data (code=>int, headers=>array(), body=>string)
	 */
	public function makeOAuth2Request($credentials, $url, $method = 'GET', $request_params = array(), $curlopt = array(), $refresh_if_unauthorized = TRUE)
	{
		$ch = curl_init();

		if (!is_array($request_params)) {
			$request_params = array();
		}
		if (!is_array($curlopt)) {
			$curlopt = array();
		}
		$params = array_merge($request_params, $this->oAuth2ExtraParameters);

		$first_separator = strpos($url, '?') === FALSE ? '?' : '&';

		// always pass access_token as a query string parameter
		if (count($params)) {
			if ($method == 'GET') {
				$call_url = $url . $first_separator . http_build_query($params);
			} else if ($method == 'POST') {
				$curlopt[CURLOPT_POST] = TRUE;
				$curlopt[CURLOPT_POSTFIELDS] = $params;
			}
		}

		// use bearer tocken or query string
		if ($this->oAuth2SendAccessTokenAsHeader) {
			$curlopt[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $credentials->getAccessToken();
		} else {
			$call_url = $url . $first_separator . http_build_query(array(
				$this->oAuth2AccessTokenParamName => $credentials->getAccessToken()
			));
		}

		UserTools::debug("URL: $call_url");

		curl_setopt($ch, CURLOPT_URL, $call_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
		curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);

		if (is_array($curlopt)) {
			curl_setopt_array($ch, $curlopt);
		}

		$result = curl_exec($ch);
		UserTools::debug("Request: " . var_export(curl_getinfo($ch, CURLINFO_HEADER_OUT), true));
		UserTools::debug("Response: $result");
		UserTools::debug("HTTP Response code: " . curl_getinfo($ch, CURLINFO_HTTP_CODE));

		// let's see if our access token has expired and try to refresh it
		if ($result) {
			$data = json_decode($result, true);

			if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 401 || array_key_exists('code', $data) && $data['code'] == 'not_authorized') {
				$this->refreshAccessToken($credentials);

				// call thyself without refreshing on failure (to avoid recursion)

				// @TODO debug why this goes into infinite loop before re-emabling it
				return $this->makeOAuth2Request($credentials, $url, $method, $request_params, $curlopt, FALSE);
			}
		}

		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
			throw new OAuth2Exception("OAuth2 call failed: " . curl_error($ch) . ' (Code: ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . ')');
		}
		curl_close($ch);

		return $result;
	}

	/**
	 * All OAuth2-based systems have very small footprint
	 *
	 * @return boolean Always returns true
	 */
	public function isCompact() {
		return true;
	}
}

/**
 * Class representing user credentials for making OAuth2 API calls
 *
 * Should be extended by subclasses to implement additional features and customize
 * formatting for user information, e.g. add links to user profiles, userpics and so on.
 *
 * @package StartupAPI
 * @subpackage Authentication\OAuth2
 */
class OAuth2UserCredentials extends UserCredentials {
	/**
	 * Module that represents the credentials
	 */
	protected $oauth2_module;

	/**
	 * @var int OAuth2 client id
	 */
	protected $oauth2_client_id;

	/**
	 * @var array User info object specific to a subclass
	 */
	protected $userinfo;

	/**
	 * @var string OAuth2 access token
	 */
	protected $access_token;

	/**
	 * @var int Second since epoch when OAuth2 access token expires
	 */
	protected $access_token_expires;

	/**
	 * @var string Refresh token to be used to get new OAuth access token without re-authorization
	 */
	protected $refresh_token;

	/**
	 * Creates new OAuth2 credentials
	 *
	 * @param int $oauth2_client_id OAuth client ID
	 * @param array $userinfo User info array as retrieved from provider
	 */
	public function __construct($oauth2_module, $oauth2_client_id, $userinfo,
		$access_token, $access_token_expires, $refresh_token)
	{
		$this->oauth2_module = $oauth2_module;
		$this->oauth2_client_id = $oauth2_client_id;
		$this->userinfo = $userinfo;
		$this->access_token = $access_token;
		$this->access_token_expires = $access_token_expires;
		$this->refresh_token = $refresh_token;
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
	 * Returns OAuth2 access token
	 *
	 * @return string OAuth2 access token
	 */
	public function getAccessToken() {
		return $this->access_token;
	}

	/**
	 * Returns OAuth2 refresh token
	 *
	 * @return string OAuth2 refresh token
	 */
	public function getRefreshToken() {
		return $this->refresh_token;
	}

	public function getClientId() {
		return $this->oauth2_client_id;
	}

	public function updateCredentials($access_token, $access_token_expires, $refresh_token) {
		$this->access_token = $access_token;
		$this->access_token_expires = $access_token_expires;
		if ($refresh_token) {
			$this->refresh_token = $refresh_token;
		}
	}

	public function makeOAuth2Request($request, $method = 'GET', $params = null, $curlopt = array()) {
		return $this->oauth2_module->makeOAuth2Request($this, $request, $method, $params, $curlopt);
	}
}

/**
 * Represents exceptions related to OAuth2 flows
 *
 * @package StartupAPI
 * @subpackage Authentication\OAuth2
 */
class OAuth2Exception extends StartupAPIException {

}
