<?php
/**
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

require_once dirname(__FILE__)."/php-sdk/src/base_facebook.php";

require_once(dirname(dirname(dirname(__FILE__))).'/global.php');

/**
 * Extends the BaseFacebook class with the intent of using
 * PHP sessions to store user ids and access tokens.
 *
 * Based on a class provided in Facebook PHP SDK
 *
 * @package StartupAPI
 * @subpackage Authentication\Facebook
 */
class Facebook extends BaseFacebook
{
	/**
	* Identical to the parent constructor, except that
	* we start a PHP session to store the user ID and
	* access token if during the course of execution
	* we discover them.
	*
	* @param Array $config the application configuration.
	* @see BaseFacebook::__construct in facebook.php
	*/
	public function __construct($config) {
		if (!session_id()) {
			session_start();
		}
		parent::__construct($config);
	}

	/**
	 * @var array Keys supported by OAuth endpoint
	 */
	protected static $kSupportedKeys = array('state', 'code', 'access_token', 'user_id');

	/**
	 * Provides the implementations of the inherited abstract
	 * methods.  The implementation uses PHP sessions to maintain
	 * a store for authorization codes, user ids, CSRF states, and
	 * access tokens.
	 *
	 * @param string $key Key
	 * @param mixed $value Value
	 */
	protected function setPersistentData($key, $value) {
		if (!in_array($key, self::$kSupportedKeys)) {
			self::errorLog('Unsupported key passed to setPersistentData.');
			return;
		}

		$session_var_name = $this->constructSessionVariableName($key);
		$_SESSION[$session_var_name] = $value;
	}

	/**
	 * Retrieves data between requests
	 *
	 * @param string $key
	 * @param boolean $default
	 *
	 * @return mixed
	 */
	protected function getPersistentData($key, $default = false) {
		if (!in_array($key, self::$kSupportedKeys)) {
			self::errorLog('Unsupported key passed to getPersistentData.');
			return $default;
		}

		$session_var_name = $this->constructSessionVariableName($key);
		return isset($_SESSION[$session_var_name]) ? $_SESSION[$session_var_name] : $default;
	}

	/**
	 * Erases persisted data for the key
	 *
	 * @param string $key Key
	 */
	protected function clearPersistentData($key) {
		if (!in_array($key, self::$kSupportedKeys)) {
			self::errorLog('Unsupported key passed to clearPersistentData.');
			return;
		}

		$session_var_name = $this->constructSessionVariableName($key);
		unset($_SESSION[$session_var_name]);
	}

	/**
	 * Removes all persisted data
	 */
	protected function clearAllPersistentData() {
		foreach (self::$kSupportedKeys as $key) {
			$this->clearPersistentData($key);
		}
	}

	/**
	 * Creates storage key based on the key provided
	 *
	 * @param string $key Key
	 *
	 * @return string Storage variable name
	 */
	protected function constructSessionVariableName($key) {
		return implode('_', array(UserConfig::$facebook_storage_key_prefix, $this->getAppId(), $key));
	}
}
