<?php
/**
 * Basic authentication module using username and password
 *
 * Registers users with their username, password, name and email address
 *
 * This is the module that is enabled by default in user_config.sample.php
 * because it requires not configuration.
 *
 * @package StartupAPI
 * @subpackage Authentication\UsernamePassword
 */
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
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($username))
			{
				throw new DBBindResultException($db, $stmt);
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
			throw new DBPrepareStmtException($db);
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
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($regdate, $regs))
			{
				throw new DBBindResultException($db, $stmt);
			}

			while($stmt->fetch() === TRUE)
			{
				$dailyregs[] = array('regdate' => $regdate, 'regs' => $regs);
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return $dailyregs;
	}

	public function renderLoginForm($action)
	{
		?>
		<form id="startupapi-usernamepass-login-form" action="<?php echo $action?>" method="POST">
		<fieldset>
		<legend>Enter your username and password to log in</legend>
		<ul>
		<li><label for="startupapi-usernamepass-login-username">Username</label><input id="startupapi-usernamepass-login-username" name="username" type="text" size="25" maxlength="25"/></li>
		<li><label for="startupapi-usernamepass-login-password">Password</label><input id="startupapi-usernamepass-login-password" name="pass" type="password" size="25" autocomplete="off"/><a id="startupapi-usernamepass-login-forgotpass" href="<?php echo UserConfig::$USERSROOTURL?>/modules/usernamepass/forgotpassword.php">Forgot password?</a></li>
		<?php if (UserConfig::$allowRememberMe) {?><li class="remember"><label for="remember"><input type="checkbox" name="remember" value="yes" id="remember"<?php if (UserConfig::$rememberMeDefault) {?> checked<?php }?>/>remember me</label></li> <?php }?>
		<li><button id="startupapi-usernamepass-login-button" type="submit" name="login">Log in</button><?php if (UserConfig::$enableRegistration) {?> <a href="<?php echo UserConfig::$USERSROOTURL?>/register.php">or register</a><?php } ?></li>
		</ul>
		</fieldset>
		</form>
		<?php
	}

	public function renderRegistrationForm($full = false, $action = null, $errors = null, $data = null)
	{
		?>
		<form id="startupapi-usernamepass-register-form" action="<?php echo $action?>" method="POST">
		<fieldset>
		<legend>Enter your information to create an account</legend>
		<ul>
		<li><label for="startupapi-usernamepass-register-username">Username</label><input id="startupapi-usernamepass-register-username" name="username" type="text" size="25" maxlength="25" value="<?php echo array_key_exists('username', $data) ? UserTools::escape($data['username']) : ''?>"/><?php echo array_key_exists('username', $errors) ? ' <abbr title="'.UserTools::escape(implode("\n", $errors['username'])).'">*</abbr>' : ''?></li>
		<li><label for="startupapi-usernamepass-register-pass">Password</label><input id="startupapi-usernamepass-register-pass" name="pass" type="password" size="25" autocomplete="off"/><?php echo array_key_exists('pass', $errors) ? ' <abbr title="'.UserTools::escape(implode("\n", $errors['pass'])).'">*</abbr>' : ''?></li>
		<li><label for="startupapi-usernamepass-register-passrepeat">Repeat password</label><input id="startupapi-usernamepass-register-passrepeat" name="repeatpass" type="password" size="25" autocomplete="off"/><?php echo array_key_exists('repeatpass', $errors) ? ' <abbr title="'.UserTools::escape(implode("\n", $errors['repeatpass'])).'">*</abbr>' : ''?></li>
		<li><label for="startupapi-usernamepass-register-name">Name</label><input id="startupapi-usernamepass-register-name" name="name" type="test" size="25" value="<?php echo array_key_exists('name', $data) ? UserTools::escape($data['name']) : ''?>"/><?php echo array_key_exists('name', $errors) ? ' <abbr title="'.UserTools::escape(implode("\n", $errors['name'])).'">*</abbr>' : ''?></li>
		<li><label for="startupapi-usernamepass-register-email">E-mail</label><input id="startupapi-usernamepass-register-email" name="email" type="email" size="25" value="<?php echo array_key_exists('email', $data) ? UserTools::escape($data['email']) : ''?>"/><?php echo array_key_exists('email', $errors) ? ' <abbr title="'.UserTools::escape(implode("\n", $errors['email'])).'">*</abbr>' : ''?></li>
		<li><button id="startupapi-usernamepass-register-button" type="submit" name="register">Register</button> <a href="<?php echo UserConfig::$USERSROOTURL?>/login.php">or login here</a></li>
		</ul>
		</form>
		</fieldset>
		<?php
	}

	public function renderEditUserForm($action, $errors, $user, $data)
	{
		?>
		<form id="startupapi-usernamepass-edit-form" action="<?php echo $action?>" method="POST">
		<fieldset>
		<legend>Update your name, email and password</legend>
		<ul>
		<?php
		$username = $user->getUsername();

		if (is_null($username)) {
		?>
		<li><label>Username</label><input name="username" type="text" size="25" maxlength="25" value="<?php echo array_key_exists('username', $data) ? UserTools::escape($data['username']) : ''?>"/><?php echo array_key_exists('username', $errors) ? ' <span class="startup-api-error-message" title="'.UserTools::escape(implode("\n", $errors['username'])).'">*</span>' : ''?></li>
		<?php }
		else
		{?>
		<li><label>Username</label><b title="Sorry, you can't change your username">&nbsp;<?php echo UserTools::escape($username)?></b></li>
		<?php }?>
		<li class="startupapi-usernamepass-edit-section">Name and email</li>
		<li><label>Name</label><input name="name" type="test" size="40" value="<?php echo UserTools::escape(array_key_exists('name', $data) ? $data['name'] : $user->getName())?>"/><?php echo array_key_exists('name', $errors) ? ' <span class="startup-api-error-message" title="'.UserTools::escape(implode("\n", $errors['name'])).'">*</span>' : ''?></li>
		<li><label>E-mail</label><input name="email" type="email" size="40" value="<?php echo UserTools::escape(array_key_exists('email', $data) ? $data['email'] : $user->getEmail())?>"/><?php echo array_key_exists('email', $errors) ? ' <span class="startup-api-error-message" title="'.UserTools::escape(implode("\n", $errors['email'])).'">*</span>' : ''?>
			<?php if (!$user->isEmailVerified()) { ?><a id="startupapi-usernamepass-edit-verify-email" href="<?php echo UserConfig::$USERSROOTURL ?>/verify_email.php">Email address not verified yet, click here to verify</a><?php } ?>
		</li>

		<li class="startupapi-usernamepass-edit-section">Change password</li>
		<?php if (!is_null($user->getUsername())) {?>
		<li><label>Current password</label><input name="currentpass" type="password" size="25" autocomplete="off"/><?php echo array_key_exists('currentpass', $errors) ? ' <span class="startup-api-error-message" title="'.UserTools::escape(implode("\n", $errors['currentpass'])).'">*</span>' : ''?></li>
		<?php } ?>
		<li><label><?php if (is_null($user->getUsername())) {?>Set a<?php } else {?>New<?php } ?> password</label><input name="pass" type="password" size="25" autocomplete="off"/><?php echo array_key_exists('pass', $errors) ? ' <span class="startup-api-error-message" title="'.UserTools::escape(implode("\n", $errors['pass'])).'">*</span>' : ''?></li>
		<li><label>Repeat new password</label><input name="repeatpass" type="password" size="25" autocomplete="off"/><?php array_key_exists('repeatpass', $errors) ? ' <span class="startup-api-error-message" title="'.UserTools::escape(implode("\n", $errors['repeatpass'])).'">*</span>' : ''?></li>
		<li><button id="startupapi-usernamepass-edit-button" type="submit" name="save">Save</button></li>
		</ul>
		</fieldset>
		<?php UserTools::renderCSRFNonce(); ?>
		</form>
		<?php
	}

	public function processLogin($data, &$remember)
	{
		$remember = UserConfig::$allowRememberMe && array_key_exists('remember', $data);

		$db = UserConfig::getDB();

		$user = User::getUserByUsernamePassword($data['username'], $data['pass']);

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

	/**
	 * Updates user's password
	 *
	 * @param User $user User object
	 * @param array $data Form data
	 *
	 * @return boolean True if password update was successful, false otherwise
	 *
	 * @throws InputValidationException
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

	/**
	 * Bypasses required password reset flag if set to true
	 *
	 * THIS SHOULD ONLY BE SET ON PASSWORD RESET PAGE
	 * SETTING THIS ON OTHER PAGES CAN RESULT IN SECURITY BREACH
	 *
	 * @var boolean
	 *
	 * @internal
	 */
	public static $IGNORE_PASSWORD_RESET = false;
}

/**
 * Username credentials
 *
 * @package StartupAPI
 * @subpackage Authentication\UsernamePassword
 */
class UsernamePassUserCredentials extends UserCredentials {
	/**
	 * @var string Username
	 */
	private $username;

	/**
	 * Creates Username credentials object
	 *
	 * @param type $username
	 */
	public function __construct($username) {
		$this->username = $username;
	}

	/**
	 * Returns user's username
	 *
	 * @return string Username
	 */
	public function getUsername() {
		return $this->username;
	}

	public function getHTML() {
		return $this->username;
	}
}
