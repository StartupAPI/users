<?php

namespace StartupAPI\API\v1;

/**
 * @package StartupAPI
 * @subpackage API
 */
require_once(dirname(__DIR__) . '/StartupAPIEndpoint.php');
require_once(dirname(__DIR__) . '/StartupAPIEndpointParamType.php');

/**
 * Returns currently authenticated user
 *
 * @package StartupAPI
 * @subpackage API
 */
class User extends \StartupAPI\API\StartupAPIAuthenticatedEndpoint {

	private $description = "Returns currently authenticated user";

	public function call($values) {
		$user = parent::call($values);

		// @TODO Implement general API serialization logic for all objects
		return array(
			'id' => $user->getID(),
			'name' => $user->getName()
		);
	}

}
