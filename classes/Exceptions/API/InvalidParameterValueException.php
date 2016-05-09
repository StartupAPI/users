<?php
namespace StartupAPI\Exceptions\API;

/**
 * Thrown when parameter value is invalid
 */
class InvalidParameterValueException extends APIException {

	function __construct($message = "Invalid parameter value", $code = null, $previous = null) {
		parent::__construct($message, $code, $previous);
	}

}
