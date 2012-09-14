<?php
/**
 * @package StartupAPI
 * @subpackage Authentication
 */
require_once(dirname(__FILE__).'/facebook.php');

class FacebookAuthenticationModule extends AuthenticationModule
{
	private $sdk;

	private $appID;
	private $secret;
	private $permissions;
	private $remember;
	private $show_facepile = true;

	private $headersLoaded = false;

	/**
	 * Cretes Facebook authentication module
	 * @param string $appID Facebook application ID
	 * @param string $secret Facebook application secret (not key)
	 * @param array $permissions Array of additional permissions (e.g. email)
	 * 	full list can be found here: http://developers.facebook.com/docs/authentication/permissions/
	 */
	public function __construct($appID, $secret, $permissions = array(), $remember = true, $options = null)
	{
		parent::__construct();

		$this->appID= $appID;
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
			'appId'  => $this->appID,
			'secret' => $this->secret
		);

		$this->sdk = new Facebook($config);
	}

	public function getID()
	{
		return "facebook";
	}

	public function getLegendColor()
	{
		return "3b5999";
	}

	public function getTitle()
	{
		return "Facebook";
	}

	public function getUserCredentials($user)
	{
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('SELECT fb_id FROM '.UserConfig::$mysql_prefix.'users WHERE id = ?'))
		{
			if (!$stmt->bind_param('i', $userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($fb_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();

			if (!is_null($fb_id))
			{
				return new FacebookUserCredentials($fb_id);
			}
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return null;
	}

	public function getTotalConnectedUsers()
	{
		$db = UserConfig::getDB();

		$conns = 0;

		if ($stmt = $db->prepare('SELECT count(*) AS conns FROM '.UserConfig::$mysql_prefix.'users WHERE fb_id IS NOT NULL'))
		{
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

	/*
	 * retrieves aggregated registrations numbers 
	 */
	public function getDailyRegistrations()
	{
		$db = UserConfig::getDB();

		$dailyregs = array();

		if ($stmt = $db->prepare('SELECT CAST(regtime AS DATE) AS regdate, count(*) AS regs FROM '.UserConfig::$mysql_prefix.'users WHERE fb_id IS NOT NULL GROUP BY regdate'))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($regdate, $regs))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$dailyregs[] = array('regdate' => $regdate, 'regs' => $regs);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $dailyregs;
	}

	public function renderLoginForm($action)
	{
		if (is_null($action))
		{
			$action = UserConfig::$USERSROOTURL.'/login.php?module='.$this->getID();
		}

		$this->renderForm($action, 'login');
	}

	public function renderAutoLogoutForm()
	{
?><html>
<head><title>Logging out from Facebook...</title></head>
<body>
<div id="fb-root"></div>
<script src="http://connect.facebook.net/en_US/all.js"></script>
<script>
FB.init({
	appId  : '<?php echo $this->appID?>',
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

// force logout sequence after a long timeout of 15 seconds
setTimeout('window.location.href = "<?php echo UserConfig::$USERSROOTFULLURL; ?>/logout.php?autologgedout=<?php echo $this->getID(); ?>"', 15000);
</script>
Logging out from Facebook...
</body>
</html>
<?php
	}

	private function renderForm($action, $form)
	{
		if ($form == 'login') {
			$formsubmit = 'login';
			$buttonspritestyle = 'background-position: 0px -22px; width: 198px; height: 22px;';
			$buttontitle = 'Login with Facebook';
		} else if ($form == 'register') {
			$formsubmit = 'register';
			$buttonspritestyle = 'background-position: 0px 0px; width: 250px; height: 22px;';
			$buttontitle = 'Quick Sign-up using Facebook';
		} else if ($form == 'connect') {
			$formsubmit = 'save';
			$buttonspritestyle = 'background-position: 0px -44px; width: 230px; height: 22px;';
			$buttontitle = 'Connect to your Facebook Account';
		}

		?><div id="fb-root"></div>

		<?php if ($this->show_facepile && $form == 'register') { ?>
		<fb:facepile width="450" size="large" max-rows="1"></fb:facepile>
		<?php } ?>
		<form action="<?php echo $action?>" method="POST" name="facebookconnectform">
		<input type="hidden" name="<?php echo $formsubmit ?>" value="Connect &gt;&gt;&gt;"/>
		<?php UserTools::renderCSRFNonce(); ?>
		</form>
		<a class="startupapi-fb-connect" href="#" onclick="UserBaseFBConnectButtonClicked(); return false;"><span style="background-image: url(<?php echo UserConfig::$USERSROOTURL ?>/modules/facebook/facebook-sprite.png); <?php echo $buttonspritestyle ?> display: block; cursor: hand; margin-top: 0.3em" title="<?php echo $buttontitle ?>"></span></a>

		<script>
		var UserBaseFBFormType = <?php echo json_encode($form) ?>;

		var UserBaseFBConnectButtonClicked = function() {
			// FB is not loaded yet
		};

		window.fbAsyncInit = function() {
			// permissions required by this instance of UserBase
			var required_perms = <?php echo json_encode($this->permissions); ?>;
			var required_perms_string = <?php echo json_encode(implode(',', $this->permissions)); ?>;

			FB.init({
				appId  : '<?php echo $this->appID?>',
				status : true, // check login status
				cookie : true, // enable cookies to allow the server to access the session
				oauth  : true, // enable OAuth 2.0
				xfbml  : true, // parse XFBML
				channelURL : '<?php echo UserConfig::$USERSROOTFULLURL; ?>/modules/facebook/channel.php' // channel file
			});

			// Auto-login if user is connected already
			FB.getLoginStatus(function(r) {
				if(r.status === 'connected') {
					// if permissions are nor required, submit the form now
					if (required_perms.length === 0) {
						document.facebookconnectform.submit();
						return;
					}

					FB.api('/me/permissions', function(response) {
						if (response && response.data && response.data.length > 0) {
							var perms = response.data[0];

							var i = required_perms.length;
							while (i--) {
								// do not submit form if at least one
								// required permission is not found
								if (perms[required_perms[i]] !== 1) {
									return;
								}
							}

							document.facebookconnectform.submit();
							return;
						}
					});

				}
			});

			// when button is clicked popu-up a dialog
			UserBaseFBConnectButtonClicked = function() {
				// it will only have 'connected' status if permissions match
				FB.login(function(response) {
					if (response.status === 'connected') {
						document.facebookconnectform.submit();
						return;
					}
				}, {scope: required_perms_string});
			};
		};

		(function() {
			var e = document.createElement('script');
			e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
			e.async = true;
			document.getElementById('fb-root').appendChild(e);
		}());

		</script>
		<?php
	}

	public function renderRegistrationForm($full = false, $action = null, $errors = null, $data = null)
	{
		if (is_null($action))
		{
			$action = UserConfig::$USERSROOTURL.'/register.php?module='.$this->getID();
		}

		$this->renderForm($action, 'register');
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
		$fb_id = $user->getFacebookID();

		if (is_null($fb_id)) {
			$this->renderForm($action, 'connect');
		}
		else
		{
			try {
				$me = $this->api('/'.$fb_id.'?fields=id,name,email,link');
			} catch (FacebookApiException $e) {
				UserTools::debug("Can't get /me API data");
				return null;
			}
			?>
			<table><tr>
			<td rowspan="2"><a href="<?php echo UserTools::escape($me['link']) ?>" target="_blank"><img src="http://graph.facebook.com/<?php echo $fb_id ?>/picture" style="border: 0; max-width: 100px; max-height: 100px" title="<?php echo UserTools::escape($me['name']) ?>"></a></td>
			<td><a href="<?php echo UserTools::escape($me['link']) ?>" target="_blank"><?php echo UserTools::escape($me['name']) ?></a></td>
			</tr><tr>
			<td>
			<form action="<?php echo $action?>" method="POST" name="facebookusereditform">
			<input type="hidden" name="save" value="Save &gt;&gt;&gt;"/>
			<input type="submit" name="remove" value="remove" style="font-size: xx-small"/>
			<?php UserTools::renderCSRFNonce(); ?>
			</form>
			</td>
			</tr></table>
		<?php
		}
	}

	public function processAutoLogin()
	{
		UserTools::debug('Automatic login start');

		$remember = false;

		return $this->processLogin(null, $remember, true);
	}

	public function getAutoLogoutURL($return) {
		return UserConfig::$USERSROOTFULLURL.'/modules/facebook/logout.php';
	}

	public function processLogin($post_data, &$remember, $auto = false)
	{
		UserTools::debug('processLogin start');
		$remember = $this->remember;

		try {
			$fbuser = intval($this->sdk->getUser());
		} catch (FacebookApiException $e) {
			UserTools::debug("Can't get Facebook user");
			return null;
		}

		UserTools::debug('Facebook user id: '.$fbuser);

		if ($fbuser == 0) {
			// if we're trying to auto-login, just return null
			if ($auto) {
				return null;
			}

			$errors['fbuserid'][] = 'No Facebook id is passed';
			throw new InputValidationException('No facebook user id', 0, $errors);
		}

		$permissions = $this->api('/me/permissions');
		UserTools::debug('User permissions: '.var_export($permissions, true));
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

	public function processRegistration($post_data, &$remember)
	{
		UserTools::debug('processRegistration start');
		$remember = $this->remember;

		try {
			$fbuser = intval($this->sdk->getUser());
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
		if (!is_null($existing_user))
		{
			UserTools::debug('processRegistration - existing user: '.$existing_user->getID());
			$existing_user->recordActivity(USERBASE_ACTIVITY_LOGIN_FB);
			return $existing_user;
		}

		try {
			$me = $this->api('/me?fields=id,name,email,link');
		} catch (FacebookApiException $e) {
			UserTools::debug("Can't get /me API data");
			return null;
		}

		if (array_key_exists('name', $me))
		{
			$name = $me['name'];
		}
		else
		{
			$errors['username'][] = "User doesn't have a name";
		}

		// ok, let's create a user
		try {
			$user = User::createNewFacebookUser($name, $fbuser, $me);
		} catch (UserCreationException $e) {
			$errors[$e->getField()][] = $e->getMessage();
		}

		if (count($errors) > 0)
		{
			throw new ExistingUserException('User already exists', 0, $errors);
		}

		$user->recordActivity(USERBASE_ACTIVITY_REGISTER_FB);
		return $user;
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
		if (array_key_exists('remove', $data)) {
			$user->setFacebookID(null);
			$user->save();

			$user->recordActivity(USERBASE_ACTIVITY_REMOVED_FB);

			$this->sdk->api('/me/permissions','DELETE');

			return true;
		}

		try {
			$fbuser = intval($this->sdk->getUser());
		} catch (FacebookApiException $e) {
			UserTools::debug("Can't get Facebook user");
			return null;
		}

		$errors = array();
		if ($fbuser == 0) {
			$errors['fbuserid'][] = 'No Facebook id is passed';
			throw new InputValidationException('No facebook user id', 0, $errors);
		}

		if (!is_null(User::getUserByFacebookID($fbuser)))
		{
			$errors['fbuserid'][] = 'Another user is already associated with your Facebook account.';
		}

		if (count($errors) > 0)
		{
			throw new InputValidationException('Validation failed', 0, $errors);
		}

		$user->setFacebookID($fbuser);

		// if user has email address and we required it for Facebook connection, let's save it
		if (!$user->getEmail()) {
			try {
				$me = $this->api('/me?fields=email');
			} catch (FacebookApiException $e) {
				UserTools::debug("Can't get /me API data");
				return null;
			}

			if (array_key_exists('email', $me))
			{
				$user->setEmail($me['email']);
			}
		}

		$user->save();

		$user->recordActivity(USERBASE_ACTIVITY_ADDED_FB);

		return true;
	}

	/**
	 * Wrapper to native SDK api call that will recover in case of expired tokens
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
				header('Location: '.$url);
				exit;
			}

			throw $fb_ex;
		}

		return $result;
	}
}

class FacebookUserCredentials extends UserCredentials {
	// Facebook user id
	private $fb_id;

	public function __construct($fb_id) {
		$this->fb_id = $fb_id;
	}

	public function getFacebookID() {
		return $this->fb_id;
	}

	public function getHTML() {
		return "<a href=\"http://www.facebook.com/profile.php?id=$this->fb_id\">$this->fb_id</a>";
	}
}
