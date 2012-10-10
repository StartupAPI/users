<?php
/**
 * Email authentication module (UNFINISHED)
 *
 * This module is used for authenticating users using just an email address.
 * It is useful for signing up people for future newsletter notifications of for an early beta program.
 *
 * @todo Finish the implementation ;)
 *
 * @package StartupAPI
 * @subpackage Authentication\Email
 */
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

	/**
	 * Returns user's credentials (email address)
	 *
	 * @param User $user User object
	 *
	 * @return EmailUserCredentials|null
	 *
	 * @throws DBException
	 */
	public function getUserCredentials($user)
	{
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('SELECT email FROM '.UserConfig::$mysql_prefix.'users WHERE id = ?'))
		{
			if (!$stmt->bind_param('i', $userid))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($email))
			{
				throw new DBBindResultException($db, $stmt);
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
			throw new DBPrepareStmtException($db);
		}

		return null;
	}

	/**
	 * Returns a number of users with who provided email address
	 *
	 * @return int Number of users with email address
	 *
	 * @throws DBException
	 */
	public function getTotalConnectedUsers()
	{
		$db = UserConfig::getDB();

		$conns = 0;

		if ($stmt = $db->prepare('SELECT count(*) AS conns FROM '.UserConfig::$mysql_prefix.'users WHERE email IS NOT NULL'))
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

	public function renderLoginForm($action)
	{
		?>
		<form id="startupapi-email-login-form" action="<?php echo $action?>" method="POST">
		<fieldset>
		<legend>Enter your email address to re-send login link</legend>
		<ul>
		<li><label for="startupapi-email-login-email">Email</label><input id="startupapi-email-login-email" name="email" type="email" size="40"/><?php echo array_key_exists('email', $errors) ? ' <abbr title="'.UserTools::escape(implode("\n", $errors['email'])).'">*</abbr>' : ''?></li>
		<li><button id="startupapi-email-login-button" type="submit" name="login">Re-send login link</button><?php if (UserConfig::$enableRegistration) {?> <a href="<?php echo UserConfig::$USERSROOTURL?>/register.php">or register</a><?php } ?></li>
		</ul>
		</fieldset>
		</form>
		<?php

	}

	public function renderRegistrationForm($full = false, $action = null, $errors = null, $data = null)
	{
		?>
		<form id="startupapi-email-signup-form" action="<?php echo $action?>" method="POST">
		<fieldset>
		<legend>Enter your name and email address to sign up</legend>
		<ul>
		<li><label for="startupapi-email-register-name">Name</label><input id="startupapi-email-register-name" name="name" type="test" size="40" value="<?php echo array_key_exists('name', $data) ? UserTools::escape($data['name']) : ''?>"/><?php echo array_key_exists('name', $errors) ? ' <abbr title="'.UserTools::escape(implode("\n", $errors['name'])).'">*</abbr>' : ''?></li>
		<li><label for="startupapi-email-signup-email">Email</label><input id="startupapi-email-signup-email" name="email" type="text" size="40"/><?php echo array_key_exists('email', $errors) ? ' <abbr title="'.UserTools::escape(implode("\n", $errors['email'])).'">*</abbr>' : ''?></li>
		<li><button id="startupapi-email-signup-button" type="submit" name="register">Sign up</button> <a href="<?php echo UserConfig::$USERSROOTURL?>/login.php">or re-send login link</a></li>
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
		<form id="startupapi-email-edit-form" action="<?php echo $action?>" method="POST">
		<fieldset>
		<legend>Update your name and email</legend>
		<ul>
		<li class="startupapi-email-edit-section">Name and email</li>
		<li><label>Name</label><input name="name" type="test" size="40" value="<?php echo UserTools::escape(array_key_exists('name', $data) ? $data['name'] : $user->getName())?>"/><?php echo array_key_exists('name', $errors) ? ' <span class="startup-api-error-message" title="'.UserTools::escape(implode("\n", $errors['name'])).'">*</span>' : ''?></li>
		<li><label>E-mail</label><input name="email" type="text" size="40" value="<?php echo UserTools::escape(array_key_exists('email', $data) ? $data['email'] : $user->getEmail())?>"/><?php echo array_key_exists('email', $errors) ? ' <span class="startup-api-error-message" title="'.UserTools::escape(implode("\n", $errors['email'])).'">*</span>' : ''?></li>
		</ul>
		</fieldset>
		<?php UserTools::renderCSRFNonce(); ?>
		</form>
		<?php
	}

	/**
	 * Instead of logging user in, sends a link to their email address for clicking back
	 *
	 * @param array $data Form array
	 * @param boolesn $remember Remember user or not
	 *
	 * @return null Never returns a user object
	 *
	 * @todo implement actual email sending
	 */
	public function processLogin($data, &$remember)
	{
		$user = User::getUsersByEmailOrUsername($data['email']);
//		$user->sendConfirmationEmail();

		header('Location: '.UserConfig::$USERSROOTURL.'/modules/email/login.php?module=email&message=linksent');
		exit;

		return null; // kind-of pointless after redirect, but indicates that you can' just login using email
	}

	/**
	 * Processes login link and logs user in
	 *
	 * @param string $email email address
	 * @param type $code
	 *
	 * @return User User object if successfully logged in
	 *
	 * @throws InputValidationException
	 *
	 * @todo Finish implementation
	 */
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

		// TODO check if code is good and log the user in
		return null;
	}

	/**
	 * REgisters a user, but instead of logging them in, sends a link to their email address for clicking back
	 *
	 * @param array $data Form data
	 * @param boolean $remember Remember user or not
	 *
	 * @return null Never returns a user object
	 *
	 * @throws InputValidationException
	 * @throws ExistingUserException
	 */
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
		$user = User::createNewWithoutCredentials($this, $name, $email);
		$user->recordActivity(USERBASE_ACTIVITY_REGISTER_EMAIL);
//		$user->sendConfirmationEmail();
		return null;
	}

	/**
	 * Sends actual email with confirmation code
	 *
	 * @todo Implement this method
	 */
	public function sendConfirmationEmail() {
		// TODO: Implement email sending
	}

	/**
	 * Edits user information
	 *
	 * @throws InputValidationException
	 *
	 * @todo ACtually implement checking if email is real
	 */
	public function processEditUser($user, $data)
	{
		$errors = array();

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

		$user->setEmail($email);
//		$user->sendConfirmationEmail();
		$user->save();

		$user->recordActivity(USERBASE_ACTIVITY_UPDATEUSERINFO);

		return true;
	}
}

/**
 * Email credentials for the user
 *
 * @package StartupAPI
 * @subpackage Authentication\Email
 */
class EmailUserCredentials extends UserCredentials {
	/**
	 * @var string User's email address
	 */
	private $email;

	/**
	 * Creates user credentials object
	 *
	 * @param string $email User's email address
	 */
	public function __construct($email) {
		$this->email = $email;
	}

	/**
	 * Returns user's email address
	 *
	 * @return string User's email address
	 */
	public function getEmail() {
		return $this->email;
	}

	public function getHTML() {
		return $this->email;
	}
}
