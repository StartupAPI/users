<?php
class UsernamePasswordAuthenticationModule implements IAuthenticationModule
{
	public function getID()
	{
		return "userpass";
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
				return $username;
			}
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return null;
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
		<form action="<?php echo $action?>" method="POST">
		<table>
		<tr><td>Username</td><td><input name="username" type="text" size="25" maxlength="25"/></td></tr>
		<tr><td>Password</td><td><input name="pass" type="password" size="25" autocomplete="off"/></td></tr>
		<?php if (UserConfig::$allowRememberMe) {?><tr><td></td><td><input type="checkbox" name="remember" value="yes" id="remember"/><label for="remember">remember me</a></td></tr> <?php }?>
		<tr><td></td><td><input type="submit" name="login" value="Log in &gt;&gt;&gt;"/><?php if (UserConfig::$enableRegistration) {?> <a href="<?php echo UserConfig::$USERSROOTURL?>/register.php">or register</a><?php } ?></td></tr>
		<tr><td></td><td><a style="font-size: smaller" href="<?php echo UserConfig::$USERSROOTURL?>/modules/usernamepass/forgotpassword.php">Forgot password?</a></td></tr>
		</table>
		</form>
		<?php
	}

	public function renderRegistrationForm($full = false, $action = null, $errors = null, $data = null)
	{
		?>
		<form action="<?php echo $action?>" method="POST">
		<table>
		<tr><td>Username</td><td><input name="username" type="text" size="25" maxlength="25" value="<?php echo array_key_exists('username', $data) ? htmlentities($data['username']) : ''?>"/><?php echo array_key_exists('username', $errors) ? ' <span style="color:red" title="'.htmlentities(implode("\n", $errors['username'])).'">*</span>' : ''?></td></tr>
		<tr><td>Password</td><td><input name="pass" type="password" size="25" autocomplete="off"/><?php echo array_key_exists('pass', $errors) ? ' <span style="color:red" title="'.htmlentities(implode("\n", $errors['pass'])).'">*</span>' : ''?></td></tr>
		<tr><td>Repeat password</td><td><input name="repeatpass" type="password" size="25" autocomplete="off"/><?php echo array_key_exists('repeatpass', $errors) ? ' <span style="color:red" title="'.htmlentities(implode("\n", $errors['repeatpass'])).'">*</span>' : ''?></td></tr>
		<tr><td>Name</td><td><input name="name" type="test" size="25" value="<?php echo array_key_exists('name', $data) ? htmlentities($data['name']) : ''?>"/><?php echo array_key_exists('name', $errors) ? ' <span style="color:red" title="'.htmlentities(implode("\n", $errors['name'])).'">*</span>' : ''?></td></tr>
		<tr><td>E-mail</td><td><input name="email" type="text" size="25" value="<?php echo array_key_exists('email', $data) ? htmlentities($data['email']) : ''?>"/><?php echo array_key_exists('email', $errors) ? ' <span style="color:red" title="'.htmlentities(implode("\n", $errors['email'])).'">*</span>' : ''?></td></tr>
		<tr><td></td><td><input type="submit" name="register" value="Register &gt;&gt;&gt;"/> <a href="<?php echo UserConfig::$USERSROOTURL?>/login.php">or login here</a></td></tr>
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
		<form action="<?php echo $action?>" method="POST">
		<table>
		<?php
		$username = $user->getUsername();

		if (is_null($username)) {
		?>
		<tr><td>Username</td><td><input name="username" type="text" size="25" maxlength="25" value="<?php echo array_key_exists('username', $data) ? htmlentities($data['username']) : ''?>"/><?php echo array_key_exists('username', $errors) ? ' <span style="color:red" title="'.htmlentities(implode("\n", $errors['username'])).'">*</span>' : ''?></td></tr>
		<?php }
		else
		{?>
		<tr><td>Username</td><td><b title="Sorry, you can't change your username">&nbsp;<?php echo htmlentities($username)?></b></td></tr>
		<?php }?>
		<tr><td colspan="2" style="padding-top: 1em; font-weight: bold">Name and email</td></tr>
		<tr><td>Name</td><td><input name="name" type="test" size="40" value="<?php echo htmlentities(array_key_exists('name', $data) ? $data['name'] : $user->getName())?>"/><?php echo array_key_exists('name', $errors) ? ' <span style="color:red" title="'.htmlentities(implode("\n", $errors['name'])).'">*</span>' : ''?></td></tr>
		<tr><td>E-mail</td><td><input name="email" type="text" size="40" value="<?php echo htmlentities(array_key_exists('email', $data) ? $data['email'] : $user->getEmail())?>"/><?php echo array_key_exists('email', $errors) ? ' <span style="color:red" title="'.htmlentities(implode("\n", $errors['email'])).'">*</span>' : ''?></td></tr>

		<tr><td colspan="2" style="padding-top: 1em; font-weight: bold">Change password</td></tr>
		<?php if (!is_null($user->getUsername())) {?>
		<tr><td>Current password</td><td><input name="currentpass" type="password" size="25" autocomplete="off"/><?php echo array_key_exists('currentpass', $errors) ? ' <span style="color:red" title="'.htmlentities(implode("\n", $errors['currentpass'])).'">*</span>' : ''?></td></tr>
		<?php } ?>
		<tr><td><?php if (is_null($user->getUsername())) {?>Set a<?php } else {?>New<?php } ?> password</td><td><input name="pass" type="password" size="25" autocomplete="off"/><?php echo array_key_exists('pass', $errors) ? ' <span style="color:red" title="'.htmlentities(implode("\n", $errors['pass'])).'">*</span>' : ''?></td></tr>
		<tr><td>Repeat new password</td><td><input name="repeatpass" type="password" size="25" autocomplete="off"/><?php array_key_exists('repeatpass', $errors) ? ' <span style="color:red" title="'.htmlentities(implode("\n", $errors['repeatpass'])).'">*</span>' : ''?></td></tr>
		<tr><td></td><td><input type="submit" name="save" value="Save &gt;&gt;&gt;"/></td></tr>
		</table>
		</form>
		<?php
	}

	public function processLogin($data)
	{
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

		return $user;
	}

	public function processRegistration($data)
	{
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
		return User::createNew($name, $username, $email, $data['pass']);
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
		}

		if (!$has_username)
		{
			$user->setUsername($username);
		}

		$user->setName($name);
		$user->setEmail($email);
		$user->save();

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

		return true;
	}
}
