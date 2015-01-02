<?php

/**
 * @package StartupAPI
 * @subpackage API
 */

namespace StartupAPI\API;

/**
 * StartupAPI API Endpoint class
 *
 * Root abstract class for API endpoints, should not be implemented directly
 *
 * @package StartupAPI
 * @subpackage API
 */
abstract class StartupAPIEndpoint {

	/**
	 * @var string Description of the endpoint
	 */
	private $description;

	/**
	 * @var StartupAPIEndpointParamType[] Associative array of name => type pairs that define parameters
	 */
	private $params = array();

	public function getDocumentation() {
		return $this->description;
	}

	public function getParams() {
		return $this->params;
	}

	/**
	 * Core method that implements the API Endpoint.
	 * Performs parameter type validation, needs to be overriden
	 * and called by all implementations.
	 *
	 * @param mixed[] $values Associative array of parameter values for this call
	 * @return mixed Returns a response PHP data structure
	 */
	public function call($values) {
		foreach ($values as $name => $value) {
			if (!array_key_exists($name, $this->params)) {
				throw new UnknownParameterException("Unknown parameter: $name");
			}

			if (!$this->params[$name]->validate($value)) {
				throw new InvalidParameterValueException("Invalid parameter value for $name");
			}
		}
	}

}

abstract class StartupAPIPublicEndpoint extends StartupAPIEndpoint {

}

abstract class StartupAPIAuthenticatedEndpoint extends StartupAPIEndpoint {

	public function call($values) {
		// @TODO run authentication here
		parent::call($values);

		$user = \StartupAPI::getUser();

		if (is_null($user)) {
			throw new UnauthenticatedException();
		}

		return $user;
	}

}

/*
 * API Exceptions
 */

/**
 * Abstract class for all API Exceptions
 */
abstract class APIException extends \StartupAPIException {

}

/**
 * Thrown when parameter value is invalid
 */
class InvalidParameterValueException extends APIException {

	function __construct($message = "Invalid parameter value", $code = null, $previous = null) {
		parent::__construct($message, $code, $previous);
	}

}

/**
 * Thrown when parameter passed is not defined for endpoint
 */
class UnknownParameterException extends APIException {

	function __construct($message = "Unknown parameter", $code = null, $previous = null) {
		parent::__construct($message, $code, $previous);
	}

}

/**
 * Thrown when no authentication provided when required
 */
class UnauthenticatedException extends APIException {

	function __construct($message = "User not authenticated") {
		parent::__construct($message, 401);
	}

}
