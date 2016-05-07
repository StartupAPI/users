<?php

namespace StartupAPI\API\v1;

/**
 * @package StartupAPI
 * @subpackage API
 */
require_once(dirname(__DIR__) . '/Endpoint.php');

/**
 * Returns currently authenticated user
 *
 * @package StartupAPI
 * @subpackage API
 */
class Accounts extends \StartupAPI\API\AuthenticatedEndpoint {

	public function __construct() {
		parent::__construct('/v1/accounts', 'Returns a list of accounts for currently authenticated user');
	}

	public function call($values, $raw_request_body = null) {
		$user = parent::call($values);

		$accounts = $user->getAccounts();

		// @TODO Implement general API serialization logic for all objects
		return array_map(function(Account $account) {
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
