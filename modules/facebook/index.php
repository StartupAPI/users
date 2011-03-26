<?php
require_once(dirname(__FILE__).'/facebook.php');

class FacebookAuthenticationModule implements IAuthenticationModule
{
	private $appID;
	private $secret;
	private $permissions;
	private $remember;

	private $headersLoaded = false;

	/**
	 * Cretes Facebook authentication module
	 * @param string $appID Facebook application ID
	 * @param string $secret Facebook application secret (not key)
	 * @param array $permissions Array of additional permissions (e.g. email)
	 * 	full list can be found here: http://developers.facebook.com/docs/authentication/permissions/
	 */
	public function __construct($appID, $secret, $permissions = array(), $remember = true)
	{
		$this->appID= $appID;
		$this->secret = $secret;
		$this->permissions = $permissions;

		// TODO Replace it with immediate FB Connect call:
		// http://code.google.com/p/userbase/issues/detail?id=16
		$this->remember = $remember;
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
		$facebook = new Facebook(array(
			'appId'  => $this->appID,
			'secret' => $this->secret,
			'cookie' => true, // enable optional cookie support
		));

		$session = $facebook->getSession();

		?><div id="fb-root"></div>
		<form action="<?php echo $action?>" method="POST" name="facebookloginform">
		<input type="hidden" name="login" value="Login &gt;&gt;&gt;"/>
		</form>

		<a class="userbase-fb-login" href="#" onclick="UserBaseFBLogin()"><span style="background-image: url(<?php echo UserConfig::$USERSROOTURL ?>/modules/facebook/facebook-sprite.png); background-position: 0px -22px; width: 198px; height: 22px; display: block; cursor: hand;" title="Login with Facebook Connect"></span></a>

		<script src="<?php echo UserConfig::$USERSROOTURL; ?>/modules/facebook/json2-min.js"></script>
		<script>
		var UserBaseFBLogin = function() { console.log('FB is not loaded yet') };

		window.fbAsyncInit = function() {
			// permissions required by this instance of UserBase
			var required_perms = <?php echo json_encode($this->permissions); ?>;
			var required_perms_string = <?php echo json_encode(implode(',', $this->permissions)); ?>;

			FB.init({
				appId  : '<?php echo $this->appID?>',
				session : <?php echo json_encode($session); ?>, // don't refetch the session when PHP already has it
				status : true, // check login status
				cookie : true // enable cookies to allow the server to access the session
			});

			UserBaseFBLogin = function() {
				console.log('loggin in');
				// here perms is just a comma-separated string
				FB.login(function(response) {
					if (response.session &&
						(required_perms == '' || response.perms == required_perms)
					) {
						document.facebookloginform.submit();
						return;
					}
				}, {perms: required_perms_string});
			};

			(function() {
				FB.getLoginStatus(function(response) {
					// getLoginStatus returns an array with 'extended' key or null
					if (response.session) {
						if (required_perms.length > 0) {
							// bug in API - it returns a serialized array
							if (typeof(response.perms) == 'string') {
								response.perms = JSON.parse(response.perms);
							}

							if (typeof(response.perms) == 'object'
								&& typeof(response.perms.extended) == 'object'
								&& (response.perms.extended instanceof Array)
							) {
								var i = required_perms.length;
								while (i--) {
									var ex = response.perms.extended;

									var j = ex.length;
									var found = false;
									while (j--) {
										if (required_perms[i] == ex[j]) {
											found = true;
											break;
										}
									}

									if (!found) {
										return;
									}
								}
							} else {
								return; // no permissions passed
							}
						}

						// override login function with simple form submit
						UserBaseFBLogin = function() {
							document.facebookloginform.submit();
						}
						return;
					} else {
						return;
					}
				}, {perms: required_perms_string});
			})(); // returning a function to run on login button click
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

		if (!$this->headersLoaded)
		{
			?>
			<script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php/en_US" type="text/javascript"></script><script type="text/javascript">FB.init("<?php echo $this->api_key?>", "<?php echo UserConfig::$USERSROOTURL; ?>/modules/facebook/xd_receiver.htm");</script>

			<form action="<?php echo $action?>" method="POST" name="facebookregform">
			<input type="hidden" name="register" value="Register &gt;&gt;&gt;"/>
			</form>
			<?php
			$this->headersLoaded = true;
		}
		?>
		<a href="#" onclick="FB.Connect.requireSession(function() {document.facebookregform.submit()}); return false;"><span style="background-image: url(<?php echo UserConfig::$USERSROOTURL ?>/modules/facebook/facebook-sprite.png); background-position: 0px 0px; width: 250px; height: 22px; display: block; cursor: hand;" title="Quick Sign-up using Facebook Connect"></span></a>
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
		?>
		<script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php/en_US" type="text/javascript"></script><script type="text/javascript">FB.init("<?php echo $this->api_key?>", "<?php echo UserConfig::$USERSROOTURL; ?>/modules/facebook/xd_receiver.htm");</script><?php
		if (is_null($user->getFacebookID())) {
			?>
			<a href="#" onclick="FB.Connect.requireSession(function() {document.facebookusereditform.submit()}); return false;"><span style="background-image: url(<?php echo UserConfig::$USERSROOTURL ?>/modules/facebook/facebook-sprite.png); background-position: 0px -44px; width: 230px; height: 22px; display: block; cursor: hand;" title="Connect to your Facebook account"></span></a>
			<form action="<?php echo $action?>" method="POST" name="facebookusereditform">
			<input type="hidden" name="save" value="Save &gt;&gt;&gt;"/>
			</form>
			<?php
		}
		else
		{
		?>
			<table><tr>
			<td rowspan="2"><fb:profile-pic uid="<?php echo $user->getFacebookID(); ?>" linked="true" size="square" facebook-logo="true" linked="true"/></td>
			<td><fb:name uid="<?php echo $user->getFacebookID(); ?>" useyou="false"/></td>
			</tr><tr>
			<td>
			<form action="<?php echo $action?>" method="POST" name="facebookusereditform">
			<input type="hidden" name="save" value="Save &gt;&gt;&gt;"/>
			<input type="submit" name="remove" value="remove" style="font-size: xx-small"/>
			</form>
			</td>
			</tr></table>
		<?php
		}
	}

	public function processLogin($post_data, &$remember)
	{
		$remember = $this->remember; 

		$facebook = new Facebook(array(
			'appId'  => $this->appID,
			'secret' => $this->secret,
			'cookie' => true, // enable optional cookie support
		));

		$session = $facebook->getSession();

		if (!$session) {
			return null;
		}

		try {
			$fbuser = $facebook->getUser();
		} catch (FacebookApiException $e) {
			error_log("Can't get Facebook user");
			return null;
		}

		$user = User::getUserByFacebookID($fbuser);

		if (!is_null($user)) {
			$user->recordActivity(USERBASE_ACTIVITY_LOGIN_FB);
			return $user;
		} else {
			return $this->processRegistration($post_data, $remember);
		}
	}

	public function processRegistration($post_data, &$remember)
	{
		$remember = $this->remember;

		$facebook = new Facebook(array(
			'appId'  => $this->appID,
			'secret' => $this->secret,
			'cookie' => true, // enable optional cookie support
		));

		$session = $facebook->getSession();
		if (!$session) {
			return null;
		}

		try {
			$fbuser = $facebook->getUser();
		} catch (FacebookApiException $e) {
			error_log("Can't get Facebook user");
			return null;
		}

		$errors = array();
		if (is_int($fbuser)) {
			$errors['fbuserid'][] = 'No Facebook id is passed';
			throw new InputValidationException('No facebook user id', 0, $errors);
		}

		// checking if user with this Facebook ID already exists and if so, then logs them in
		$existing_user = User::getUserByFacebookID($fbuser);
		if (!is_null($existing_user))
		{
			$existing_user->recordActivity(USERBASE_ACTIVITY_LOGIN_FB);
			return $existing_user;
		}

		try {
			$me = $facebook->api('/me');
		} catch (FacebookApiException $e) {
			error_log("Can't get /me API data");
			return null;
		}

		if (array_key_exists('first_name', $me)	&& array_key_exists('last_name', $me))
		{
			$name = $me['first_name'].' '.$me['last_name'];
		}
		else
		{
			$errors['username'][] = "User doesn't have first and last name";
		}

		if (count($errors) > 0)
		{
			throw new ExistingUserException('User already exists', 0, $errors);
		}

		// ok, let's create a user
		$user = User::createNewFacebookUser($name, $fbuser, $me);
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

			return true;
		}

		$errors = array();

		$facebook = new Facebook($this->api_key, $this->secret);
		$fbuser = $facebook->require_login();

		$errors = array();
		if (is_int($fbuser)) {
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
		$user->save();

		$user->recordActivity(USERBASE_ACTIVITY_ADDED_FB);

		return true;
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
