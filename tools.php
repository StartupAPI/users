<?php
/*
 * Various tools used within UserBase
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
		if (count($_POST) > 0) {
			if (!array_key_exists('CSRF_NONCE', $_POST)) {
				error_log('POST request in admin interface without CSRF nonce. Make sure form includes CSRF_NONCE hidden field.');
				header('HTTP/1.0 403 POST request origin check failed', true, 403);
				exit;
			}

			$passed_nonce = $storage->fetch(UserConfig::$csrf_nonce_key);

			if ($passed_nonce != $_POST['CSRF_NONCE']) {
				error_log('[Possible CSRF attach] POST request with wrong nonce!!!');
				header('HTTP/1.0 403 POST request origin check failed', true, 403);
				exit;
			}
		}

		self::$CSRF_NONCE = base64_encode(mcrypt_create_iv(50, MCRYPT_DEV_URANDOM));
		$storage->store(UserConfig::$csrf_nonce_key, self::$CSRF_NONCE);
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
