<?php

namespace StartupAPI\API\v1\User\UsernamePass;

/**
 * @package StartupAPI
 * @subpackage API
 */
require_once(dirname(dirname(dirname(__DIR__))) . '/classes/User.php');
require_once(dirname(dirname(dirname(__DIR__))) . '/classes/Account.php');

/**
 * Returns currently authenticated user
 *
 * @package StartupAPI
 * @subpackage API
 */
class Login extends \StartupAPI\API\Endpoint {

	public function __construct() {
		parent::__construct('/v1/login', "Logs user in and sets up appropriate cookies");

		$this->params = array(
			'username' => new \StartupAPI\API\Parameter("Username", 'johnsmith'),
			'pass' => new \StartupAPI\API\Parameter("Password", '*********')
		);
	}

	public function call($values, $raw_request_body = null) {
		parent::call($values, $raw_request_body);

		$module = \AuthenticationModule::get('usernamepass');
		$remember = true;
		$user = $module->processLogin($values, $remember);

		if (is_null($user)) {
			throw new \StartupAPI\API\UnauthenticatedException();
		} else {
			$user->setSession($remember);
		}

		// @TODO Implement general API serialization logic for all objects
		return array(
			'id' => $user->getID(),
			'name' => $user->getName()
		);
	}

}
