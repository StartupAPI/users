<?php

namespace StartupAPI\API\v1;

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
			throw new \StartupAPI\API\Exceptions\UnauthenticatedException();
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
