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

	public static function randomBytes($length) {
		if (function_exists('openssl_random_pseudo_bytes')) {
			return openssl_random_pseudo_bytes($length);
		} else {
			return mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
		}
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

		self::$CSRF_NONCE = base64_encode(self::randomBytes(50));

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

    public static function encrypt($str)
    {
		$key = UserConfig::$SESSION_SECRET;

        if (function_exists('openssl_encrypt')) {
          $cipher = self::getOpenSSLCipher();
          $iv_len = openssl_cipher_iv_length($cipher);
          $iv = openssl_random_pseudo_bytes($iv_len);
          $data = openssl_encrypt($str, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
          $hmac = hash_hmac('sha256', $data, $key, $as_binary=true);
          return base64_encode($iv.$hmac.$data);
        } else {
          $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
          $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
          $data = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, self::padkey($key, 256), $str, MCRYPT_MODE_ECB, $iv);
          return base64_encode($data);
        }

    }

    public static function decrypt($data)
    {
		$key = UserConfig::$SESSION_SECRET;

        if (false === ($data = base64_decode($data))) {
            return false;
        }

        if (function_exists('openssl_decrypt')) {
          $cipher = self::getOpenSSLCipher();
          $iv_len = openssl_cipher_iv_length($cipher);
          $iv = substr($data, 0, $iv_len);
          $hmac = substr($data, $iv_len, $sha2len=32);
          $ciphertext_raw = substr($data, $iv_len+$sha2len);
          $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
          $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
          if (hash_equals($hmac, $calcmac)) {
            return $original_plaintext;
          } else {
            return null;
          }
        } else {
          $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
          $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
          return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, self::padkey($key, 256), $data, MCRYPT_MODE_ECB, $iv);
        }
    }

	private static function getOpenSSLCipher() {
		$ciphers = array_intersect(array(
		  'AES-256-CTR',
		  'AES-256-CFB',
		  'AES-128-CFB',
		), openssl_get_cipher_methods());
  
		if (empty($ciphers)) {
		  throw new Exception('No usable ciphers available');
		}
  
		return array_shift($ciphers);
	  }

	  private static function padkey($key, $bits = 256)
	  {
		  $keylen = round($bits / 8);
		  $currlen = strlen($key);
		  $newkey = (($currlen < $keylen) ? str_pad($key, $keylen, '0', STR_PAD_RIGHT) : substr($key, 0, $keylen));
		  return $newkey;
	  }  
}
