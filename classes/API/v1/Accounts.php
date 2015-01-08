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
class Accounts extends \StartupAPI\API\StartupAPIAuthenticatedEndpoint  implements \StartupAPI\API\EndpointAllowsRead {

	protected $description = "User's Accounts";

	public function getReadDescription() {
		return "Returns a list of accounts for currently authenticated user";
	}

	public function read($values) {
		$user = parent::call($values);

		$accounts = $user->getAccounts();

		// @TODO Implement general API serialization logic for all objects
		return array_map(function($account) {
			$users_and_roles = $account->getUsers();
			return array(
				'id' => $account->getID(),
				'name' => $account->getName(),
				'member_ids' => array_map(function($user_and_role) {
							return $user_and_role[0]->getID();
						}, $users_and_roles)
			);
		}, $accounts);
	}

}
