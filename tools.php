<?php

/**
 * Various tools used within Startup API
 *
 * @package StartupAPI
 */
class UserTools {

	/**
	 * @var string CSRF nonce for this request
	 */
	public static $CSRF_NONCE;

	/**
	 * Properly escapes strings making it safe to include user data in HTML output
	 *
	 * @param string $string Data to output
	 *
	 * @return string Escaped user data for output in HTML
	 */
	public static function escape($string) {
		return htmlentities($string, ENT_COMPAT, 'UTF-8');
	}

	/**
	 * URL-encodes the value, but uses %20 for spaces instead of '+'
	 * This is useful for some providers, including email clients that have problems with '+' signs
	 *
	 * @param string $string String to encode
	 *
	 * @return string Encoded string
	 */
	public static function spaceencode($string) {
		return str_replace('+', '%20', urlencode($string));
	}

	/**
	 * Prevents CSRF for all POST requests by comparing cookie and POST nonces.
	 *
	 * Keeps track of 3 recent nonces to avoid problems for double-submissions, but still prevent CSRF attacks
	 */
	public static function preventCSRF() {
		$storage = new MrClay_CookieStorage(array(
					'secret' => UserConfig::$SESSION_SECRET,
					'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
					'path' => UserConfig::$SITEROOTURL,
					'httponly' => true
				));

		/*
		 * Preventing CSRFs in all POST requests by double-posting cookies
		 */
		$passed_nonces = explode(' ', $storage->fetch(UserConfig::$csrf_nonce_key));

		// rolling array to keep a few nonces
		$unused_nonces = array();

		if (count($_POST) > 0) {
			if (!array_key_exists('CSRF_NONCE', $_POST)) {
				UserTools::debug('POST request without CSRF nonce. Make sure form includes CSRF_NONCE hidden field.');
				header('HTTP/1.0 403 POST request origin check failed', true, 403);
				exit;
			}

			$nonce_matched = false;
			foreach ($passed_nonces as $passed_nonce) {
				if ($passed_nonce == $_POST['CSRF_NONCE']) {
					$nonce_matched = true;
				} else {
					$unused_nonces[] = $passed_nonce;
				}
			}

			if (!$nonce_matched) {
				UserTools::debug('[Possible CSRF attack] POST request with wrong nonce!!!');
				header('HTTP/1.0 403 POST request origin check failed', true, 403);
				exit;
			}
		} else {
			$unused_nonces = $passed_nonces;
		}

		self::$CSRF_NONCE = base64_encode(mcrypt_create_iv(50, MCRYPT_DEV_URANDOM));

		// adding new nonce in front
		array_unshift($unused_nonces, self::$CSRF_NONCE);
		// keeping at most 3 nounces
		$unused_nonces = array_splice($unused_nonces, 0, 3);

		$storage->store(UserConfig::$csrf_nonce_key, implode(' ', $unused_nonces));
	}

	/**
	 * Outputs CSRF nonce form field to be included in all POST forms
	 */
	public static function renderCSRFNonce() {
		?><input type="hidden" name="CSRF_NONCE" value="<?php echo self::escape(self::$CSRF_NONCE); ?>"/>
		<?php
	}

	/**
	 * Debug wrapper for simplified debugging, call it like this:
	 *
	 * Logs debugging information into error log if UserConfig::$DEBUG variable is set to true
	 *
	 * Usage:
	 * <code>
	 * UserTools::debug('... some message ...');
	 * </code>
	 *
	 * @param string $message Debug message
	 *
	 * @see UserConfig::$DEBUG
	 */
	public static function debug($message) {
		if (UserConfig::$DEBUG) {
			$trace = debug_backtrace();

			if (is_array($message)) {
				$message = implode(' | ', $message);
			}

			$message = preg_replace('/\s+/', ' ', $message);

			$log_message = '[DEBUG] ' . $message;
			if (count($trace) > 1) {

				$log_message .= ' (' . $trace[1]['function'];

				if (UserConfig::$DEBUG_SHOW_ARGS) {
					$log_message .= '(' . var_export($trace[1]['args'], true) . ')';
				}
			}

			$log_message .= ' on line ' . $trace[0]['line'] . ' in ' . $trace[0]['file'] . ')';

			error_log($log_message);
		}
	}

}
