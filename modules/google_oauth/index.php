<?php
/**
 * @package StartupAPI
 * @subpackage Authentication
 */
require_once(dirname(dirname(dirname(__FILE__))).'/OAuthModule.php');

class GoogleOAuthAuthenticationModule extends OAuthAuthenticationModule
{
	protected $userCredentialsClass = 'GoogleOAuthUserCredentials';

	/**
	 * Constructor for Google OAuth module
	 * @param string $oAuthConsumerKey OAuth Consumer Key
	 * @param string $oAuthConsumerSecret OAuth Consumer Secret
	 * @param array $GoogleAPIScopes (optional) Array of Google API Scopes
	 *		See full list here: http://code.google.com/apis/gdata/faq.html#AuthScopes
	 */
	public function __construct($oAuthConsumerKey, $oAuthConsumerSecret,
		$GoogleAPIScopes = null)
	{
		// default scope needed for identity verification
		// TODO rewrite using hybrid OpenID + OAuth implementation
		$scopes = array('https://www.google.com/m8/feeds/');

		if (is_array($GoogleAPIScopes)) {
			$scopes = array_merge($scopes, $GoogleAPIScopes);
		}

		parent::__construct(
			'Google',
			'https://www.google.com/',
			$oAuthConsumerKey,
			$oAuthConsumerSecret,
			'https://www.google.com/accounts/OAuthGetRequestToken',
			'https://www.google.com/accounts/OAuthGetAccessToken',
			'https://www.google.com/accounts/OAuthAuthorizeToken',
			array('HMAC-SHA1'),
			implode(' ', $scopes),
			UserConfig::$USERSROOTURL.'/modules/google_oauth/login-button.png',
			UserConfig::$USERSROOTURL.'/modules/google_oauth/login-button.png',
			UserConfig::$USERSROOTURL.'/modules/google_oauth/login-button.png',
			array(
				array(3001, "Logged in using Google account", 1),
				array(3002, "Added Google account", 1),
				array(3003, "Removed Google account", 0),
				array(3004, "Registered using Google account", 1),
			)
		);
	}

	public function getID()
	{
		return "google-oauth";
	}

	public function getLegendColor()
	{
		return "e51837";
	}

	public function getTitle()
	{
		return "Google";
	}

	public function getIdentity($oauth_user_id) {
		// get meetup user id
		$request = new OAuthRequester('https://www.google.com/m8/feeds/groups/default/full', 'GET');
		$result = $request->doRequest($oauth_user_id);

		$self_url = null;

		if ($result['code'] == 200) {
			$raw_xml = $result['body'];
			$xml = new SimpleXMLElement($raw_xml);

			return array(
				'id' => (string)$xml->id,
				'name' => (string)$xml->author->name,
				'email' => (string)$xml->author->email
			);
		}


		return null;
	}
}

class GoogleOAuthUserCredentials extends OAuthUserCredentials {
}
