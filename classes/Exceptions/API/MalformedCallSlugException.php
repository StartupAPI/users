<?php
namespace StartupAPI\Exceptions\API;

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
