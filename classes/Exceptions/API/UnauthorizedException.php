<?php
namespace StartupAPI\Exceptions\API;

/**
 * Thrown when user is authenticated, but not allowed to make a request
 */
class UnauthorizedException extends APIException {

	function __construct($message = "Request forbidden") {
		parent::__construct($message, 403);
	}

}
