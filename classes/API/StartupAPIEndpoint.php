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
	protected $params = array();

	/**
	 *
	 * @return string Description of the endpoint
	 */
	public function getDocumentation() {
		return $this->description;
	}

	/**
	 * @return StartupAPIEndpointParamType[] Associative array of name => type pairs that define parameters
	 */
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
		$missing_params = $this->params;

		// check all passed parameters
		foreach ($values as $name => $value) {
			if (!array_key_exists($name, $this->params)) {
				throw new UnknownParameterException("Unknown parameter: $name");
			}

			if (!$this->params[$name]->validate($value)) {
				throw new InvalidParameterValueException("Invalid parameter value for $name");
			}

			unset($missing_params[$name]);
		}

		// check if missing parameters are required
		foreach ($missing_params as $key => $param) {
			if (!$param->isOptional()) {
				throw new RequiredParameterException($key);
			}
		}
	}

}

abstract class StartupAPIPublicEndpoint extends StartupAPIEndpoint {

}

abstract class StartupAPIAuthenticatedEndpoint extends StartupAPIEndpoint {

	/**
	 * Performs authentication before processing the call, returns authenticated user object.
	 * Not supposed to be called directly, but subclasses can use this return value as a shortcut.
	 *
	 * @param mixed[] $values Associative array of parameter values for this call
	 * @return User Returns currently authenticated user
	 * @throws UnauthenticatedException
	 */
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
 * Thrown when parameter is required
 */
class RequiredParameterException extends APIException {

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

/**
 * Thrown when user is authenticated, but not allowed to make a request
 */
class UnauthorizedException extends APIException {

	function __construct($message = "Request forbidden") {
		parent::__construct($message, 403);
	}

}
