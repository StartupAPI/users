<?php
namespace StartupAPI\API\Exceptions;

/**
 * Thrown when no authentication provided when required
 */
class UnauthenticatedException extends APIException {

	function __construct($message = "User not authenticated") {
		parent::__construct($message, 401);
	}

}
