<?php
namespace StartupAPI\API\Exceptions;

/**
 * Thrown when parameter is required
 *
 * @package StartupAPI
 * @subpackage API
 */
class RequiredParameterException extends BadParameterException {

	private $name;

	/**
	 * @param string $name Parameter name
	 * @param string $message Exception message
	 * @param int $code Exception code
	 * @param Exception $previous previous exception
	 */
	function __construct($name, $message = "Parameter required", $code = null, $previous = null) {
		parent::__construct($message, $code, $previous);

		$this->name = $name;
		$this->message = $message . ": $name";
	}

	private function getParameterName() {
		return $this->name;
	}

}
