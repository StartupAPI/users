<?php
require_once(dirname(dirname(dirname(__FILE__))).'/OAuthModule.php');

/**
 * @package StartupAPI
 * @subpackage Authentication\Etsy
 */
class EtsyAuthenticationModule extends OAuthAuthenticationModule
{
	protected $userCredentialsClass = 'EtsyUserCredentials';
	private $base_url;

	public function __construct($oAuthConsumerKey, $oAuthConsumerSecret, $oAuthScope = 'email_r', $sandbox = false)
	{
		// !!! attention !!!
		// clear <prefix>_oauth_consumer_registry entry for Etsy
		// before switching between sandbox and production
		if ($sandbox) {
			$this->base_url = 'http://sandbox.openapi.etsy.com/v2';
		} else {
			$this->base_url = 'http://openapi.etsy.com/v2';
		}

		parent::__construct(
			'Etsy',
			$this->base_url.'/',
			$oAuthConsumerKey,
			$oAuthConsumerSecret,
			$this->base_url.'/oauth/request_token',
			$this->base_url.'/oauth/access_token',
			'https://www.etsy.com/oauth/signin',
			array('HMAC-SHA1', 'PLAINTEXT'),
			$oAuthScope,
			UserConfig::$USERSROOTURL.'/modules/etsy/login-button.png',
			UserConfig::$USERSROOTURL.'/modules/etsy/login-button.png',
			UserConfig::$USERSROOTURL.'/modules/etsy/login-button.png',
			array(
				array(7001, "Logged in using Etsy account", 1),
				array(7002, "Added Etsy account", 1),
				array(7003, "Removed Etsy account", 0),
				array(7004, "Registered using Etsy account", 1),
			)
		);
	}

	public function getID()
	{
		return "etsy";
	}

	public function getLegendColor()
	{
		return "d55f15";
	}

	public function getTitle()
	{
		return "Etsy";
	}

#	protected function getAuthorizeURL($tokenResultParameters) {
#		return $tokenResultParameters['authorize_uri'];
#	}

	public function getIdentity($oauth_user_id) {
		// get etsy user id
		$request = new OAuthRequester($this->base_url.'/private/users/__SELF__', 'GET');
		$result = $request->doRequest($oauth_user_id);

		if ($result['code'] == 200) {
			$userdata = json_decode($result['body'], true);

			$identity = $userdata['results'][0];

			// array includes 'id' parameter which uniquely identifies a user
			if (array_key_exists('user_id', $identity)
				&& array_key_exists('login_name', $identity)
			) {
				$identity['id'] = $identity['user_id'];
				$identity['name'] = $identity['login_name'];

				if (array_key_exists('primary_email', $identity)) {
					$identity['email'] = $identity['primary_email'];
				}

				return $identity;
			}
		}

		return null;
	}

	protected function renderUserInfo($serialized_userinfo) {
		$user_info = unserialize($serialized_userinfo);
		?><a href="http://<?php echo UserTools::escape($user_info['name']); ?>.etsy.com/" target="_blank">
<?php echo UserTools::escape($user_info['name']); ?></a><?php
	}
}

/**
 * @package StartupAPI
 * @subpackage Authentication\Etsy
 */
class EtsyUserCredentials extends OAuthUserCredentials {
	public function getHTML() {
		return '<a href="http://'.UserTools::escape($this->userinfo['name']).'.etsy.com/" target="_blank">'.UserTools::escape($this->userinfo['name']).'</a>';
	}
}
