<?php
require_once(__DIR__ . '/php-graph-sdk/src/Facebook/autoload.php');

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
			'app_id' => $this->appID,
			'app_secret' => $this->secret,
			'graph_api_version' => 'v5.0'
		);

		$this->sdk = new \Facebook\Facebook($config);
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

		if ($stmt = $db->prepare('SELECT fb_id, fb_link FROM u_users WHERE id = ?')) {
			if (!$stmt->bind_param('i', $userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($fb_id, $fb_link)) {
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();

			if (!is_null($fb_id)) {
				return new FacebookUserCredentials($fb_id, $fb_link);
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
		$fb_link = $user->getFacebookProfileLink();

		if (is_null($fb_id)) {
			return $this->renderForm($template_info, $action, 'connect');
		} else {
			try {
			  $response = $this->sdk->get('/me?fields=id,name,email,link', $this->getAccessToken());
			} catch(\Facebook\Exceptions\FacebookResponseException $e) {
			  // When Graph returns an error
			  UserTools::debug("Can't get Facebook user. Graph returned an error: " . $e->getMessage());
				return null;
			} catch(\Facebook\Exceptions\FacebookSDKException $e) {
			  // When validation fails or other local issues
				UserTools::debug("Can't get Facebook user. Facebook SDK returned an error: " . $e->getMessage());
				return null;
			}

			$fbuser = $response->getGraphUser();
			$fbuser_id = $fbuser->getId();
			$me = $response->getDecodedBody();

			$template_info['slug'] = $this->getID();
			$template_info['action'] = $action;
			$template_info['errors'] = $errors;
			$template_info['data'] = $data;

			$template_info['me'] = $me;
			$template_info['fb_id'] = $fbuser_id;
			$template_info['fb_link'] = $fb_link;

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

	function getAccessToken() {
		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
			'path' => UserConfig::$SITEROOTURL,
			'httponly' => true
		));

		$accessToken = $storage->fetch(UserConfig::$fb_access_token_key);

		UserTools::debug('FB access token used: ' . var_export($accessToken, true));

		if (!$accessToken) {
			$accessToken = $this->retrieveAccessToken();
		}

		return $accessToken;
	}

	function retrieveAccessToken() {
		try {
		  $accessToken = $this->sdk->getJavaScriptHelper()->getAccessToken();
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  UserTools::debug("Can't get Facebook user. Graph returned an error: " . $e->getMessage());
			return null;
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
			UserTools::debug("Can't get Facebook user. Facebook SDK returned an error: " . $e->getMessage());
			return null;
		}

		if (!isset($accessToken)) {
			UserTools::debug("Can't get Facebook user. No cookie set or no OAuth data could be obtained from cookie.");
			return null;
		}

		$oAuth2Client = $this->sdk->getOAuth2Client();
		if (!$accessToken->isLongLived()) {
		  // Exchanges a short-lived access token for a long-lived one
		  try {
		    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
		  } catch (Facebook\Exceptions\FacebookSDKException $e) {
				UserTools::debug("Error getting long-lived access token: " . $helper->getMessage());
				return null;
		  }
		}

		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
			'path' => UserConfig::$SITEROOTURL,
			'httponly' => true
		));

		if (!$storage->store(UserConfig::$fb_access_token_key, $accessToken->getValue())) {
			throw new StartupAPIException(implode("\n", $storage->errors));
		}

		return $accessToken->getValue();
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
		  $response = $this->sdk->get('/me', $this->getAccessToken());
		} catch(\Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  UserTools::debug("Can't get Facebook user. Graph returned an error: " . $e->getMessage());
			return null;
		} catch(\Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
			UserTools::debug("Can't get Facebook user. Facebook SDK returned an error: " . $e->getMessage());
			return null;
		}

		$fbuser = $response->getGraphUser();
		$fbuser_id = $fbuser->getId();

		UserTools::debug('Facebook user id: ' . $fbuser_id);

		if ($fbuser_id == 0) {
			// if we're trying to auto-login, just return null
			if ($auto) {
				return null;
			}

			$errors['fbuserid'][] = 'No Facebook id is passed';
			throw new InputValidationException('No facebook user id', 0, $errors);
		}

		try {
			$granted_permissions = $this->api('/me/permissions')->getDecodedBody();
			UserTools::debug('User permissions: ' . var_export($granted_permissions, true));

			foreach ($granted_permissions['data'] as $perm) {
				if ($perm['status'] == 'granted') {
					$perm_lookup[$perm['permission']] = true;
				}
			}

			foreach ($this->permissions as $perm) {
				if (!array_key_exists($perm, $perm_lookup)) {
					// looks like not all required permissions were granted
					UserTools::debug("Can't login - not enough permissions granted");
					return null;
				}
			}
		} catch (Exception $e) {
			UserTools::debug("Can't get FB /me/permissions API data: " . $e);
			return null;
		}

		$user = User::getUserByFacebookID($fbuser_id);

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
		  $response = $this->sdk->get('/me?fields=id,name,email,link', $this->getAccessToken());
		} catch(\Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  UserTools::debug("Can't get Facebook user. Graph returned an error: " . $e->getMessage());
			return null;
		} catch(\Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
			UserTools::debug("Can't get Facebook user. Facebook SDK returned an error: " . $e->getMessage());
			return null;
		}

		$fbuser = $response->getGraphUser();
		$fbuser_id = $fbuser->getId();
		$fbuser_link = $fbuser["link"];
		$me = $response->getDecodedBody();

		$errors = array();
		if (!$fbuser_id) {
			$errors['fbuserid'][] = 'No Facebook id is passed';
			throw new InputValidationException('No facebook user id', 0, $errors);
		}

		// checking if user with this Facebook ID already exists and if so, then logs them in
		$existing_user = User::getUserByFacebookID($fbuser_id);
		if (!is_null($existing_user)) {
			UserTools::debug('processRegistration - existing user: ' . $existing_user->getID());
			$existing_user->recordActivity(USERBASE_ACTIVITY_LOGIN_FB);
			return $existing_user;
		}

		if (array_key_exists('name', $me)) {
			$name = $me['name'];
		} else {
			// @TODO Revise this check to show appropriate message when FB user doesn't have a name
			$errors['username'][] = "User doesn't have a name";
		}

		// ok, let's create a user
		try {
			$user = User::createNewFacebookUser($name, $fbuser_id, $fbuser_link, $me);
		} catch (UserCreationException $e) {
			$errors[$e->getField()][] = $e->getMessage();
		}

		if (count($errors) > 0) {
			// @TODO figure out what to throw here and send appropriate exception
			throw new ExistingUserException(null, 'User already exists', 0, $errors);
		}

		$user->recordActivity(USERBASE_ACTIVITY_REGISTER_FB);
		return $user;
	}

	public function processEditUser($user, $data) {
		if (array_key_exists('remove', $data)) {
			$user->setFacebookID(null);
			$user->setFacebookProfileLink(null);
			$user->save();

			$user->recordActivity(USERBASE_ACTIVITY_REMOVED_FB);

			try {
				$response = $this->sdk->delete('/me/permissions', [], $this->getAccessToken());
			} catch(\Facebook\Exceptions\FacebookResponseException $e) {
				// When Graph returns an error
				UserTools::debug("Can't delete Facebook app. Graph returned an error: " . $e->getMessage());
				return null;
			} catch(\Facebook\Exceptions\FacebookSDKException $e) {
				// When validation fails or other local issues
				UserTools::debug("Can't delete Facebook app. Facebook SDK returned an error: " . $e->getMessage());
				return null;
			}

			$storage = new MrClay_CookieStorage(array(
				'secret' => UserConfig::$SESSION_SECRET,
				'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
				'path' => UserConfig::$SITEROOTURL,
				'httponly' => true
			));

			$storage->delete(UserConfig::$fb_access_token_key);

			return true;
		}

		try {
		  $response = $this->sdk->get('/me?fields=id,name,email,link', $this->getAccessToken());
		} catch(\Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  UserTools::debug("Can't get Facebook user. Graph returned an error: " . $e->getMessage());
			return null;
		} catch(\Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
			UserTools::debug("Can't get Facebook user. Facebook SDK returned an error: " . $e->getMessage());
			return null;
		}

		$fbuser = $response->getGraphUser();
		$fbuser_id = $fbuser->getId();
		$fbuser_link = $fbuser["link"];
		$me = $response->getDecodedBody();

		$errors = array();
		if (!$fbuser_id) {
			$errors['fbuserid'][] = 'No Facebook id is passed';
			throw new InputValidationException('No facebook user id', 0, $errors);
		}

		if (!is_null(User::getUserByFacebookID($fbuser_id))) {
			$errors['fbuserid'][] = 'Another user is already associated with your Facebook account.';
		}

		if (count($errors) > 0) {
			throw new InputValidationException('Validation failed', 0, $errors);
		}

		$user->setFacebookID($fbuser_id);
		$user->setFacebookProfileLink($fbuser_link);

		// if user has email address and we required it for Facebook connection, let's save it
		if (!$user->getEmail()) {
			if (array_key_exists('email', $me)) {
				try {
					$user->setEmail($me['email']);
				} catch (ExistingUserException $ex) {
					// @TODO find existing users with such emai address and merge accounts???
				}
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
	public function api($query) {
		$args = func_get_args();

		try {
			$result = $this->sdk->get($query, $this->getAccessToken());
		} catch(\Facebook\Exceptions\FacebookResponseException $e) {
			// When Graph returns an error
			UserTools::debug("Graph returned an error: " . $e->getMessage());

			return null;
		} catch(\Facebook\Exceptions\FacebookSDKException $e) {
			// When validation fails or other local issues
			UserTools::debug("Facebook SDK returned an error: " . $e->getMessage());

			return null;
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
	 * @var string Facebook profile link
	 */
	private $fb_link;

	/**
	 * Creates credemtials onject
	 *
	 * @param int $fb_id Facebook user ID
	 * @param string $fb_link Facebook profile link
	 */
	public function __construct($fb_id, $fb_link = null) {
		$this->fb_id = $fb_id;
		$this->fb_link = $fb_link;
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
		return StartupAPI::$template->render("@startupapi/modules/facebook/credentials.html.twig", array('fb_id' => $this->fb_id, 'fb_link' => $this->fb_link));
	}

}
