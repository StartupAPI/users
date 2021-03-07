<?php

namespace StartupAPI\API\v1\User;

/**
 * @package StartupAPI
 * @subpackage API
 */
require_once(dirname(dirname(__DIR__)) . '/Endpoint.php');
require_once(dirname(dirname(__DIR__)) . '/Parameter.php');

require_once(dirname(dirname(dirname(__DIR__))) . '/User.php');
require_once(dirname(dirname(dirname(__DIR__))) . '/Account.php');

/**
 * Returns currently authenticated user
 *
 * @package StartupAPI
 * @subpackage API
 */
class Get extends \StartupAPI\API\AuthenticatedEndpoint {

	public function __construct() {
		parent::__construct('/v1/user', "Returns currently authenticated user or user specified by 'id' parameter");

		$this->params = array(
			'id' => new \StartupAPI\API\Parameter("User ID", 1, true)
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
					throw new \StartupAPI\API\UnauthorizedException("You are not allowed to request information about this user");
				}
			} else {
				throw new \StartupAPI\API\ObjectNotFoundException("No such user");
			}
		}

		$result = array(
			'id' => $user->getID(),
			'name' => $user->getName(),
			'is_system_admin' => $user->isAdmin(),
			'is_email_verified' => $user->isEmailVerified()
		);

		// optional email
		$email = $user->getEmail();
		if ($email) {
			$result['email'] = $email;
		}

		// optional username
		$username = $user->getUsername();
		if ($username) {
			$result['username'] = $username;
		}

		if ($user->isImpersonated()) {
			$result['impersonator'] = $user->getImpersonator()->getID();
		}

		return $result;
	}

}
