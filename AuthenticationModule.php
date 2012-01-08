<?php
/**
 * @package StartupAPI
 * @subpackage Authentication
 */
interface IAuthenticationModule extends IUserBaseModule
{
	public function renderLoginForm($action);
	public function renderRegistrationForm($full = false, $action = null, $errors = null, $data = null);
	public function processLogin($data, &$remember);
	public function processAutoLogin();
	public function getAutoLogoutURL($return);
	public function renderAutoLogoutForm();
	public function processRegistration($data, &$remember);

	/**
	 * This method should return user credentials object
	 *
	 * @param User $user User to get credentials for
	 * @return UserCredentials User credentials object specific to the module
	 */
	public function getUserCredentials($user);

	/**
	 * This module returns total number of connections with provider
	 * @return int Number of users who have connections through this provider
	 *	       Some modules might allow for multiple connections, but the user is only counted once
	 */
	public function getTotalConnectedUsers();
}

abstract class AuthenticationModule extends UserBaseModule implements IAuthenticationModule {
	public function __construct() {
		parent::__construct();
		UserConfig::$authentication_modules[] = $this;
	}

	/**
	 * Returns authentication module by ID
	 * @param string $id ID of the module
	 */
	public static function get($id) {
		foreach (UserConfig::$authentication_modules as $module)
		{
			if ($module->getID() == $id) {
				return $module;
			}
		}
	}

	/**
	 * By default, do not auto-login, should be overriden by modules that support auto-login
	 */
	public function processAutoLogin() {
		return null;
	}

	/**
	 * By default, modules do not support auto-logout, don't try doing that
	 */
	public function getAutoLogoutURL($return) {
		return null;
	}

	/**
	 * By default, modules do not support auto-logout
	 */
	public function renderAutoLogoutForm() {
		return null;
	}

	/*
	 * retrieves aggregated registrations numbers
	 */
	public function getDailyRegistrations()
	{
		$db = UserConfig::getDB();

		$dailyregs = array();

		if ($stmt = $db->prepare('SELECT CAST(regtime AS DATE) AS regdate, count(*) AS regs FROM '.UserConfig::$mysql_prefix.'users WHERE regmodule = ? GROUP BY regdate'))
		{
			if (!$stmt->bind_param('s', $this->getID()))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
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
}

class InputValidationException extends Exception {
	private $errors;

	public function __construct($string, $code, $errors)
	{
		parent::__construct($string, $code);
		$this->errors = $errors;
	}

	public function getErrors()
	{
		return $this->errors;
	}
}

class ExistingUserException extends Exception {
	private $errors;

	public function __construct($string, $code, $errors)
	{
		parent::__construct($string, $code);
		$this->errors = $errors;
	}

	public function getErrors()
	{
		return $this->errors;
	}
}

class UserCreationException extends Exception {
	private $field;

	public function __construct($string, $code, $field)
	{
		parent::__construct($string, $code);
		$this->field = $field;
	}

	public function getField()
	{
		return $this->field;
	}
}

/*
 * Class representing user credentials for particular module
 * Must be subclassed and implemented by module
 */
abstract class UserCredentials {
	/**
	 * This method should return HTML representation of user credentials to be included in admin interface
	 * Usually linking to user's public profile at the source service
	 *
	 * @return string HTML representation of user credentials
	 */
	public abstract function getHTML();
}
