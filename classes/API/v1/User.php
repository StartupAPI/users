<?php

namespace StartupAPI\API\v1;

/**
 * @package StartupAPI
 * @subpackage API
 */
require_once(dirname(__DIR__) . '/StartupAPIEndpoint.php');
require_once(dirname(__DIR__) . '/StartupAPIEndpointParamType.php');

require_once(dirname(dirname(__DIR__)) . '/User.php');
require_once(dirname(dirname(__DIR__)) . '/Account.php');

/**
 * Returns currently authenticated user
 *
 * @package StartupAPI
 * @subpackage API
 */
class User extends \StartupAPI\API\StartupAPIAuthenticatedEndpoint {

	private $description = "Returns currently authenticated user";

	public function __construct() {
		$this->params = array(
			'id' => new \StartupAPI\API\StartupAPIEndpointParamType(true)
		);
	}

	public function call($values) {
		$user = parent::call($values);

		if (array_key_exists('id', $values)) {
			$requested_user = $user->getUser($values['id']);

			// now make sure they belong to the same account
			if (!is_null($requested_user)) {
				$authenticated_user_accounts = $user->getAccounts();
				$requested_user_accounts = $requested_user->getAccounts();

				$users_belong_to_same_account = false;
				foreach ($authenticated_user_accounts as $authenticated_user_account) {
					foreach ($requested_user_accounts as $requested_user_account) {
						if ($authenticated_user_account->isTheSameAs($requested_user_account)) {
							$users_belong_to_same_account = true;
							break 2;
						}
					}
				}

				if ($users_belong_to_same_account) {
					$user = $requested_user;
				} else {
					throw new \StartupAPI\API\UnauthorizedException("You are not allowed to request information about this user");
				}
			} else {
				return null;
			}
		}

		// @TODO Implement general API serialization logic for all objects
		return array(
			'id' => $user->getID(),
			'name' => $user->getName()
		);
	}

}
