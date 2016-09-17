<?php
require_once(dirname(dirname(__DIR__)) . '/classes/OAuthModule.php');

/**
 * LinkedIn authorization modlue
 *
 * Provides LinkedIn authentication and API access using OAuth
 * Register your app here: https://www.linkedin.com/secure/developer
 *
 * @package StartupAPI
 * @subpackage Authentication\Linkedin
 */
class LinkedInAuthenticationModule extends OAuthAuthenticationModule {

	protected $userCredentialsClass = 'LinkedInUserCredentials';

	/**
	 * Instantiates LinkedIn authentication module and registers it with the system
	 *
	 * @param string $oAuthConsumerKey OAuth Consumer Key
	 * @param string $oAuthConsumerSecret OAuth Consumer Secret
	 * @param string $oAuthScope Requested permission scopes (zero or more scope strings, usually URLs, separated by spaces)
	 */
	public function __construct($oAuthConsumerKey, $oAuthConsumerSecret, $oAuthScope = null) {
		parent::__construct(
			'LinkedIn',
			'https://api.linkedin.com',
			$oAuthConsumerKey,
			$oAuthConsumerSecret,
			'https://api.linkedin.com/uas/oauth/requestToken',
			'https://api.linkedin.com/uas/oauth/accessToken',
			'https://www.linkedin.com/uas/oauth/authenticate',
			array('HMAC-SHA1', 'PLAINTEXT'),
			$oAuthScope,
			UserConfig::$USERSROOTURL . '/modules/linkedin/linkedin.png',
			UserConfig::$USERSROOTURL . '/modules/linkedin/linkedin.png',
			UserConfig::$USERSROOTURL . '/modules/linkedin/linkedin.png'
		);
	}

	public function getID() {
		return "linkedin";
	}

	public function getLegendColor() {
		return "4291ba";
	}

	public static function getModulesTitle() {
		return "LinkedIn";
	}

	public static function getModulesDescription() {
		return "<p>LinkedIn authentication module</p>
				 <p>Provides authentication using LinkedIn accounts and API access using OAuth</p>";
	}

	public function getDescription() {
		return self::getModulesDescription();
	}

	public static function getSignupURL() {
		return 'https://www.linkedin.com/secure/developer';
	}

	public static function getModulesLogo($size = 100) {
		if ($size == 100) {
			return UserConfig::$USERSROOTURL . '/modules/linkedin/images/logo_100x.png';
		}
	}

	public function getIdentity($oauth_user_id) {
		$request = new OAuthRequester('http://api.linkedin.com/v1/people/~:(id,formatted-name,picture-url,public-profile-url,email-address)?format=json', 'GET');
		$result = $request->doRequest($oauth_user_id);

		if ($result['code'] == 200) {
			$userdata = json_decode($result['body'], true);

			if (array_key_exists('id', $userdata) && array_key_exists('formattedName', $userdata)) {
				$userdata['name'] = $userdata['formattedName'];
				$userdata['email'] = $userdata['emailAddress'];
				unset($userdata['formattedName']);
				return $userdata;
			}
		}

		return null;
	}

	protected function renderUserInfo($serialized_userinfo) {
		$template_info = unserialize($serialized_userinfo);
		if (!is_array($template_info)) {
			$template_info = array();
		}

		return StartupAPI::$template->render("@startupapi/modules/linkedin/user_info.html.twig", $template_info);
	}

}

/**
 * LinkedIn user credentials class
 *
 * @package StartupAPI
 * @subpackage Authentication\Linkedin
 */
class LinkedInUserCredentials extends OAuthUserCredentials {

	public function getHTML() {
		return StartupAPI::$template->render("@startupapi/modules/linkedin/credentials.html.twig", $this->userinfo);
	}

}
