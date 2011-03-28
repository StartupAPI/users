<?php
interface IAuthenticationModule extends IUserBaseModule
{
	public function renderLoginForm($action);
	public function renderRegistrationForm($full = false, $action = null, $errors = null, $data = null);
	public function processLogin($data, &$remember);
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
