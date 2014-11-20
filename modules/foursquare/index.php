<?php
require_once(dirname(dirname(__DIR__)).'/classes/OAuth2Module.php');

/**
 * First register an app here: https://foursquare.com/developers/apps
 * GitHub OAuth(2) docs: https://developer.foursquare.com/overview/auth
 * 
 * @package StartupAPI
 * @subpackage Authentication\Foursquare
 */
class FoursquareAuthenticationModule extends OAuth2AuthenticationModule
{
	const compatibilityDate = '20130302';

	protected $userCredentialsClass = 'FoursquareUserCredentials';

	public function __construct($oAuth2ClientID, $oAuth2ClientSecret, $scopes = '')
	{
		parent::__construct(
			'Foursquare',
			'https://api.foursquare.com/v2/',
			$oAuth2ClientID,
			$oAuth2ClientSecret,
			'https://foursquare.com/oauth2/authenticate',
			'https://foursquare.com/oauth2/access_token',
			$scopes,
			NULL,
			NULL,
			NULL,
			array(
				array(7101, "Logged in using Foursquare account", 1),
				array(7102, "Added Foursquare account", 1),
				array(7103, "Removed Foursquare account", 0),
				array(7104, "Registered using Foursquare account", 1),
			)
		);

		$this->oAuth2AccessTokenParamName = 'oauth_token';
		$this->oAuth2ExtraParameters = array('v' => self::compatibilityDate);
	}

	public function getID()
	{
		return "foursquare";
	}

	public function getLegendColor()
	{
		return "2fafe2";
	}

	public static function getModulesTitle() {
		return "Foursquare";
	}

	public static function getModulesDescription() {
		return '<p>Foursquare login and API access module.</p>';
	}

	public function getDescription() {
		return self::getModulesDescription();
	}

	public static function getSignupURL() {
		return 'https://foursquare.com/developers/apps';
	}

	public static function getModulesLogo($size = 100) {
		if ($size == 100) {
			return UserConfig::$USERSROOTURL . '/modules/foursquare/images/logo_100x.png';
		}
	}

	// YODO make actual foursquare calls, not twitter
	public function getIdentity($oauth2_client_id) {
		$credentials = $this->getOAuth2Credentials($oauth2_client_id); 

		try {
			$result = $credentials->makeOAuth2Request('https://api.foursquare.com/v2/users/self');
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

		if (array_key_exists('meta', $data) &&
			array_key_exists('code', $data['meta']) &&
			$data['meta']['code'] !== 200
		) {
			return null;
		}

		if (array_key_exists('response', $data)) {
			$user_info = $data['response']['user'];
			if (array_key_exists('id', $user_info) &&
				array_key_exists('firstName', $user_info) &&
				array_key_exists('lastName', $user_info)
			) {
				$user_info['name'] = $user_info['firstName'] . ' ' . $user_info['lastName'];
				return $user_info;
			}
		}

		return null;
	}

	protected function renderUserInfo($serialized_userinfo) {
		$template_info = unserialize($serialized_userinfo);
		if (!is_array($template_info)) {
			$template_info = array();
		}

		return StartupAPI::$template->render("@startupapi/modules/foursquare/user_info.html.twig", $template_info);
	}
}

/**
 * @package StartupAPI
 * @subpackage Authentication\Foursquare
 */
class FoursquareUserCredentials extends OAuth2UserCredentials {
	public function getHTML() {
		return StartupAPI::$template->render("@startupapi/modules/foursquare/credentials.html.twig", $this->userinfo);
	}
}
