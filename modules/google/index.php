<?php
require_once(dirname(dirname(__DIR__)).'/classes/OAuth2Module.php');

/**
 * First register an app here: https://console.developers.google.com/project
 * Google OAuth(2) docs: https://developers.google.com/identity/protocols/OAuth2
 * 
 * @package StartupAPI
 * @subpackage AuthenticationGoogle\
 */
class GoogleAuthenticationModule extends OAuth2AuthenticationModule
{
	protected $userCredentialsClass = 'GoogleUserCredentials';

	public function __construct($oAuth2ClientID, $oAuth2ClientSecret,
		$scopes = 'https://www.googleapis.com/auth/userinfo.email'
	) {
		parent::__construct(
			'Google',
			'https://www.googleapis.com/',
			$oAuth2ClientID,
			$oAuth2ClientSecret,
			'https://accounts.google.com/o/oauth2/auth',
			'https://www.googleapis.com/oauth2/v3/token',
			$scopes,
			UserConfig::$USERSROOTURL.'/modules/google/login-button.png',
			UserConfig::$USERSROOTURL.'/modules/google/login-button.png',
			UserConfig::$USERSROOTURL.'/modules/google/login-button.png',
			array(
				array(3005, "Logged in using Google account", 1),
				array(3006, "Added Google account", 1),
				array(3007, "Removed Google account", 0),
				array(3008, "Registered using Google account", 1),
			)
		);

		$this->oAuth2AccessTokenRequestFormURLencoded = TRUE;
	}

	public function getID()
	{
		return "google";
	}

	public function getLegendColor()
	{
		return "D34438";
	}

	public static function getModulesTitle() {
		return "Google";
	}

	public static function getModulesDescription() {
		return '<p>Google login and API access module.</p>';
	}

	public function getDescription() {
		return self::getModulesDescription();
	}

	public static function getSignupURL() {
		return 'https://console.developers.google.com/project';
	}

	public static function getModulesLogo($size = 100) {
		if ($size == 100) {
			return UserConfig::$USERSROOTURL . '/modules/google/images/logo_100x.png';
		}
	}

	public function getIdentity($oauth2_client_id) {
		$credentials = $this->getOAuth2Credentials($oauth2_client_id); 

		try {
			$result = $credentials->makeOAuth2Request('https://www.googleapis.com/plus/v1/people/me');
		} catch (OAuth2Exception $ex) {
			return null;
		}

		$data = json_decode($result, true);

		if (is_null($data)) {
			switch(json_last_error())
			{
				case JSON_ERROR_DEPTH:
					error_log('JSON Error: Maximum stack depth exceeded');
				break;
				case JSON_ERROR_CTRL_CHAR:
					error_log('JSON Error: Unexpected control character found');
				break;
				case JSON_ERROR_SYNTAX:
					error_log('JSON Error: Syntax error, malformed JSON');
				break;
				case JSON_ERROR_NONE:
					error_log('JSON Error: No errors');
				break;
			}

			return null;
		}

		if (array_key_exists('error', $data)) {
			UserTools::debug("Got errors from /people/me API call: " . var_export($data['error'], true));
			return null;
		}

		$user_info = $data;
		if (array_key_exists('id', $user_info) && array_key_exists('displayName', $user_info)
		) {
			$user_info['name'] = $user_info['displayName'];
		} else {
			UserTools::debug("Don't have ID or displayName: " . var_export($user_info, true));
			return null;
		}

		if (array_key_exists('emails', $user_info) && count($user_info['emails'] > 0)) {
			$user_info['email'] = $user_info['emails'][0]['value'];
		}
		return $user_info;
	}

	protected function renderUserInfo($serialized_userinfo) {
		$template_info = unserialize($serialized_userinfo);
		if (!is_array($template_info)) {
			$template_info = array();
		}

		return StartupAPI::$template->render("@startupapi/modules/google/user_info.html.twig", $template_info);
	}
}

/**
 * @package StartupAPI
 * @subpackage Authentication\Google
 */
class GoogleUserCredentials extends OAuth2UserCredentials {
	public function getHTML() {
		return StartupAPI::$template->render("@startupapi/modules/google/credentials.html.twig", $this->userinfo);
	}
}
