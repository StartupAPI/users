<?
require_once(dirname(__FILE__).'/client/facebook.php');

class FacebookAuthenticationModule implements IAuthenticationModule
{
	private $api_key;
	private $secret;

	private $headersLoaded = false;

	public function __construct($api_key, $secret)
	{
		$this->setKeys($api_key, $secret);
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

	public function getTitle()
	{
		return "Facebook";
	}

	public function renderLoginForm($action)
	{
		?>
		<script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php/en_US" type="text/javascript"></script><script type="text/javascript">FB.init("<?=$this->api_key?>", "<?=UserConfig::$USERSROOTURL; ?>/modules/facebook/xd_receiver.htm");</script>

		<form action="<?=$action?>" method="POST" name="facebookloginform">
		<input type="hidden" name="login" value="Login &gt;&gt;&gt;"/>
		</form>

		<a href="#" onclick="FB.Connect.requireSession(function() {document.facebookloginform.submit()}); return false;"><span style="background-image: url(<?=UserConfig::$USERSROOTURL ?>/modules/facebook/facebook-sprite.png); background-position: 0px -22px; width: 198px; height: 22px; display: block; cursor: hand;" title="Login with Facebook Connect"></span></a>
		<?
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
			<script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php/en_US" type="text/javascript"></script><script type="text/javascript">FB.init("<?=$this->api_key?>", "<?=UserConfig::$USERSROOTURL; ?>/modules/facebook/xd_receiver.htm");</script>

			<form action="<?=$action?>" method="POST" name="facebookregform">
			<input type="hidden" name="register" value="Register &gt;&gt;&gt;"/>
			</form>
			<?
			$this->headersLoaded = true;
		}
		?>
		<a href="#" onclick="FB.Connect.requireSession(function() {document.facebookregform.submit()}); return false;"><span style="background-image: url(<?=UserConfig::$USERSROOTURL ?>/modules/facebook/facebook-sprite.png); background-position: 0px 0px; width: 250px; height: 22px; display: block; cursor: hand;" title="Quick Sign-up using Facebook Connect"></span></a>
		<?
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
		<script src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php/en_US" type="text/javascript"></script><script type="text/javascript">FB.init("<?=$this->api_key?>", "<?=UserConfig::$USERSROOTURL; ?>/modules/facebook/xd_receiver.htm");</script><?
		if (is_null($user->getFacebookID())) {
			?>
			<a href="#" onclick="FB.Connect.requireSession(function() {document.facebookusereditform.submit()}); return false;"><span style="background-image: url(<?=UserConfig::$USERSROOTURL ?>/modules/facebook/facebook-sprite.png); background-position: 0px -44px; width: 230px; height: 22px; display: block; cursor: hand;" title="Connect to your Facebook account"></span></a>
			<form action="<?=$action?>" method="POST" name="facebookusereditform">
			<input type="hidden" name="save" value="Save &gt;&gt;&gt;"/>
			</form>
			<?
		}
		else
		{
		?>
			<table><tr>
			<td rowspan="2"><fb:profile-pic uid="<?=$user->getFacebookID(); ?>" linked="true" size="square" facebook-logo="true" linked="true"/></td>
			<td><fb:name uid="<?=$user->getFacebookID(); ?>" useyou="false"/></td>
			</tr><tr>
			<td>
			<form action="<?=$action?>" method="POST" name="facebookusereditform">
			<input type="hidden" name="save" value="Save &gt;&gt;&gt;"/>
			<input type="submit" name="remove" value="remove" style="font-size: xx-small"/>
			</form>
			</td>
			</tr></table>
		<?
		}
	}

	public function processLogin($post_data)
	{
		$facebook = new Facebook($this->api_key, $this->secret);
		$fbuser = $facebook->require_login();

		$user = User::getUserByFacebookID($fbuser);

		if (!is_null($user)) {
			return $user;
		} else {
			return $this->processRegistration($post_data);
		}
	}

	public function processRegistration($post_data)
	{
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
		return User::createNewFacebookUser($name, $fbuser);
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

		return true;
	}
}
