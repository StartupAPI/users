<?php
namespace StartupAPI\Exceptions;

/**
 * Exception superclass used for all exceptions in StartupAPI
 *
 * @package StartupAPI
 */
class StartupAPIException extends \Exception {

	/**
	 * General Startup API Exception
	 *
	 * @param string $message Exception message
	 * @param int $code Exception code
	 * @param Exception $previous Previous exception in the chain
	 */
	function __construct($message, $code = null, $previous = null) {
		parent::__construct('[StartupAPI] ' . $message, $code, $previous);
	}

}
