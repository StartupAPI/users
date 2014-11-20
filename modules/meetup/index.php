<?php
require_once(dirname(dirname(__DIR__)).'/classes/OAuthModule.php');

/**
 * Meetup authentication module
 *
 * Provides authentication using Meetup.com accounts and API access using OAuth
 *
 * @package StartupAPI
 * @subpackage Authentication\Meetup
 */
class MeetupAuthenticationModule extends OAuthAuthenticationModule
{
	protected $userCredentialsClass = 'MeetupUserCredentials';

	/**
	 * Instantiates Meetup authentication module and registers it with the system
	 *
	 * @param string $oAuthConsumerKey OAuth Consumer Key
	 * @param string $oAuthConsumerSecret OAuth Consumer Secret
	 * @param string $oAuthScope Requested permission scopes (zero or more scope strings, usually URLs, separated by spaces)
	 */
	public function __construct($oAuthConsumerKey, $oAuthConsumerSecret, $oAuthScope = 'basic')
	{
		parent::__construct(
			'Meetup',
			'http://api.meetup.com',
			$oAuthConsumerKey,
			$oAuthConsumerSecret,
			'https://api.meetup.com/oauth/request/',
			'https://api.meetup.com/oauth/access/',
			'http://www.meetup.com/authenticate/',
			array('HMAC-SHA1', 'PLAINTEXT'),
			$oAuthScope,
			UserConfig::$USERSROOTURL.'/modules/meetup/login-button.png',
			UserConfig::$USERSROOTURL.'/modules/meetup/login-button.png',
			UserConfig::$USERSROOTURL.'/modules/meetup/login-button.png',
			array(
				array(2001, "Logged in using Meetup account", 1),
				array(2002, "Added Meetup account", 1),
				array(2003, "Removed Meetup account", 0),
				array(2004, "Registered using Meetup account", 1),
			)
		);
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
		return 'http://www.meetup.com/meetup_api/oauth_consumers/';
	}

	public function getIdentity($oauth_user_id) {
		// get meetup user id
		$request = new OAuthRequester('https://api.meetup.com/members.json/?relation=self', 'GET');
		$result = $request->doRequest($oauth_user_id);

		if ($result['code'] == 200) {
			$userdata = json_decode($result['body'], true);

			// array includes 'id' parameter which uniquely identifies a user
			if (array_key_exists('id', $userdata['results'][0])
				&& array_key_exists('name', $userdata['results'][0])
			) {
				return $userdata['results'][0];
			}
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
class MeetupUserCredentials extends OAuthUserCredentials {
	public function getHTML() {
		return StartupAPI::$template->render("@startupapi/modules/meetup/credentials.html.twig", $this->userinfo);
	}
}
