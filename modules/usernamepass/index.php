<?php
class UsernamePasswordAuthenticationModule extends AuthenticationModule
{
	public function getID()
	{
		return "userpass";
	}

	public function getLegendColor()
	{
		return "a3a3a3";
	}

	public function getTitle()
	{
		return "Username / Password";
	}

	public function getUserCredentials($user)
	{
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('SELECT username FROM '.UserConfig::$mysql_prefix.'users WHERE id = ?'))
		{
			if (!$stmt->bind_param('i', $userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($username))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();

			// if user used password recovery and remembered his old password
			// then clean temporary password and password reset flag
			// (don't reset the flag if was was set for some other reasons)
			if (!is_null($username))
			{
				return new UsernamePassUserCredentials($username);
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

		if ($stmt = $db->prepare('SELECT count(*) AS conns FROM '.UserConfig::$mysql_prefix.'users WHERE username IS NOT NULL'))
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

		if ($stmt = $db->prepare('SELECT CAST(regtime AS DATE) AS regdate, count(*) AS regs FROM '.UserConfig::$mysql_prefix.'users WHERE username IS NOT NULL GROUP BY regdate'))
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
		<style>
		#userbase-usernamepass-login-form {
			font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
			padding: 0.4em 1em;
			margin: 0;
			width: 480px;
			border: 4px solid #ccc;
			border-radius: 7px;
			-moz-border-radius: 7px;
			-webkit-border-radius: 7px;
		}

		#userbase-usernamepass-login-form li {
			font-size: 1.2em;
			line-height: 1.5;

			clear: both;
			margin: 0 0 .75em;
			padding: 0;
		}

		#userbase-usernamepass-login-form fieldset {
			border: 0;
			padding: 0;
			margin: 0;
		}

		#userbase-usernamepass-login-form legend {
			border: 0;
			padding: 0;
			margin: 0;
			font-size: 1.8em;
			line-height: 1.8;
			padding-bottom: .6em;
		}

		#userbase-usernamepass-login-form ul {
			list-style: none;
			margin: 0;
			padding: 0;
		}

		#userbase-usernamepass-login-form label {
			display: block;
			float: left;
			line-height: 1.6;
			margin-right: 10px;
			text-align: right;
			width: 110px;
			padding: 3px 0;
		}

		#userbase-usernamepass-login-form label:after {
			content: ':';
		}

		#userbase-usernamepass-login-button {
			margin-left: 125px;
			padding: 0.3em 25px;
			cursor: pointer;
		}

		#userbase-usernamepass-login-forgotpass {
			margin-left: 130px;
                        cursor: pointer;
			font-size: 0.6em;
			display: block;
		}

		#userbase-usernamepass-login-form input {
			background: #f6f6f6;
			border: 2px solid #888;
			border-radius: 2px;
			-moz-border-radius: 2px;
			-webkit-border-radius: 2px;
			padding: 4px;
		}

		#userbase-usernamepass-login-form input:focus {
			background: #fff;
		}

		#userbase-usernamepass-login-form .remember label {
			display: block;
			float: none;
			margin-left: 127px;
			text-align: left;
			width: 270px;
		}

		#userbase-usernamepass-login-form .remember input {
			border: 0;
			background: #fff;
		}

		#userbase-usernamepass-login-form .remember {
			margin-bottom: 0;
		}

		#userbase-usernamepass-login-form .remember label:after {
			content: ''
		}
		</style>
		<form id="userbase-usernamepass-login-form" action="<?php echo $action?>" method="POST">
		<fieldset>
		<legend>Enter your username and password to log in</legend>
		<ul>
		<li><label for="userbase-usernamepass-login-username">Username</label><input id="userbase-usernamepass-login-username" name="username" type="text" size="25" maxlength="25"/></li>
		<li><label for="userbase-usernamepass-login-password">Password</label><input id="userbase-usernamepass-login-password" name="pass" type="password" size="25" autocomplete="off"/><a id="userbase-usernamepass-login-forgotpass" href="<?php echo UserConfig::$USERSROOTURL?>/modules/usernamepass/forgotpassword.php">Forgot password?</a></li>
		<?php if (UserConfig::$allowRememberMe) {?><li class="remember"><label for="remember"><input type="checkbox" name="remember" value="yes" id="remember"<?php if (UserConfig::$rememberMeDefault) {?> checked<?php }?>/>remember me</label></li> <?php }?>
		<li><button id="userbase-usernamepass-login-button" type="submit" name="login">Log in</button><?php if (UserConfig::$enableRegistration) {?> <a href="<?php echo UserConfig::$USERSROOTURL?>/register.php">or register</a><?php } ?></li>
		</ul>
		</fieldset>
		</form>
		<?php
	}

	public function renderRegistrationForm($full = false, $action = null, $errors = null, $data = null)
	{
		?>
		<style>
		#userbase-usernamepass-register-form {
			font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
			padding: 0.4em 1em;
			margin: 0;
			width: 470px;
			border: 4px solid #ccc;
			border-radius: 7px;
			-moz-border-radius: 7px;
			-webkit-border-radius: 7px;
		}

		#userbase-usernamepass-register-form li {
			font-size: 1.2em;
			line-height: 1.5;

			clear: both;
			margin: 0 0 .75em;
			padding: 0;
		}

		#userbase-usernamepass-register-form fieldset {
			border: 0;
			padding: 0;
			margin: 0;
		}

		#userbase-usernamepass-register-form legend {
			border: 0;
			padding: 0;
			margin: 0;
			font-size: 1.8em;
			line-height: 1.8;
			padding-bottom: .6em;
		}

		#userbase-usernamepass-register-form ul {
			list-style: none;
			margin: 0;
			padding: 0;
		}

		#userbase-usernamepass-register-form label {
			display: block;
			float: left;
			line-height: 1.6;
			margin-right: 10px;
			text-align: right;
			width: 140px;
			padding: 3px 0;
		}

		#userbase-usernamepass-register-form label:after {
			content: ':';
		}

		#userbase-usernamepass-register-button {
			margin-left: 155px;
			padding: 0.3em 25px;
			cursor: pointer;
		}

		#userbase-usernamepass-register-forgotpass {
			margin-left: 130px;
                        cursor: pointer;
			font-size: 0.6em;
			display: block;
		}

		#userbase-usernamepass-register-form input {
			background: #f6f6f6;
			border: 2px solid #888;
			border-radius: 2px;
			-moz-border-radius: 2px;
			-webkit-border-radius: 2px;
			padding: 4px;
		}

		#userbase-usernamepass-register-form input:focus {
			background: #fff;
		}

		#userbase-usernamepass-register-form abbr {
			cursor: help;
			font-style: normal;
			border: 0;
			color: red;
			font-size: 1.2em;
			font-weight: bold;
		}
		</style>
		<form id="userbase-usernamepass-register-form" action="<?php echo $action?>" method="POST">
		<fieldset>
		<legend>Enter your information to create an account</legend>
		<ul>
		<li><label for="userbase-usernamepass-register-username">Username</label><input id="userbase-usernamepass-register-username" name="username" type="text" size="25" maxlength="25" value="<?php echo array_key_exists('username', $data) ? UserTools::escape($data['username']) : ''?>"/><?php echo array_key_exists('username', $errors) ? ' <abbr title="'.UserTools::escape(implode("\n", $errors['username'])).'">*</abbr>' : ''?></li>
		<li><label for="userbase-usernamepass-register-pass">Password</label><input id="userbase-usernamepass-register-pass" name="pass" type="password" size="25" autocomplete="off"/><?php echo array_key_exists('pass', $errors) ? ' <abbr title="'.UserTools::escape(implode("\n", $errors['pass'])).'">*</abbr>' : ''?></li>
		<li><label for="userbase-usernamepass-register-passrepeat">Repeat password</label><input id="userbase-usernamepass-register-passrepeat" name="repeatpass" type="password" size="25" autocomplete="off"/><?php echo array_key_exists('repeatpass', $errors) ? ' <abbr title="'.UserTools::escape(implode("\n", $errors['repeatpass'])).'">*</abbr>' : ''?></li>
		<li><label for="userbase-usernamepass-register-name">Name</label><input id="userbase-usernamepass-register-name" name="name" type="test" size="25" value="<?php echo array_key_exists('name', $data) ? UserTools::escape($data['name']) : ''?>"/><?php echo array_key_exists('name', $errors) ? ' <abbr title="'.UserTools::escape(implode("\n", $errors['name'])).'">*</abbr>' : ''?></li>
		<li><label for="userbase-usernamepass-register-email">E-mail</label><input id="userbase-usernamepass-register-email" name="email" type="text" size="25" value="<?php echo array_key_exists('email', $data) ? UserTools::escape($data['email']) : ''?>"/><?php echo array_key_exists('email', $errors) ? ' <abbr title="'.UserTools::escape(implode("\n", $errors['email'])).'">*</abbr>' : ''?></li>
		<li><button id="userbase-usernamepass-register-button" type="submit" name="register">Register</button> <a href="<?php echo UserConfig::$USERSROOTURL?>/login.php">or login here</a></li>
		</ul>
		</table>
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
		#userbase-usernamepass-edit-form {
			font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
			padding: 0.4em 1em;
			margin: 0;
			width: 520px;
			border: 4px solid #ccc;
			border-radius: 7px;
			-moz-border-radius: 7px;
			-webkit-border-radius: 7px;
		}

		#userbase-usernamepass-edit-form li {
			font-size: 1.2em;
			line-height: 1.5;

			clear: both;
			margin: 0 0 .75em;
			padding: 0;
		}

		#userbase-usernamepass-edit-form fieldset {
			border: 0;
			padding: 0;
			margin: 0;
		}

		#userbase-usernamepass-edit-form legend {
			border: 0;
			padding: 0;
			margin: 0;
			font-size: 1.8em;
			line-height: 1.8;
			padding-bottom: .6em;
		}

		#userbase-usernamepass-edit-form ul {
			list-style: none;
			margin: 0;
			padding: 0;
		}

		#userbase-usernamepass-edit-form label {
			display: block;
			float: left;
			line-height: 1.6;
			margin-right: 10px;
			text-align: right;
			width: 165px;
			padding: 3px 0;
		}

		#userbase-usernamepass-edit-form label:after {
			content: ':';
		}

		#userbase-usernamepass-edit-button {
			margin-left: 180px;
			padding: 0.3em 25px;
			cursor: pointer;
		}

		#userbase-usernamepass-edit-forgotpass {
			margin-left: 130px;
                        cursor: pointer;
			font-size: 0.6em;
			display: block;
		}

		#userbase-usernamepass-edit-form input {
			background: #f6f6f6;
			border: 2px solid #888;
			border-radius: 2px;
			-moz-border-radius: 2px;
			-webkit-border-radius: 2px;
			padding: 4px;
		}

		#userbase-usernamepass-edit-form input:focus {
			background: #fff;
		}

		#userbase-usernamepass-edit-form abbr {
			cursor: help;
			font-style: normal;
			border: 0;
			color: red;
			font-size: 1.2em;
			font-weight: bold;
		}

		#userbase-usernamepass-edit-form .userbase-usernamepass-edit-section {
			font-size: 1.5em;
			font-weight: bold;
			margin-top: 1em;
		}
		</style>
		<form id="userbase-usernamepass-edit-form" action="<?php echo $action?>" method="POST">
		<fieldset>
		<legend>Update your name, email and password</legend>
		<ul>
		<?php
		$username = $user->getUsername();

		if (is_null($username)) {
		?>
		<li><label>Username</label><input name="username" type="text" size="25" maxlength="25" value="<?php echo array_key_exists('username', $data) ? UserTools::escape($data['username']) : ''?>"/><?php echo array_key_exists('username', $errors) ? ' <span style="color:red" title="'.UserTools::escape(implode("\n", $errors['username'])).'">*</span>' : ''?></li>
		<?php }
		else
		{?>
		<li><label>Username</label><b title="Sorry, you can't change your username">&nbsp;<?php echo UserTools::escape($username)?></b></li>
		<?php }?>
		<li class="userbase-usernamepass-edit-section">Name and email</li>
		<li><label>Name</label><input name="name" type="test" size="40" value="<?php echo UserTools::escape(array_key_exists('name', $data) ? $data['name'] : $user->getName())?>"/><?php echo array_key_exists('name', $errors) ? ' <span style="color:red" title="'.UserTools::escape(implode("\n", $errors['name'])).'">*</span>' : ''?></li>
		<li><label>E-mail</label><input name="email" type="text" size="40" value="<?php echo UserTools::escape(array_key_exists('email', $data) ? $data['email'] : $user->getEmail())?>"/><?php echo array_key_exists('email', $errors) ? ' <span style="color:red" title="'.UserTools::escape(implode("\n", $errors['email'])).'">*</span>' : ''?></li>

		<li class="userbase-usernamepass-edit-section">Change password</li>
		<?php if (!is_null($user->getUsername())) {?>
		<li><label>Current password</label><input name="currentpass" type="password" size="25" autocomplete="off"/><?php echo array_key_exists('currentpass', $errors) ? ' <span style="color:red" title="'.UserTools::escape(implode("\n", $errors['currentpass'])).'">*</span>' : ''?></li>
		<?php } ?>
		<li><label><?php if (is_null($user->getUsername())) {?>Set a<?php } else {?>New<?php } ?> password</label><input name="pass" type="password" size="25" autocomplete="off"/><?php echo array_key_exists('pass', $errors) ? ' <span style="color:red" title="'.UserTools::escape(implode("\n", $errors['pass'])).'">*</span>' : ''?></li>
		<li><label>Repeat new password</label><input name="repeatpass" type="password" size="25" autocomplete="off"/><?php array_key_exists('repeatpass', $errors) ? ' <span style="color:red" title="'.UserTools::escape(implode("\n", $errors['repeatpass'])).'">*</span>' : ''?></li>
		<li><button id="userbase-usernamepass-edit-button" type="submit" name="save">Save</button></li>
		</ul>
		</fieldset>
		</form>
		<?php
	}

	public function processLogin($data, &$remember)
	{
		$remember = UserConfig::$allowRememberMe && array_key_exists('remember', $data);

		$db = UserConfig::getDB();

		$user = null;

		if ($stmt = $db->prepare('SELECT id, name, username, email, pass, salt, temppass, requirespassreset, fb_id FROM '.UserConfig::$mysql_prefix.'users WHERE username = ?'))
		{
			if (!$stmt->bind_param('s', $data['username']))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($id, $name, $username, $email, $pass, $salt, $temppass, $requirespassreset, $fb_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			if ($stmt->fetch() === TRUE)
			{
				if (sha1($salt.$data['pass']) == $pass)
				{
					$user = new User($id, $name, $username, $email, $requirespassreset, $fb_id);

				}
			}

			$stmt->close();

			// if user used password recovery and remembered his old password
			// then clean temporary password and password reset flag
			// (don't reset the flag if was was set for some other reasons)
			if (!is_null($user) && !is_null($temppass) && $user->requiresPasswordReset())
			{
				$user->setRequiresPasswordReset(false);
				$user->save();

				$user->resetTemporaryPassword();
			}
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		if (is_null($user))
		{
			if ($stmt = $db->prepare('SELECT id, name, username, email, fb_id FROM '.UserConfig::$mysql_prefix.'users WHERE username = ? AND temppass = ? AND temppasstime > DATE_SUB(NOW(), INTERVAL 1 DAY)'))
			{
				if (!$stmt->bind_param('ss', $data['username'], $data['pass']))
				{
					 throw new Exception("Can't bind parameter".$stmt->error);
				}
				if (!$stmt->execute())
				{
					throw new Exception("Can't execute statement: ".$stmt->error);
				}
				if (!$stmt->bind_result($id, $name, $username, $email, $fb_id))
				{
					throw new Exception("Can't bind result: ".$stmt->error);
				}

				if ($stmt->fetch() === TRUE)
				{
					$user = new User($id, $name, $username, $email, null, $fb_id);
				}

				$stmt->close();

				if (!is_null($user))
				{
					$user->setRequiresPasswordReset(true);
					$user->save();
				}
			}
			else
			{
				throw new Exception("Can't prepare statement: ".$db->error);
			}
		}
		else
		{
			$user->resetTemporaryPassword();
		}

		if (!is_null($user))
		{
			$user->recordActivity(USERBASE_ACTIVITY_LOGIN_UPASS);
		}
		return $user;
	}

	public function processRegistration($data, &$remember)
	{
		$remember = UserConfig::$allowRememberMe && UserConfig::$rememberUserOnRegistration;

		$errors = array();
		if (array_key_exists('pass', $data) && array_key_exists('repeatpass', $data) && $data['pass'] !== $data['repeatpass'])
		{
			$errors['repeatpass'][] = 'Passwords don\'t match';
		}

		if (array_key_exists('pass', $data) && strlen($data['pass']) < 6)
		{
			$errors['pass'][] = 'Passwords must be at least 6 characters long';
		}

		if (array_key_exists('username', $data))
		{
			$username = strtolower(trim($data['username']));

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

		if (array_key_exists('name', $data))
		{
			$name = trim($data['name']);
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
			$email = trim($data['email']);
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

		if (count(User::getUsersByEmailOrUsername($username)) > 0 ) {
			$errors['username'][] = "This username is already used, please pick another one";
		}
		if (count(User::getUsersByEmailOrUsername($email)) > 0 ) {
			$errors['email'][] = "This email is already used by another user, please enter another email address.";
		}

		if (count($errors) > 0)
		{
			throw new ExistingUserException('User already exists', 0, $errors);
		}

		// ok, let's create a user
		$user = User::createNew($name, $username, $email, $data['pass']);
		$user->recordActivity(USERBASE_ACTIVITY_REGISTER_UPASS);
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
				$username = strtolower(trim($data['username']));

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
			$name = trim($data['name']);
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
			$email = trim($data['email']);
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
