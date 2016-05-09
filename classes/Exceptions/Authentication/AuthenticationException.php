<?php
namespace StartupAPI\Exceptions\Authentication;

/**
 * Abstract authentication exception class for specific exceptions to subclass
 *
 * Comparing to regular exception, this one also stores a list of messages to be passed back to forms being rendered
 *
 * @package StartupAPI
 * @subpackage Authentication
 */
abstract class AuthenticationException extends \StartupAPI\Exceptions\StartupAPIException {

	/**
	 * An associative array of error messages
	 *
	 * Each form is free to define it's own keys, usually to indicate
	 * part of the form where error messages will be displayed.
	 *
	 * Values can also be linear arrays to allow for display of
	 * multiple messages in the same location.
	 *
	 * @var array
	 */
	protected $errors;

	/**
	 * Creates an exception accepting the list of error messages as 3rd parameter
	 *
	 * Example:
	 * <code>
	 * $errors['email'][] = "Using example domain";
	 * $errors['email'][] = "Verification email doesn't match";
	 * throw new Exceptions\Authentication\InputValidationException('Email validation failed', 0, $errors);
	 * </code>
	 *
	 * @see InputValidationException
	 *
	 * @param string $string Exception message
	 * @param int $code Excepton code
	 * @param array $errors A list of error messages to be displayed to the user
	 */
	public function __construct($string, $code, $errors) {
		parent::__construct($string, $code);
		$this->errors = $errors;
	}

	/**
	 * Returns a list of error messages to display
	 *
	 * @return array an array of error messages
	 */
	public function getErrors() {
		return $this->errors;
	}

}
