<?php
require_once(dirname(__FILE__).'/config.php');

interface IAuthenticationModule
{
	public function getID();
	public function getTitle();
	public function renderLoginForm($action);
	public function renderRegistrationForm($full = false, $action = null, $errors = null, $data = null);
	public function processLogin($data, &$remember);
	public function processRegistration($data, &$remember);
	public function getUserCredentials($user);
	public function getDailyRegistrations();
	public function getRecentRegistrations();
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
