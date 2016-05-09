<?php
namespace StartupAPI\Exceptions\API;

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
