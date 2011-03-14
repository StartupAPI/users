<?php
require_once(dirname(__FILE__).'/client/facebook.php');

class FacebookAuthenticationModule implements IAuthenticationModule
{
	private $api_key;
	private $secret;
	private $remember;

	private $headersLoaded = false;

	public function __construct($api_key, $secret, $remember = true)
	{
		$this->setKeys($api_key, $secret);

		// TODO Replace it with immediate FB Connect call:
		// http://code.google.com/p/userbase/issues/detail?id=16
		$this->remember = $remember;
	}

	public function setKeys($api_key, $secret)
	{
		$this->api_key = $api_key;
		$this->secret = $secret;
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
				return "<a href=\"http://www.facebook.com/profile.php?id=$fb_id\">$fb_id</a>";
			}
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return null;
	}

	/*
	 * retrieves recent aggregated registrations numbers 
	 */
	public function getRecentRegistrations()
	{
		$db = UserConfig::getDB();

		$regs = 0;

		if ($stmt = $db->prepare('SELECT count(*) AS regs FROM '.UserConfig::$mysql_prefix.'users WHERE fb_id IS NOT NULL AND regtime > DATE_SUB(NOW(), INTERVAL 30 DAY)'))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($regs))
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

		return $regs;
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
		?>
		<script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php/en_US" type="text/javascript"></script><script type="text/javascript">FB.init("<?php echo $this->api_key?>", "<?php echo UserConfig::$USERSROOTURL; ?>/modules/facebook/xd_receiver.htm");</script>

		<form action="<?php echo $action?>" method="POST" name="facebookloginform">
		<input type="hidden" name="login" value="Login &gt;&gt;&gt;"/>
		</form>

		<a href="#" onclick="FB.Connect.requireSession(function() {document.facebookloginform.submit()}); return false;"><span style="background-image: url(<?php echo UserConfig::$USERSROOTURL ?>/modules/facebook/facebook-sprite.png); background-position: 0px -22px; width: 198px; height: 22px; display: block; cursor: hand;" title="Login with Facebook Connect"></span></a>
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

		$facebook = new Facebook($this->api_key, $this->secret);
		$fbuser = $facebook->require_login();

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

		$facebook = new Facebook($this->api_key, $this->secret);
		$fbuser = $facebook->require_login();

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

		$data = $facebook->api_client->users_getInfo($fbuser, 'last_name, first_name');

		if (count($data) > 0
			&& array_key_exists('first_name', $data[0])
			&& array_key_exists('last_name', $data[0]))
		{
			$name = $data[0]['first_name'].' '.$data[0]['last_name'];
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
		$user = User::createNewFacebookUser($name, $fbuser);
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
