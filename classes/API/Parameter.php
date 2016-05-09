<?php
namespace StartupAPI\API;

/**
 * Basic parameter type with no validation
 * Other types should subclass this
 *
 * @package StartupAPI
 * @subpackage API
 */
class Parameter {

	/**
	 * @var string Parameter description
	 */
	private $description;

	/**
	 * @var string Sample value to be shown in call examples
	 */
	private $sample_value;

	/**
	 * @var boolean Makes this parameter optional
	 */
	private $optional = false;

	/**
	 * @var boolean Allow multiple instances of the same parameter
	 */
	private $multiple = false;

	/**
	 * @param string $description Parameter description
	 * @param boolean $optional Make this parameter optional
	 * @param boolean $multiple Allow multiple instances of the same parameter
	 */
	public function __construct($description, $sample_value, $optional = false, $multiple = false) {
		$this->description = $description;
		$this->sample_value = $sample_value;
		$this->optional = $optional ? true : false;
		$this->multiple = $multiple ? true : false;
	}

	/**
	 * @return string Parameter description
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return string Sample parameter value
	 */
	public function getSampleValue() {
		return $this->sample_value;
	}

	/**
	 * @return boolean Returns true if parameter is optional
	 */
	public function isOptional() {
		return $this->optional;
	}

	/**
	 * @return boolean True if multiple values for this parameter are allowed
	 */
	public function allowsMultipleValues() {
		return $this->multiple;
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
			throw new Exceptions\InvalidParameterValueException("Multiple values are not allowed for this parameter");
		}

		if (is_null($value) && !$this->optional) {
			throw new Exceptions\RequiredParameterException();
		}

		return true;
	}

}
