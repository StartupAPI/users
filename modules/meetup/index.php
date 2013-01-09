<?php
require_once(dirname(dirname(dirname(__FILE__))).'/classes/OAuthModule.php');

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

	public function getTitle()
	{
		return "Meetup";
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
		$user_info = unserialize($serialized_userinfo);
		?><a href="<?php echo UserTools::escape($user_info['link']); ?>" target="_blank"><?php echo UserTools::escape($user_info['name']); ?></a><br/>
		<img src="<?php echo UserTools::escape($user_info['photo_url']); ?>" style="max-width: 60px; max-height: 60px"/><?php
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
		return '<a href="'.UserTools::escape($this->userinfo['link']).'" target="_blank">'.$this->userinfo['name'].'</a>';
	}
}
