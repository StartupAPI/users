<?php
namespace StartupAPI;

/**
 * Abstract class representing user credentials for making OAuth API calls
 *
 * Should be extended by subclasses to implement additional features and customize
 * formatting for user information, e.g. add links to user profiles, userpics and so on.
 *
 * @package StartupAPI
 * @subpackage Authentication
 */
abstract class OAuthUserCredentials extends UserCredentials {
	/**
	 * @var int OAuth user id
	 */
	protected $oauth_user_id;

	/**
	 * @var array User info object specific to a subclass
	 */
	protected $userinfo;

	/**
	 * Creates new OAuth credentials
	 *
	 * @param int $oauth_user_id OAuth user ID
	 * @param array $userinfo User info array as retrieved from provider
	 */
	public function __construct($oauth_user_id, $userinfo) {
		$this->oauth_user_id = $oauth_user_id;
		$this->userinfo = $userinfo;
	}

	/**
	 * Returns OAuth user ID
	 *
	 * @return string OAuth user ID
	 */
	public function getOAuthUserID() {
		return $this->oauth_user_id;
	}

	/**
	 * Returns an array of user information key-value pairs
	 *
	 * @return array Array of user-specific information
	 */
	public function getUserInfo() {
		return $this->userinfo;
	}

	/**
	 * Returns a chunk of HTML to display user's credentials
	 *
	 * This method will most likely be implemented by a subclass using $this->userinfo object.
	 * For some providers it can be returning a code to include a JavaScript widget.
	 *
	 * @return string HTML to display user information
	 */
	public function getHTML() {
		return $this->userinfo['name'];
	}

	/**
	 * Makes HTTP request with OAuth authentication
	 *
	 * This method allows requesting information on behalf of the user from a 3rd party provider.
	 * Possibly the most important feature of the whole system.
	 *
	 * @param string $request Request URL
	 * @param string $method HTTP method (e.g. GET, POST, PUT, etc)
	 * @param array $params Request parameters key->value array
	 * @param string $body Request body
	 * @param array $files Array of file names (currently supports 1 max untilmultipart/form-data is supported)
	 *
	 * @return array Response data (code=>int, headers=>array(), body=>string)
	 */
	public function makeOAuthRequest($request, $method = null, $params = null, $body = null, $files = null) {
		$request = new OAuthRequester($request, $method, $params, $body, $files);
		return $request->doRequest($this->oauth_user_id);
	}
}
