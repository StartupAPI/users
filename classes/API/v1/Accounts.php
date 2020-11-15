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

		$current_account = \Account::getCurrentAccount($user);

		$results = array();
		foreach ($accounts as $account) {
			$account_info = array(
				'id' => $account->getID(),
				'name' => $account->getName(),
				'is_admin' => ($account->getUserRole($user) == \Account::ROLE_ADMIN),
				'is_current' => $current_account->isTheSameAs($account)
			);

			$plan = $account->getPlan(); // can be FALSE
			if ($plan) {
				$account_info['plan']['name'] = $plan->getName();
				$account_info['plan']['description'] = $plan->getDescription();
			}

			$results[] = $account_info;
		}

		return $results;
	}

}
