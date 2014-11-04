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

		// TODO Replace it with immediate FB Connect call:
		// http://code.google.com/p/userbase/issues/detail?id=16
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

		if ($stmt = $db->prepare('SELECT fb_id FROM ' . UserConfig::$mysql_prefix . 'users WHERE id = ?')) {
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

		if ($stmt = $db->prepare('SELECT count(*) AS conns FROM ' . UserConfig::$mysql_prefix . 'users WHERE fb_id IS NOT NULL')) {
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

		if ($stmt = $db->prepare('SELECT CAST(regtime AS DATE) AS regdate, count(*) AS regs FROM ' . UserConfig::$mysql_prefix . 'users WHERE fb_id IS NOT NULL GROUP BY regdate')) {
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

		$this->renderForm($template_info, $action, 'login');
	}

	public function renderAutoLogoutForm() {
		?><html>
			<head><title>Logging out from Facebook...</title></head>
			<body>
				<div id="fb-root"></div>
				<script src="http://connect.facebook.net/en_US/all.js"></script>
				<script>
					// force logout sequence after a long timeout of 5 seconds
					setTimeout('window.location.href = "<?php echo UserConfig::$USERSROOTFULLURL; ?>/logout.php?autologgedout=<?php echo $this->getID(); ?>"', 5000);

					FB.init({
						appId  : '<?php echo $this->appID ?>',
						status : true, // check login status
						cookie : true, // enable cookies to allow the server to access the session
						channelURL : '<?php echo UserConfig::$USERSROOTFULLURL; ?>/modules/facebook/channel.php', // channel file
						oauth  : true // enable OAuth 2.0
					});
					FB.getLoginStatus(function(response) {
						if (response.authResponse) {
							FB.logout(function(response) {
								window.location.href = "<?php echo UserConfig::$USERSROOTFULLURL; ?>/logout.php?autologgedout=<?php echo $this->getID(); ?>";
							});
						} else {
							window.location.href = "<?php echo UserConfig::$USERSROOTFULLURL; ?>/logout.php?autologgedout=<?php echo $this->getID(); ?>";
						}
					});
				</script>
				Logging out from Facebook...
			</body>
		</html>
		<?php
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
		$template_info['action'] = $action;
		$template_info['form'] = $form;
		$template_info['show_facepile'] = $this->show_facepile;
		$template_info['appID'] = $this->appID;
		$template_info['permissions'] = $this->permissions;
		$template_info['permissions_string'] = implode(',', $this->permissions);

		if (UserConfig::$currentTOSVersion && is_callable(UserConfig::$onRenderTOSLinks)) {
			ob_start();
			call_user_func(UserConfig::$onRenderTOSLinks);
			$template_info['TOSlinks'] = ob_get_contents();
			ob_end_clean();
		}

		return StartupAPI::$template->render("modules/facebook/login_form.html.twig", $template_info);
	}

	public function renderRegistrationForm($full = false, $action = null, $errors = null, $data = null) {
		if (is_null($action)) {
			$action = UserConfig::$USERSROOTURL . '/register.php?module=' . $this->getID();
		}

		$this->renderForm($template_info, $action, 'register');
	}

	public function renderEditUserForm($action, $errors, $user, $data) {
		$fb_id = $user->getFacebookID();

		if (is_null($fb_id)) {
			$this->renderForm($action, 'connect');
		} else {
			try {
				$me = $this->api('/me?fields=id,name,email,link');
			} catch (FacebookApiException $e) {
				UserTools::debug("Can't get /me API data: " . $e);
				return;
			}
			?>
			<table><tr>
					<td rowspan="2"><a href="<?php echo UserTools::escape($me['link']) ?>" target="_blank"><img src="http://graph.facebook.com/<?php echo $fb_id ?>/picture" style="border: 0; max-width: 100px; max-height: 100px" title="<?php echo UserTools::escape($me['name']) ?>"></a></td>
					<td><a href="<?php echo UserTools::escape($me['link']) ?>" target="_blank"><?php echo UserTools::escape($me['name']) ?></a></td>
				</tr><tr>
					<td>
						<form style="margin: 0" action="<?php echo $action ?>" method="POST" name="facebookusereditform">
							<input type="hidden" name="save" value="Save &gt;&gt;&gt;"/>
							<input class="btn btn-mini" type="submit" name="remove" value="remove" style="font-size: xx-small"/>
							<?php UserTools::renderCSRFNonce(); ?>
						</form>
					</td>
				</tr></table>
			<?php
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

		$permissions = $this->api('/me/permissions');
		UserTools::debug('User permissions: ' . var_export($permissions, true));
		foreach ($this->permissions as $perm) {
			if (!array_key_exists($perm, $permissions['data'][0]) || $permissions['data'][0][$perm] !== 1) {
				// looks like not all required permissions were granted
				UserTools::debug("Can't login - not enough permissions granted");
				return null;
			}
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
		} catch (FacebookApiException $e) {
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
			} catch (FacebookApiException $e) {
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
		return "<a href=\"http://www.facebook.com/profile.php?id=$this->fb_id\">$this->fb_id</a>";
	}

}
