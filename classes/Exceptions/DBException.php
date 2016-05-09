<?php
namespace StartupAPI\Exceptions;

/**
 * Exception for database-related problems
 *
 * @package StartupAPI
 */
class DBException extends StartupAPIException {

	/**
	 * Creates a database-related exception
	 *
	 * @param mysqli $db MySQLi database object
	 * @param mysqli_stmt $stmt MySQLi database statement
	 * @param string $message Exception message
	 * @param int $code Exception code
	 * @param Exception $previous Previous exception in the chain
	 */
	function __construct(mysqli $db = null, $stmt = null, $message = null, $code = null, $previous = null) {
		$exception_message = $message;

		$class = get_class($this);
		$file = self::getFile();
		$line = self::getLine();

		if (is_null($db)) {
			$exception_message = "[$class] Can't connect to database, \$db object is null (in $file on line $line)";
		} else if ($db->connect_error) {
			$exception_message = "[$class] Can't connect to database: (" . $db->connect_errno . ") " .
					$db->connect_error . " (in $file on line $line)";
		} else if ($db->error) {
			$exception_message = "[$class] DB Error: " . $db->error . " (in $file on line $line)";
		} else if (!$stmt) {
			$exception_message = "[$class]" .
					' $db->error: ' . $db->error .
					' with message: ' . $message . " (in $file on line $line)";
		} else {
			$exception_message = "[$class]" .
					' $stmt->error: ' . $stmt->error .
					' with message: ' . $message . " (in $file on line $line)";
		}

		parent::__construct($exception_message, $code, $previous);
	}

}
