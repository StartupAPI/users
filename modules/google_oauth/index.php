<?php
require_once(dirname(dirname(dirname(__FILE__))).'/OAuthModule.php');

class GoogleOAuthAuthenticationModule extends OAuthAuthenticationModule
{
	protected $userCredentialsClass = 'GoogleOAuthUserCredentials';

	public function __construct($oAuthConsumerKey, $oAuthConsumerSecret, $remember = true)
	{
		parent::__construct(
			'Google',
			'https://www.google.com/',
			$oAuthConsumerKey,
			$oAuthConsumerSecret,
			'https://www.google.com/accounts/OAuthGetRequestToken',
			'https://www.google.com/accounts/OAuthGetAccessToken',
			'https://www.google.com/accounts/OAuthAuthorizeToken',
			array('HMAC-SHA1'),
			'https://www.google.com/m8/feeds/',
			$remember
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
