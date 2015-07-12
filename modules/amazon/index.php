<?php
require_once(dirname(dirname(__DIR__)).'/classes/OAuth2Module.php');

/**
 * First register an app here: https://sellercentral.amazon.com/gp/homepage.html
 * Amazon OAuth(2) docs: https://login.amazon.com/documentation
 * 
 * @package StartupAPI
 * @subpackage Authentication\Amazon
 */
class AmazonAuthenticationModule extends OAuth2AuthenticationModule
{
	protected $userCredentialsClass = 'AmazonUserCredentials';

	public function __construct($oAuth2ClientID, $oAuth2ClientSecret, $scopes = 'profile')
	{
		parent::__construct(
			'Amazon',
			'https://api.amazon.com',
			$oAuth2ClientID,
			$oAuth2ClientSecret,
			'https://www.amazon.com/ap/oa',
			'https://api.amazon.com/auth/o2/token',
			$scopes,
			NULL,
			NULL,
			NULL,
			array(
				array(9051, "Logged in using Amazon account", 1),
				array(9052, "Added Amazon account", 1),
				array(9053, "Removed Amazon account", 0),
				array(9054, "Registered using Amazon account", 1),
			)
		);
	}

	public function getID()
	{
		return "amazon";
	}

	public function getLegendColor()
	{
		return "E47911";
	}

	public static function getModulesTitle() {
		return "Amazon";
	}

	public static function getModulesDescription() {
		return '<p>Amazon login and API access module.</p>';
	}

	public function getDescription() {
		return self::getModulesDescription();
	}

	public static function getSignupURL() {
		return 'https://sellercentral.amazon.com/gp/homepage.html';
	}

	public static function getModulesLogo($size = 100) {
		if ($size == 100) {
			return UserConfig::$USERSROOTURL . '/modules/amazon/images/logo_100x.png';
		}
	}

	public function getIdentity($oauth2_client_id) {
		$credentials = $this->getOAuth2Credentials($oauth2_client_id);

		error_log("OAuth Credentials: " . var_export($credentials, true));

		try {
			$result = $credentials->makeOAuth2Request('https://api.amazon.com/user/profile', 'GET', null, array(
				CURLOPT_HTTPHEADER => array(
					'Accept: application/json',
					'User-Agent: ' . UserConfig::$appName . ' (Startup API v.' . StartupAPI::getVersion() . ')'
				)
			));
		} catch (OAuth2Exception $ex) {
			error_log("Error getting identity: " . $ex->getMessage());
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

		if (array_key_exists('user_id', $data) && array_key_exists('name', $data)) {
			return $data;
		}

		return null;
	}

	protected function renderUserInfo($serialized_userinfo) {
		$template_info = unserialize($serialized_userinfo);
		if (!is_array($template_info)) {
			$template_info = array();
		}

		return StartupAPI::$template->render("@startupapi/modules/amazon/user_info.html.twig", $template_info);
	}
}

/**
 * @package StartupAPI
 * @subpackage Authentication\Amazon
 */
class AmazonUserCredentials extends OAuth2UserCredentials {
	public function getHTML() {
		return StartupAPI::$template->render("@startupapi/modules/amazon/credentials.html.twig", $this->userinfo);
	}
}
