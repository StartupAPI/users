<?php

/**
 * @package StartupAPI
 * @subpackage API
 */

namespace StartupAPI\API;

/**
 * Basic parameter type with no validation
 * Other types should subclass this
 *
 * @package StartupAPI
 * @subpackage API
 */
class StartupAPIEndpointParamType {

	/**
	 * @var boolean Makes this parameter optional
	 */
	private $optional = false;

	/**
	 * @var boolean Allow multiple instances of the same parameter
	 */
	private $multiple = false;

	/**
	 * @param boolean $multiple Allow multiple instances of the same parameter
	 */
	public function __construct($optional = false, $multiple = false) {
		$this->optional = $optional ? true : false;
		$this->multiple = $multiple ? true : false;
	}

	/**
	 * @return boolean Returns true if parameter is optional
	 */
	public function isOptional() {
		return $this->optional;
	}

	/**
	 * Tool method, returns true if valid value is passed.
	 * This class always returns "true", subclasses can implement if needed.
	 * Can throw InvalidParameterValueException with detailed message or just return false if not valid.
	 *
	 * @param mixed $value Value to be validated
	 * @return boolean
	 *
	 * @throws InvalidParameterValueException
	 */
	public function validate($value) {
		// invalid if multiples are not allowed, but array of values is passed in
		if (!$this->multiple && is_array($value)) {
			throw new InvalidParameterValueException("Multiple values are not allowed for this parameter");
		}

		if (is_null($value) && !$this->optional) {
			throw new RequiredParameterException();
		}

		return true;
	}

}
