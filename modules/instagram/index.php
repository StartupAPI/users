<?php
namespace StartupAPI\Modules;

/**
 * First register an app here: https://instagram.com/developer/clients/register/
 * Instagram OAuth2 docs: https://instagram.com/developer/authentication/
 *
 * @package StartupAPI
 * @subpackage Authentication\Instagram
 */
class InstagramAuthenticationModule extends OAuth2AuthenticationModule
{
	protected $userCredentialsClass = 'InstagramUserCredentials';

	public function __construct($oAuth2ClientID, $oAuth2ClientSecret,
		$scopes = 'basic'
	) {
		parent::__construct(
			'Instagram',
			'https://api.instagram.com/',
			$oAuth2ClientID,
			$oAuth2ClientSecret,
			'https://api.instagram.com/oauth/authorize/',
			'https://api.instagram.com/oauth/access_token',
			$scopes,
			UserConfig::$USERSROOTURL.'/modules/instagram/login-button.png',
			UserConfig::$USERSROOTURL.'/modules/instagram/login-button.png',
			UserConfig::$USERSROOTURL.'/modules/instagram/login-button.png',
			array(
				array(11005, "Logged in using Instagram account", 1),
				array(11006, "Added Instagram account", 1),
				array(11007, "Removed Instagram account", 0),
				array(11008, "Registered using Instagram account", 1),
			)
		);
	}

	public function getID()
	{
		return "instagram";
	}

	public function getLegendColor()
	{
		return "6E9CB0";
	}

	public static function getModulesTitle() {
		return "Instagram";
	}

	public static function getModulesDescription() {
		return '<p>Instagram login and API access module.</p>';
	}

	public function getDescription() {
		return self::getModulesDescription();
	}

	public static function getSignupURL() {
		return 'https://instagram.com/developer/clients/register/';
	}

	public static function getModulesLogo($size = 100) {
		if ($size == 100) {
			return UserConfig::$USERSROOTURL . '/modules/instagram/images/logo_100x.png';
		}
	}

	public function getIdentity($oauth2_client_id) {
		$credentials = $this->getOAuth2Credentials($oauth2_client_id);

		try {
			$result = $credentials->makeOAuth2Request('https://api.instagram.com/v1/users/self');
		} catch (Exceptions\OAuth2Exception $ex) {
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

		if ($data['meta']['code'] != 200) {
			UserTools::debug("Got API error from /users/self call: " . var_export($data['error'], true));
			return null;
		}

		if (array_key_exists('error', $data)) {
			UserTools::debug("Got errors from /users/self API call: " . var_export($data['error'], true));
			return null;
		}

		$user_info = $data['data'];
		if (array_key_exists('id', $user_info) && array_key_exists('full_name', $user_info)
		) {
			$user_info['name'] = $user_info['full_name'];
		} else {
			UserTools::debug("Don't have ID or full_name: " . var_export($user_info, true));
			return null;
		}
		return $user_info;
	}

	protected function renderUserInfo($serialized_userinfo) {
		$template_info = unserialize($serialized_userinfo);
		if (!is_array($template_info)) {
			$template_info = array();
		}

		return StartupAPI::$template->render("@startupapi/modules/instagram/user_info.html.twig", $template_info);
	}
}

/**
 * @package StartupAPI
 * @subpackage Authentication\Instagram
 */
class InstagramUserCredentials extends OAuth2UserCredentials {
	public function getHTML() {
		return StartupAPI::$template->render("@startupapi/modules/instagram/credentials.html.twig", $this->userinfo);
	}
}
