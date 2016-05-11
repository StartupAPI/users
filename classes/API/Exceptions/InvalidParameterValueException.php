<?php
namespace StartupAPI\API\Exceptions;

/**
 * Thrown when parameter value is invalid
 * 
 * @package StartupAPI
 * @subpackage API
 */
class InvalidParameterValueException extends APIException {

	function __construct($message = "Invalid parameter value", $code = null, $previous = null) {
		parent::__construct($message, $code, $previous);
	}

}
