<?php

namespace StartupAPI\API\v1\User\UsernamePass;

/**
 * @package StartupAPI
 * @subpackage API
 */
require_once(dirname(dirname(dirname(__DIR__))) . '/classes/User.php');
require_once(dirname(dirname(dirname(__DIR__))) . '/classes/Account.php');
require_once(dirname(dirname(dirname(__DIR__))) . '/classes/StartupAPIModule.php');

/**
 * Returns currently authenticated user
 *
 * @package StartupAPI
 * @subpackage API
 */
class Register extends \StartupAPI\API\AuthenticatedEndpoint {

	public function __construct() {
		parent::__construct('/v1/user', "Create new user with username and password");

		$this->params = array(
			'name' => new \StartupAPI\API\Parameter("Name", "John Smith"),
			'email' => new \StartupAPI\API\Parameter("Email", "john.smith@example.com"),
			'username' => new \StartupAPI\API\Parameter("Username", "johnsmith"),
			'pass' => new \StartupAPI\API\Parameter("Password", "*********"),
		);
	}

	public function call($values, $raw_request_body = null) {
		$user = parent::call($values);

		$values['repeatpass'] = $values['pass'];

		$module = \StartupAPIModule::get('usernamepass');

		if (!$module) {
			throw new \StartupAPI\API\ObjectNotFoundException('Required module is not available');
		}

		try {
			$remember = true;
			$user = $module->processRegistration($values, $remember);
		} catch (\StartupAPI\API\InputValidationException $ex) {
			throw new \StartupAPI\API\InvalidParameterValueException($ex->getMessage());
		} catch (\Exception $ex) {
			throw new \StartupAPI\API\APIException($ex->getMessage());
		}

		return array(
			'user_id' => $user->getID()
		);
	}
}
