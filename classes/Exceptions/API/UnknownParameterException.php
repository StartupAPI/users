<?php
namespace StartupAPI\Exceptions\API;

/**
 * Thrown when parameter passed is not defined for endpoint
 */
class UnknownParameterException extends BadParameterException {

	private $param_name;

	function __construct($param_name, $message = "Unknown parameter") {
		parent::__construct($message);

		$this->param_name = $param_name;
		$this->message = $message . ": $param_name";
	}

	private function getParameterName() {
		return $this->param_name;
	}

}
