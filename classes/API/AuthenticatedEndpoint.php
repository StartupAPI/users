<?php
namespace StartupAPI\API;

/**
 * StartupAPI API AuthenticatedEndpoint class
 *
 * Abstract class for API authenticated endpoints, should not be instantiated directly
 *
 * @package StartupAPI
 * @subpackage API
 */

abstract class AuthenticatedEndpoint extends Endpoint {

	/**
	 * Performs authentication before processing the call, returns authenticated user object.
	 * Not supposed to be called directly, but subclasses can use this return value as a shortcut.
	 *
	 * @param mixed[] $values Associative array of parameter values for this call
	 * @return User Returns currently authenticated user
	 * @throws UnauthenticatedException
	 */
	protected function call($values, $raw_request_body = null) {
		parent::call($values);

		$user = \StartupAPI::getUser();

		if (is_null($user)) {
			throw new Exceptions\UnauthenticatedException();
		}

		return $user;
	}

}
