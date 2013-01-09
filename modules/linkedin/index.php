<?php
require_once(dirname(dirname(dirname(__FILE__))).'/classes/OAuthModule.php');

/**
 * LinkedIn authorization modlue
 *
 * Provides LinkedIn authentication and API access using OAuth
 * Register your app here: https://www.linkedin.com/secure/developer
 *
 * @package StartupAPI
 * @subpackage Authentication\Linkedin
 */
class LinkedInAuthenticationModule extends OAuthAuthenticationModule
{
	protected $userCredentialsClass = 'LinkedInUserCredentials';

	/**
	 * Instantiates LinkedIn authentication module and registers it with the system
	 *
	 * @param string $oAuthConsumerKey OAuth Consumer Key
	 * @param string $oAuthConsumerSecret OAuth Consumer Secret
	 * @param string $oAuthScope Requested permission scopes (zero or more scope strings, usually URLs, separated by spaces)
	 */
	public function __construct($oAuthConsumerKey, $oAuthConsumerSecret, $oAuthScope = 'basic')
	{
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
			UserConfig::$USERSROOTURL.'/modules/linkedin/linkedin-small.png',
			UserConfig::$USERSROOTURL.'/modules/linkedin/linkedin-small.png',
			UserConfig::$USERSROOTURL.'/modules/linkedin/linkedin-small.png'
		);
	}

	public function getID()
	{
		return "linkedin";
	}

	public function getLegendColor()
	{
		return "4291ba";
	}

	public function getTitle()
	{
		return "LinkedIn";
	}

	public function getIdentity($oauth_user_id) {
		$request = new OAuthRequester('http://api.linkedin.com/v1/people/~:(id,formatted-name,picture-url,public-profile-url)?format=json', 'GET');
		$result = $request->doRequest($oauth_user_id);

		if ($result['code'] == 200) {
			$userdata = json_decode($result['body'], true);

			if (array_key_exists('id', $userdata) && array_key_exists('formattedName', $userdata)) {
				$userdata['name'] = $userdata['formattedName'];
				unset($userdata['formattedName']);
				return $userdata;
			}
		}

		return null;
	}

	protected function renderUserInfo($serialized_userinfo) {
		$user_info = unserialize($serialized_userinfo);
		?><script src="//platform.linkedin.com/in.js" type="text/javascript"></script>
		<script type="IN/MemberProfile" data-id="<?php echo UserTools::escape($user_info['publicProfileUrl']); ?>" data-format="hover" data-text="<?php echo UserTools::escape($user_info['name']); ?>" data-related="false"></script>
<?php
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
		return '<a href="'.UserTools::escape($this->userinfo['publicProfileUrl']).'" target="_blank">'.$this->userinfo['name'].'</a>';
	}
}
