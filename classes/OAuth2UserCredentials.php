<?php
namespace StartupAPI;

/**
 * Class representing user credentials for making OAuth2 API calls
 *
 * Should be extended by subclasses to implement additional features and customize
 * formatting for user information, e.g. add links to user profiles, userpics and so on.
 *
 * @package StartupAPI
 * @subpackage Authentication\OAuth2
 */
class OAuth2UserCredentials extends UserCredentials {
	/**
	 * Module that represents the credentials
	 */
	protected $oauth2_module;

	/**
	 * @var int OAuth2 client id
	 */
	protected $oauth2_client_id;

	/**
	 * @var array User info object specific to a subclass
	 */
	protected $userinfo;

	/**
	 * @var string OAuth2 access token
	 */
	protected $access_token;

	/**
	 * @var int Second since epoch when OAuth2 access token expires
	 */
	protected $access_token_expires;

	/**
	 * @var string Refresh token to be used to get new OAuth access token without re-authorization
	 */
	protected $refresh_token;

	/**
	 * Creates new OAuth2 credentials
	 *
	 * @param int $oauth2_client_id OAuth client ID
	 * @param array $userinfo User info array as retrieved from provider
	 */
	public function __construct($oauth2_module, $oauth2_client_id, $userinfo,
		$access_token, $access_token_expires, $refresh_token)
	{
		$this->oauth2_module = $oauth2_module;
		$this->oauth2_client_id = $oauth2_client_id;
		$this->userinfo = $userinfo;
		$this->access_token = $access_token;
		$this->access_token_expires = $access_token_expires;
		$this->refresh_token = $refresh_token;
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
	 * Returns OAuth2 access token
	 *
	 * @return string OAuth2 access token
	 */
	public function getAccessToken() {
		return $this->access_token;
	}

	public function makeOAuth2Request($request, $method = 'GET', $params = null, $curlopt = array()) {
		return $this->oauth2_module->makeOAuth2Request($this, $request, $method, $params, $curlopt);
	}
}
