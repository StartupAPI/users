<?php
require_once "php-sdk/src/base_facebook.php";

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(dirname(dirname(__FILE__))).'/CookieStorage.php');

/**
 * Extends the BaseFacebook class with no persistance beteen requests.
 * This is enough if PHP SDK is only used to get current status, but not to do actual OAuth (done using JS API)
 *
 * Based on a class provided in Facebook PHP SDK
 */
class Facebook extends BaseFacebook
{
	protected $state_storage = array();

	protected static $kSupportedKeys = array('state', 'code', 'access_token', 'user_id');

	/**
	 * Provides the implementations of the inherited abstract
	 * methods.  The implementation uses PHP sessions to maintain
	 * a store for authorization codes, user ids, CSRF states, and
	 * access tokens.
	 */
	protected function setPersistentData($key, $value) {
		if (!in_array($key, self::$kSupportedKeys)) {
			self::errorLog('Unsupported key passed to setPersistentData.');
			return;
		}
		$session_var_name = $this->constructSessionVariableName($key);

		$this->setPersistentData[$key] = $value;
	}

	protected function getPersistentData($key, $default = false) {
		if (!in_array($key, self::$kSupportedKeys)) {
			self::errorLog('Unsupported key passed to getPersistentData.');
			return $default;
		}

		$session_var_name = $this->constructSessionVariableName($key);

		return array_key_exists($session_var_name, $this->state_storage)
			? $this->state_storage[$session_var_name] : $default;
	}

	protected function clearPersistentData($key) {
		error_log("getPersistentData($key)");

		if (!in_array($key, self::$kSupportedKeys)) {
			self::errorLog('Unsupported key passed to clearPersistentData.');
			return;
		}

		$session_var_name = $this->constructSessionVariableName($key);
		unset($this->state_storage[$session_var_name]);
	}

	protected function clearAllPersistentData() {
		error_log("clearAllPersistentData()");

		foreach (self::$kSupportedKeys as $key) {
			$this->clearPersistentData($key);
		}
	}

	protected function constructSessionVariableName($key) {
		return implode('_', array(UserConfig::$facebook_storage_key_prefix, $this->getAppId(), $key));
	}
}
