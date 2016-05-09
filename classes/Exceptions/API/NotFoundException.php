<?php
namespace StartupAPI\Exceptions\API;

/**
 * Abstract class for things that are not found
 */
abstract class NotFoundException extends APIException {

	function __construct($message = "Not found") {
		parent::__construct($message, 404);
	}

}
