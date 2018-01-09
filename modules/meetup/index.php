<?php
require_once(dirname(dirname(__DIR__)).'/classes/OAuth2Module.php');

/**
 * Meetup authentication module
 *
 * Provides authentication using Meetup.com accounts and API access using OAuth
 *
 * @package StartupAPI
 * @subpackage Authentication\Meetup
 */
class MeetupAuthenticationModule extends OAuth2AuthenticationModule
{
	protected $userCredentialsClass = 'MeetupUserCredentials';

	/**
	 * Instantiates Meetup authentication module and registers it with the system
	 *
	 * @param string $oAuth2ClientID OAuth2 Client ID
	 * @param string $oAuth2ClientSecret OAuth2 Client Secret
	 * @param string $oAuth2Scopes Requested permission scopes (zero or more scope strings, usually URLs, separated by spaces)
	 */
	public function __construct($oAuth2ClientID, $oAuth2ClientSecret, $oAuth2Scopes = 'basic')
	{
		parent::__construct(
			'Meetup',
			'https://api.meetup.com',
			$oAuth2ClientID,
			$oAuth2ClientSecret,
			'https://secure.meetup.com/oauth2/authorize',
			'https://secure.meetup.com/oauth2/access',
			$oAuth2Scopes,
			UserConfig::$USERSROOTURL.'/modules/meetup/login-button.png', //signup-button.png',
			UserConfig::$USERSROOTURL.'/modules/meetup/login-button.png',
			UserConfig::$USERSROOTURL.'/modules/meetup/login-button.png', //connect-button.png',
			array(
				array(2001, "Logged in using Meetup account", 1),
				array(2002, "Added Meetup account", 1),
				array(2003, "Removed Meetup account", 0),
				array(2004, "Registered using Meetup account", 1),
			)
		);

		$this->oAuth2AccessTokenRequestFormURLencoded = TRUE;
	}

	public function getID()
	{
		return "meetup";
	}

	public function getLegendColor()
	{
		return "e51837";
	}

	public static function getModulesTitle() {
		return "Meetup";
	}

	public static function getModulesDescription() {
		return "<p>Meetup authentication module</p>
				 <p>Provides authentication using Meetup.com accounts and API access using OAuth</p>";
	}

	public function getDescription() {
		return self::getModulesDescription();
	}

	public static function getModulesLogo($size = 100) {
		if ($size == 100) {
			return UserConfig::$USERSROOTURL . '/modules/meetup/images/logo_100x.png';
		}
	}

	public static function getSignupURL() {
		return 'https://www.meetup.com/meetup_api/oauth_consumers/';
	}

	public function getIdentity($oauth2_user_id) {
		$credentials = $this->getOAuth2Credentials($oauth2_user_id);

		try {
			$result = $credentials->makeOAuth2Request('https://api.meetup.com/members.json/?relation=self');
		} catch (OAuth2Exception $ex) {
			return null;
		}

		$userdata = json_decode($result, true);

		// array includes 'id' parameter which uniquely identifies a user
		if (array_key_exists('id', $userdata['results'][0])
			&& array_key_exists('name', $userdata['results'][0])
		) {
			return $userdata['results'][0];
		}

		return null;
	}

	protected function renderUserInfo($serialized_userinfo) {
		$template_info = unserialize($serialized_userinfo);
		if (!is_array($template_info)) {
			$template_info = array();
		}

		return StartupAPI::$template->render("@startupapi/modules/meetup/user_info.html.twig", $template_info);
	}
}

/**
 * Meetup user credentials class
 *
 * @package StartupAPI
 * @subpackage Authentication\Meetup
 */
class MeetupUserCredentials extends OAuth2UserCredentials {
	public function getHTML() {
		return StartupAPI::$template->render("@startupapi/modules/meetup/credentials.html.twig", $this->userinfo);
	}
}
