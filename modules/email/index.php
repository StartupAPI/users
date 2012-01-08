<?php
class EmailAuthenticationModule extends AuthenticationModule
{
	public function getID()
	{
		return "email";
	}

	public function getLegendColor()
	{
		return "f7dc67";
	}

	public function getTitle()
	{
		return "Email";
	}

	public function getUserCredentials($user)
	{
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('SELECT email FROM '.UserConfig::$mysql_prefix.'users WHERE id = ?'))
		{
			if (!$stmt->bind_param('i', $userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($email))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();

			if (!is_null($email))
			{
				return new EmailUserCredentials($email);
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

		if ($stmt = $db->prepare('SELECT count(*) AS conns FROM '.UserConfig::$mysql_prefix.'users WHERE email IS NOT NULL'))
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

	public function renderLoginForm($action)
	{
		?>
		<style>
		#userbase-email-login-form {
			font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
			padding: 0.4em 1em;
			margin: 0;
			width: 600px;
			border: 4px solid #ccc;
			border-radius: 7px;
			-moz-border-radius: 7px;
			-webkit-border-radius: 7px;
		}

		#userbase-email-login-form li {
			font-size: 1.2em;
			line-height: 1.5;

			clear: both;
			margin: 0 0 .75em;
			padding: 0;
		}

		#userbase-email-login-form fieldset {
			border: 0;
			padding: 0;
			margin: 0;
		}

		#userbase-email-login-form legend {
			border: 0;
			padding: 0;
			margin: 0;
			font-size: 1.8em;
			line-height: 1.8;
			padding-bottom: .6em;
		}

		#userbase-email-login-form ul {
			list-style: none;
			margin: 0;
			padding: 0;
		}

		#userbase-email-login-form label {
			display: block;
			float: left;
			line-height: 1.6;
			margin-right: 10px;
			text-align: right;
			width: 110px;
			padding: 3px 0;
		}

		#userbase-email-login-form label:after {
			content: ':';
		}

		#userbase-email-login-button {
			margin-left: 125px;
			padding: 0.3em 25px;
			cursor: pointer;
		}

		#userbase-email-login-form input {
			background: #f6f6f6;
			border: 2px solid #888;
			border-radius: 2px;
			-moz-border-radius: 2px;
			-webkit-border-radius: 2px;
			padding: 4px;
		}

		#userbase-email-login-form input:focus {
			background: #fff;
		}

		#userbase-email-login-form abbr {
			color: #f74d3d;
			font-weight: bold;
			cursor: help;
		}
		</style>
		<form id="userbase-email-login-form" action="<?php echo $action?>" method="POST">
		<fieldset>
		<legend>Enter your email address to re-send login link</legend>
		<ul>
		<li><label for="userbase-email-login-email">Email</label><input id="userbase-email-login-email" name="email" type="text" size="40"/><?php echo array_key_exists('email', $errors) ? ' <abbr title="'.UserTools::escape(implode("\n", $errors['email'])).'">*</abbr>' : ''?></li>
		<li><button id="userbase-email-login-button" type="submit" name="login">Re-send login link</button><?php if (UserConfig::$enableRegistration) {?> <a href="<?php echo UserConfig::$USERSROOTURL?>/register.php">or register</a><?php } ?></li>
		</ul>
		</fieldset>
		</form>
		<?php
		
	}

	public function renderRegistrationForm($full = false, $action = null, $errors = null, $data = null)
	{
		?>
		<style>
		#userbase-email-signup-form {
			font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
			padding: 0.4em 1em;
			margin: 0;
			width: 600px;
			border: 4px solid #ccc;
			border-radius: 7px;
			-moz-border-radius: 7px;
			-webkit-border-radius: 7px;
		}

		#userbase-email-signup-form li {
			font-size: 1.2em;
			line-height: 1.5;

			clear: both;
			margin: 0 0 .75em;
			padding: 0;
		}

		#userbase-email-signup-form fieldset {
			border: 0;
			padding: 0;
			margin: 0;
		}

		#userbase-email-signup-form legend {
			border: 0;
			padding: 0;
			margin: 0;
			font-size: 1.8em;
			line-height: 1.8;
			padding-bottom: .6em;
		}

		#userbase-email-signup-form ul {
			list-style: none;
			margin: 0;
			padding: 0;
		}

		#userbase-email-signup-form label {
			display: block;
			float: left;
			line-height: 1.6;
			margin-right: 10px;
			text-align: right;
			width: 110px;
			padding: 3px 0;
		}

		#userbase-email-signup-form label:after {
			content: ':';
		}

		#userbase-email-signup-button {
			margin-left: 125px;
			padding: 0.3em 25px;
			cursor: pointer;
		}

		#userbase-email-signup-form input {
			background: #f6f6f6;
			border: 2px solid #888;
			border-radius: 2px;
			-moz-border-radius: 2px;
			-webkit-border-radius: 2px;
			padding: 4px;
		}

		#userbase-email-signup-form input:focus {
			background: #fff;
		}

		#userbase-email-signup-form abbr {
			color: #f74d3d;
			font-weight: bold;
			cursor: help;
		}
		</style>
		<form id="userbase-email-signup-form" action="<?php echo $action?>" method="POST">
		<fieldset>
		<legend>Enter your name and email address to sign up</legend>
		<ul>
		<li><label for="userbase-email-register-name">Name</label><input id="userbase-email-register-name" name="name" type="test" size="40" value="<?php echo array_key_exists('name', $data) ? UserTools::escape($data['name']) : ''?>"/><?php echo array_key_exists('name', $errors) ? ' <abbr title="'.UserTools::escape(implode("\n", $errors['name'])).'">*</abbr>' : ''?></li>
		<li><label for="userbase-email-signup-email">Email</label><input id="userbase-email-signup-email" name="email" type="text" size="40"/><?php echo array_key_exists('email', $errors) ? ' <abbr title="'.UserTools::escape(implode("\n", $errors['email'])).'">*</abbr>' : ''?></li>
		<li><button id="userbase-email-signup-button" type="submit" name="register">Sign up</button> <a href="<?php echo UserConfig::$USERSROOTURL?>/login.php">or re-send login link</a></li>
		</ul>
		</fieldset>
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
		?>
		<style>
		#userbase-email-edit-form {
			font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
			padding: 0.4em 1em;
			margin: 0;
			width: 520px;
			border: 4px solid #ccc;
			border-radius: 7px;
			-moz-border-radius: 7px;
			-webkit-border-radius: 7px;
		}

		#userbase-email-edit-form li {
			font-size: 1.2em;
			line-height: 1.5;

			clear: both;
			margin: 0 0 .75em;
			padding: 0;
		}

		#userbase-email-edit-form fieldset {
			border: 0;
			padding: 0;
			margin: 0;
		}

		#userbase-email-edit-form legend {
			border: 0;
			padding: 0;
			margin: 0;
			font-size: 1.8em;
			line-height: 1.8;
			padding-bottom: .6em;
		}

		#userbase-email-edit-form ul {
			list-style: none;
			margin: 0;
			padding: 0;
		}

		#userbase-email-edit-form label {
			display: block;
			float: left;
			line-height: 1.6;
			margin-right: 10px;
			text-align: right;
			width: 165px;
			padding: 3px 0;
		}

		#userbase-email-edit-form label:after {
			content: ':';
		}

		#userbase-email-edit-button {
			margin-left: 180px;
			padding: 0.3em 25px;
			cursor: pointer;
		}

		#userbase-email-edit-forgotpass {
			margin-left: 130px;
                        cursor: pointer;
			font-size: 0.6em;
			display: block;
		}

		#userbase-email-edit-form input {
			background: #f6f6f6;
			border: 2px solid #888;
			border-radius: 2px;
			-moz-border-radius: 2px;
			-webkit-border-radius: 2px;
			padding: 4px;
		}

		#userbase-email-edit-form input:focus {
			background: #fff;
		}

		#userbase-email-edit-form abbr {
			cursor: help;
			font-style: normal;
			border: 0;
			color: red;
			font-size: 1.2em;
			font-weight: bold;
		}

		#userbase-email-edit-form .userbase-email-edit-section {
			font-size: 1.5em;
			font-weight: bold;
			margin-top: 1em;
		}
		</style>
		<form id="userbase-email-edit-form" action="<?php echo $action?>" method="POST">
		<fieldset>
		<legend>Update your name and email</legend>
		<ul>
		<li class="userbase-email-edit-section">Name and email</li>
		<li><label>Name</label><input name="name" type="test" size="40" value="<?php echo UserTools::escape(array_key_exists('name', $data) ? $data['name'] : $user->getName())?>"/><?php echo array_key_exists('name', $errors) ? ' <span style="color:red" title="'.UserTools::escape(implode("\n", $errors['name'])).'">*</span>' : ''?></li>
		<li><label>E-mail</label><input name="email" type="text" size="40" value="<?php echo UserTools::escape(array_key_exists('email', $data) ? $data['email'] : $user->getEmail())?>"/><?php echo array_key_exists('email', $errors) ? ' <span style="color:red" title="'.UserTools::escape(implode("\n", $errors['email'])).'">*</span>' : ''?></li>
		</ul>
		</fieldset>
		<?php UserTools::renderCSRFNonce(); ?>
		</form>
		<?php
	}

	public function processLogin($data, &$remember)
	{
		$user = User::getUserByUsernameOrEmail($data['email']);

		header('Location: '.UserConfig::$USERSROOTURL.'/modules/email/login.php?email='.urlencode($data['email']));
		exit;

		return null; // kind-of pointless, but indicates that you can' just login using email
	}

	public function processLoginLink($email, $code)
	{
		$errors = array();

		if ($email)
		{
			$email = trim(mb_convert_encoding($email, 'UTF-8'));
			if (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE)
			{
				$errors['email'][] = 'Invalid email address';
			}
		}
		else
		{
			$errors['email'][] = 'No email specified';
		}

		if (count($errors) > 0)
		{
			throw new InputValidationException('Validation failed', 0, $errors);
		}

		return null;
	}

	public function processRegistration($data, &$remember)
	{
		$errors = array();

		if (array_key_exists('name', $data))
		{
			$name = trim(mb_convert_encoding($data['name'], 'UTF-8'));
			if ($name == '')
			{
				$errors['name'][] = "Name can't be empty";
			}
		}
		else
		{
			$errors['name'][] = 'No name specified';
		}

		if (array_key_exists('email', $data))
		{
			$email = trim(mb_convert_encoding($data['email'], 'UTF-8'));
			if (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE)
			{
				$errors['email'][] = 'Invalid email address';
			}
		}
		else
		{
			$errors['email'][] = 'No email specified';
		}

		if (count($errors) > 0)
		{
			throw new InputValidationException('Validation failed', 0, $errors);
		}

		if (count(User::getUsersByEmailOrUsername($email)) > 0 ) {
			$errors['email'][] = "This email is already used by another user, please enter another email address.";
		}

		if (count($errors) > 0)
		{
			throw new ExistingUserException('User already exists', 0, $errors);
		}

		// ok, let's create a user
		$user = User::createNewWithoutCredentials($name, $email);
		$user->recordActivity(USERBASE_ACTIVITY_REGISTER_EMAIL);
//		$user->sendConfirmationEmail();
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
		$errors = array();

		$has_username = !is_null($user->getUsername());

		// don't change password if username was already set and no password fields are edited
		$changepass = false;

		// Force password setup when user sets username for the first time
		if (!$has_username)
		{
			$changepass = true;
		}
		else if (array_key_exists('currentpass', $data) &&
			array_key_exists('pass', $data) &&
			array_key_exists('repeatpass', $data) &&
			($data['currentpass'] != '' || $data['pass'] != '' || $data['repeatpass'] != ''))
		{
			$changepass = true;

			if (!$user->checkPass($data['currentpass']))
			{
				$errors['currentpass'][] = 'You entered wrong current password';
			}
		}

		if ($changepass)
		{
			// both passwords must be passed and non-empty
			if (array_key_exists('pass', $data) && array_key_exists('repeatpass', $data) &&
					($data['pass'] != '' || $data['repeatpass'] != '')
				)
			{
				if (strlen($data['pass']) < 6)
				{
					$errors['pass'][] = 'Passwords must be at least 6 characters long';
				}

				if ($data['pass'] !== $data['repeatpass'])
				{
					$errors['repeatpass'][] = 'Passwords don\'t match';
				}
			}
			else
			{
				if ($has_username)
				{
					$errors['pass'][] = 'You must specify new password';
				}
				else
				{
					$errors['pass'][] = 'You must set password when setting username and email';
				}
			}
		}

		// only validate username if user didn't specify it yet
		if (!$has_username)
		{
			if (array_key_exists('username', $data))
			{
				$username = strtolower(trim(mb_convert_encoding($data['username'], 'UTF-8')));

				if (strlen($username) < 2)
				{
					$errors['username'][] = 'Username must be at least 2 characters long';
				}

				if (strlen($username) > 25)
				{
					$errors['username'][] = 'Username must be no more then 25 characters long';
				}

				if (preg_match('/^[a-z][a-z0-9.]*[a-z0-9]$/', $username) !== 1)
				{
					$errors['username'][] = "Username must start with the letter and contain only latin letters, digits or '.' symbols";
				}
			}
			else
			{
				$errors['username'][] = "No username passed";
			}
		}

		if (array_key_exists('name', $data))
		{
			$name = trim(mb_convert_encoding($data['name'], 'UTF-8'));
			if ($name == '')
			{
				$errors['name'][] = "Name can't be empty";
			}
		}
		else
		{
			$errors['name'][] = 'No name specified';
		}

		if (array_key_exists('email', $data))
		{
			$email = trim(mb_convert_encoding($data['email'], 'UTF-8'));
			if (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE)
			{
				$errors['email'][] = 'Invalid email address';
			}
		}
		else
		{
			$errors['email'][] = 'No email specified';
		}

		if (!$has_username)
		{
			$existing_users = User::getUsersByEmailOrUsername($username);
			if (!array_key_exists('username', $errors) &&
				(count($existing_users) > 0 && !$existing_users[0]->isTheSameAs($user))
			) {
				$errors['username'][] = "This username is already used, please pick another one";
			}
		}

		$existing_users = User::getUsersByEmailOrUsername($email);
		if (!array_key_exists('email', $errors) &&
			(count($existing_users) > 0 && !$existing_users[0]->isTheSameAs($user))
		) {
			$errors['email'][] = "This email is already used by another user, please enter another email address.";
		}

		if (count($errors) > 0)
		{
			throw new InputValidationException('Validation failed', 0, $errors);
		}

		if ($changepass)
		{
			$user->setPass($data['pass']);
			if ($has_username) {
				$user->recordActivity(USERBASE_ACTIVITY_UPDATEPASS);
			}
		}

		if (!$has_username)
		{
			$user->setUsername($username);
			$user->recordActivity(USERBASE_ACTIVITY_ADDED_UPASS);
		}

		$user->setName($name);
		$user->setEmail($email);
		$user->save();

		$user->recordActivity(USERBASE_ACTIVITY_UPDATEUSERINFO);

		return true;
	}

	/*
	 * Updates user's password
	 *
	 * returns true if successful and false if unsuccessful
	 *
	 * throws InputValidationException if there are problems with input data
	 */
	public function processUpdatePassword($user, $data)
	{
		$errors = array();

		if (array_key_exists('pass', $data) ||
			array_key_exists('repeatpass', $data))
		{
			if (array_key_exists('pass', $data) && array_key_exists('repeatpass', $data) && $data['pass'] !== $data['repeatpass'])
			{
				$errors['repeatpass'] = 'Passwords don\'t match';
			}

			if (array_key_exists('pass', $data) && strlen($data['pass']) < 6)
			{
				$errors['pass'] = 'Passwords must be at least 6 characters long';
			}
		}
		else
		{
			$errors['pass'] = 'Passwords must be specified';
		}

		if (count($errors) > 0)
		{
			throw new InputValidationException('Validation failed', 0, $errors);
		}

		$user->setPass($data['pass']);
		$user->setRequiresPasswordReset(false);
		$user->save();

		$user->resetTemporaryPassword();

		$user->recordActivity(USERBASE_ACTIVITY_RESETPASS);

		return true;
	}

	// THIS SHOULD ONLY BE SET ON PASSWORD RESET PAGE
	// SETTING THIS ON OTHER PAGES CAN RESULT IN SECURITY BREACH
	public static $IGNORE_PASSWORD_RESET = false;
}

class UsernamePassUserCredentials extends UserCredentials {
	private $username;

	public function __construct($username) {
		$this->username = $username;
	}

	public function getUsername() {
		return $this->username;
	}

	public function getHTML() {
		return $this->username;
	}
}
