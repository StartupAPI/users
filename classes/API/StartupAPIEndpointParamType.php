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
	 * @var boolean Allow multiple instances of the same parameter
	 */
	private $multiple = false;

	/**
	 *
	 * @param boolean $multiple Allow multiple instances of the same parameter
	 */
	public function __construct($multiple = false) {
		$this->multiple = $multiple;
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
		return true;
	}

}