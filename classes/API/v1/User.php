<?php

namespace StartupAPI\API\v1;

/**
 * Returns currently authenticated user
 *
 * @package StartupAPI
 * @subpackage API
 */
class User extends \StartupAPI\API\AuthenticatedEndpoint {

	public function __construct() {
		parent::__construct('/v1/user', "Returns currently authenticated user or user specified by 'id' parameter");

		$this->params = array(
			'id' => new \StartupAPI\API\Parameter("User ID", 1, true, true)
		);
	}

	public function call($values, $raw_request_body = null) {
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
					throw new \StartupAPI\API\Exceptions\UnauthorizedException("You are not allowed to request information about this user");
				}
			} else {
				throw new \StartupAPI\API\Exceptions\ObjectNotFoundException("No such user");
			}
		}

		// @TODO Implement general API serialization logic for all objects
		return array(
			'id' => $user->getID(),
			'name' => $user->getName()
		);
	}

}
