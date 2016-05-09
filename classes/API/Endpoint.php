<?php
namespace StartupAPI\API;

/**
 * StartupAPI API Endpoint class
 *
 * Root abstract class for API endpoints, should not be instantiated directly
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
	 * @var Parameter[] Associative array of name => type pairs that define parameters
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
		if (!is_null(\StartupAPI\AuthenticationModule::get('usernamepass'))) {
			self::register($namespace, 'POST', new \StartupAPI\API\v1\Login());
		}
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
	 * @return Parameter[] Associative array of name => type pairs that define parameters
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
			throw new Exceptions\MalformedCallSlugException($call_slug);
		}

		$namespace_slug = $parts[1];
		$endpoint_slug = $parts[2];

		$endpoint_slug = "/$endpoint_slug";

		if (!array_key_exists($namespace_slug, self::$endpoints_by_method)) {
			throw new Exceptions\NamespaceNotFoundException($namespace_slug);
		}

		if (!array_key_exists($method, self::$endpoints_by_method[$namespace_slug])) {
			throw new Exceptions\MethodNotAllowedException($method);
		}

		if (!array_key_exists($endpoint_slug, self::$endpoints_by_method[$namespace_slug][$method])) {
			throw new Exceptions\CallNotFoundException($method, $call_slug);
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
				throw new Exceptions\UnknownParameterException($name);
			}

			if (!$this->params[$name]->validate($value)) {
				throw new Exceptions\InvalidParameterValueException("Invalid parameter value for $name");
			}

			unset($missing_params[$name]);
		}

		// check if missing parameters are required
		foreach ($missing_params as $key => $param) {
			if (!$param->isOptional()) {
				throw new Exceptions\RequiredParameterException($key);
			}
		}
	}

}
