<?php

/**
 * @package StartupAPI
 * @subpackage API
 */

namespace StartupAPI\API;

// APIs Endpoints to be registered
require_once(__DIR__ . '/v1/User.php');
require_once(__DIR__ . '/v1/Accounts.php');

/**
 * StartupAPI API Endpoint class
 *
 * Root abstract class for API endpoints, should not be implemented directly
 *
 * @package StartupAPI
 * @subpackage API
 */
abstract class Endpoint {

	/**
	 * @var mixed[] Registered endpoints organized by method [namespace slug][method][endpoint_slug]
	 */
	protected static $endpoints_by_method = array();

	/**
	 * @var mixed[] Registered endpoints organized by endpoint slug [namespace slug][endpoint_slug][method]
	 */
	protected static $endpoints_by_slug = array();

	/**
	 * @var EndpointNameSpace[] Registered namespaces
	 */
	protected static $namespaces = array();

	/**
	 * @var string Emdpoint slug to be used in URLs
	 */
	protected $slug;

	/**
	 * @var string Description of the endpoint
	 */
	protected $description;

	/**
	 * @var ParameterType[] Associative array of name => type pairs that define parameters
	 */
	protected $params = array();

	/**
	 * Registers endpoint in the system
	 *
	 * @param \StartupAPI\API\EndpointNameSpace $namespace Endpoint namespace
	 * @param string $method HTTP Method to work with
	 * @param self $endpoint Endpoint implementation
	 */
	public static function register(EndpointNameSpace $namespace, $method, self $endpoint) {
		self::$namespaces[$namespace->getSlug()] = $namespace;
		self::$endpoints_by_method[$namespace->getSlug()][$method][$endpoint->getSlug()] = $endpoint;
		self::$endpoints_by_slug[$namespace->getSlug()][$endpoint->getSlug()][$method] = $endpoint;
	}

	/**
	 * Helper function that registers core API endoiunts in the system
	 */
	public static function registerCoreEndpoints() {
		$namespace = new EndpointNameSpace(
				'startupapi', 'StartupAPI', 'Startup API core endpoints'
		);
		self::register($namespace, 'GET', new \StartupAPI\API\v1\User());
		self::register($namespace, 'GET', new \StartupAPI\API\v1\Accounts());
	}

	protected function __construct($slug, $description) {
		$this->slug = $slug;
		$this->description = $description;
	}

	/**
	 * @return string Returns endpoint's slug
	 */
	public function getSlug() {
		return $this->slug;
	}

	/**
	 *
	 * @return string Description of the endpoint
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return ParameterType[] Associative array of name => type pairs that define parameters
	 */
	public function getParams() {
		return $this->params;
	}

	/**
	 *
	 * @return mixed[] Returns registered endpoints organized by slug
	 */
	public static function getAllEndpointsBySlug() {
		return self::$endpoints_by_slug;
	}

	/**
	 * Returns endpoint that corresponds to the (full) call slug
	 *
	 * @param string $method
	 * @param string $call_slug
	 */
	public static function getEndpoint($method, $call_slug) {
		$parts = explode('/', $call_slug, 3);

		if (!is_array($parts) || !isset($parts[1]) || !isset($parts[2])) {
			throw new \StartupAPI\API\MalformedCallSlugException($call_slug);
		}

		$namespace_slug = $parts[1];
		$endpoint_slug = $parts[2];

		$endpoint_slug = "/$endpoint_slug";

		if (!array_key_exists($namespace_slug, self::$endpoints_by_method)) {
			throw new \StartupAPI\API\NamespaceNotFoundException($namespace_slug);
		}

		if (!array_key_exists($method, self::$endpoints_by_method[$namespace_slug])) {
			throw new \StartupAPI\API\MethodNotAllowedException($method);
		}

		if (!array_key_exists($endpoint_slug, self::$endpoints_by_method[$namespace_slug][$method])) {
			throw new \StartupAPI\API\CallNotFoundException($method, $call_slug);
		}

		return self::$endpoints_by_method[$namespace_slug][$method][$endpoint_slug];
	}

	/**
	 * @return EndpointNameSpace[] Returns registered namespaces
	 */
	public static function getNamespaces() {
		return self::$namespaces;
	}

	/**
	 * Helper function to parse strings of parameters from query string or request body
	 *
	 * @param string $urlencoded_string URL-encoded string of parameters
	 * @param mixed[] $params Associative array of parameters to merge decoded values into
	 * @return mixed[] Associative array of parameters
	 */
	public static function parseURLEncoded($urlencoded_string, $params = array()) {

		foreach (explode('&', $urlencoded_string) as $pair) {
			$key_value = explode('=', $pair);

			if (!is_array($key_value) || !isset($key_value[0]) || !isset($key_value[1])) {
				continue;
			}

			$key = $key_value[0];
			$value = $key_value[1];

			$key = urldecode($key);

			// support PHP arrays as well
			if (substr($key, -2) == '[]') {
				$key = substr($key, 0, strlen($key) - 2);
			}

			// if empty parameter name is passed, just ignore it
			if ($key == '') {
				continue;
			}

			$value = urldecode($value);

			if (array_key_exists($key, $params)) {
				// convert existing value to array if not an array yet
				if (!is_array($params[$key])) {
					$params[$key] = array($params[$key]);
				}
				$params[$key][] = $value;
			} else {
				$params[$key] = $value;
			}
		}

		return $params;
	}

	/**
	 * Core method that implements the API Endpoint.
	 * Performs parameter type validation, needs to be overriden
	 * and called by all implementations.
	 *
	 * @param mixed[] $values Associative array of parameter values for this call
	 * @param string|null $raw_request_body Raw request body (for POST/PUT requests)
	 *
	 * @return mixed Returns a response PHP data structure
	 */
	protected function call($values, $raw_request_body = null) {
		$missing_params = $this->params;

		// check all passed parameters
		foreach ($values as $name => $value) {
			if (!array_key_exists($name, $this->params)) {
				throw new UnknownParameterException($name);
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

abstract class AuthenticatedEndpoint extends Endpoint {

	/**
	 * Performs authentication before processing the call, returns authenticated user object.
	 * Not supposed to be called directly, but subclasses can use this return value as a shortcut.
	 *
	 * @param mixed[] $values Associative array of parameter values for this call
	 * @return User Returns currently authenticated user
	 * @throws UnauthenticatedException
	 */
	protected function call($values, $raw_request_body = null) {
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

abstract class BadRequestException extends APIException {

}

/**
 * Thrown when we can't parse "call" parameter
 */
class MalformedCallSlugException extends APIException {

	private $call_slug;

	/**
	 * @param string $name Parameter name
	 * @param string $message Exception message
	 * @param int $code Exception code
	 * @param Exception $previous previous exception
	 */
	function __construct($call_slug, $message = "Malformed call slug") {
		parent::__construct($message);

		$this->call_slug = $call_slug;
		$this->message = $message . ": $call_slug";
	}

	private function getCallSlug() {
		return $this->call_slug;
	}

}

abstract class BadParameterException extends BadRequestException {

}

/**
 * Thrown when parameter is required
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

/**
 * Thrown when no endpoints accept HTTP method used
 */
class MethodNotAllowedException extends APIException {

	private $method;

	/**
	 * @param string $method HTTP Method
	 * @param string $message Exception message
	 * @param int $code Exception code
	 * @param Exception $previous previous exception
	 */
	function __construct($method = null, $message = "HTTP Method not allowed", $code = null, $previous = null) {
		parent::__construct($message, $code, $previous);

		$this->method = $method;
		if (!is_null($this->method)) {
			$this->message = $message . ": $method";
		}
	}

	private function getMethod() {
		return $this->method;
	}

}

/**
 * Abstract class for things that are not found
 */
abstract class NotFoundException extends APIException {

	function __construct($message = "Not found") {
		parent::__construct($message, 404);
	}

}

/**
 * Thrown when requested onject is not found
 */
class ObjectNotFoundException extends NotFoundException {

}

/**
 * Thrown when API namespace is not found
 */
class NamespaceNotFoundException extends NotFoundException {

	private $namespace_slug;

	function __construct($namespace_slug, $message = "API Namespace not found") {
		parent::__construct($message);

		$this->namespace_slug = $namespace_slug;
		$this->message = $message . ": $namespace_slug";
	}

	private function getNamespaceSlug() {
		return $this->namespace_slug;
	}

}

/**
 * Thrown when there is no such endpoint (for a method used)
 */
class CallNotFoundException extends NotFoundException {

	private $method;
	private $call_slug;

	/**
	 * @param string $method HTTP Method
	 * @param string $call_slug API call slug
	 * @param string $message Exception message
	 */
	function __construct($method, $call_slug, $message = "API call not found") {
		parent::__construct($message);

		$this->method = $method;
		$this->call_slug = $call_slug;
		$this->message = $message . ": $method $call_slug";
	}

	private function getMethod() {
		return $this->method;
	}

	private function getCallSlug() {
		return $this->call_slug;
	}

}
