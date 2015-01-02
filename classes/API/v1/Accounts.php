<?php

namespace StartupAPI\API\v1;

/**
 * @package StartupAPI
 * @subpackage API
 */
require_once(dirname(__DIR__) . '/StartupAPIEndpoint.php');

/**
 * Returns currently authenticated user
 *
 * @package StartupAPI
 * @subpackage API
 */
class Accounts extends \StartupAPI\API\StartupAPIAuthenticatedEndpoint {

	private $description = "Returns a lis of accounts for currently authenticated user";

	public function call($values) {
		$user = parent::call($values);

		$accounts = $user->getAccounts();

		// @TODO Implement general API serialization logic for all objects
		return array_map(function($account) {
			return array(
				'id' => $account->getID(),
				'name' => $account->getName()
			);
		}, $accounts);
	}

}
