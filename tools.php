<?php
/*
 * Various tools used within Startup API
 */
class UserTools
{
	// CSRF prevention variables
	public static $CSRF_NONCE;

	/*
	 * Escapes strings making it safe to include user data in HTML output
	 */
	public static function escape($string)
	{
		return htmlentities($string, ENT_COMPAT, 'UTF-8');
	}

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
		UserTools::debug('Current nonces: '.implode(' ', $passed_nonces));

		// rolling array to keep a few nonces
		$unused_nonces = array();

		if (count($_POST) > 0) {
			if (!array_key_exists('CSRF_NONCE', $_POST)) {
				UserTools::debug('POST request in admin interface without CSRF nonce. Make sure form includes CSRF_NONCE hidden field.');
				header('HTTP/1.0 403 POST request origin check failed', true, 403);
				exit;
			}

			$nonce_matched = false;
			foreach ($passed_nonces as $passed_nonce) {
				if ($passed_nonce == $_POST['CSRF_NONCE']) {
					UserTools::debug('Nonce matched: '.$passed_nonce);
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

	public static function renderCSRFNonce() {
		?><input type="hidden" name="CSRF_NONCE" value="<?php echo self::escape(self::$CSRF_NONCE); ?>"/>
<?php
	}

	/**
	 * Debug wrapper for simplified debugging, call it like this:
	 *
	 *    UserTools::debug('... some message ...');
	 */
	public static function debug($message) {
		if (UserConfig::$DEBUG) {
			$trace = debug_backtrace();

			error_log('[DEBUG] ' . $message .
				' (' . $trace[1]['function'] .
				'(' . var_export($trace[1]['args'], true) . ')' .
				' on line ' . $trace[0]['line'] .
				' in ' . $trace[0]['file'] .
			')');
		}
	}
}
