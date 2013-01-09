<?php
require_once(dirname(dirname(__DIR__)).'/classes/OAuthModule.php');

/**
 * Authenticates users using Etsy (etsy.com) and providers access to their API using OAuth
 *
 * Register your app here: https://www.etsy.com/developers/register to get OAuth key and secret
 *
 * @package StartupAPI
 * @subpackage Authentication\Etsy
 */
class EtsyAuthenticationModule extends OAuthAuthenticationModule
{
	protected $userCredentialsClass = 'EtsyUserCredentials';

	/**
	 * @var string Base URL for API calls (prod or sandbox), set based on sandbox flag
	 */
	private $base_url;

	/**
	 * Initializes EtsyAuthenticationModule and registers it in the system
	 *
	 * @param string $oAuthConsumerKey Etsy OAuth consumer key
	 * @param string $oAuthConsumerSecret Etsy OAuth consumer secret
	 * @param string $oAuthScope Etsy permissions scope (http://www.etsy.com/developers/documentation/getting_started/oauth#section_permission_scopes)
	 * @param boolean $sandbox True for sandbox, false for production (default)
	 *
	 * @see https://www.etsy.com/developers/register
	 * @see https://www.etsy.com/developers/your-apps
	 * @see http://www.etsy.com/developers/documentation/getting_started/oauth#section_permission_scopes
	 */
	public function __construct($oAuthConsumerKey, $oAuthConsumerSecret, $oAuthScope = 'email_r', $sandbox = false)
	{
		// !!! attention !!!
		// clear database entry in <prefix>_oauth_consumer_registry table for Etsy
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

	/**
	 * Returns module's ID string - "etsy"
	 *
	 * @return string Always returns "etsy"
	 */
	public function getID()
	{
		return "etsy";
	}

	/**
	 * Returns legend color
	 *
	 * @return string Always returns Etsy-orange ("d55f15")
	 */
	public function getLegendColor()
	{
		return "d55f15";
	}

	/**
	 * Returns mogue title
	 *
	 * @return string Always returns "Etsy"
	 */
	public function getTitle()
	{
		return "Etsy";
	}

	/**
	 * We tried to use authorize_uri returned with the OAuth token, but it didn't work,
	 * hardcoding https://www.etsy.com/oauth/signin instead
	 */
#	protected function getAuthorizeURL($tokenResultParameters) {
#		return $tokenResultParameters['authorize_uri'];
#	}

	/**
	 * Returns user identity, id, login name and primary_email in case of etsy
	 *
	 * @param int $oauth_user_id OAuth user ID
	 *
	 * @return array Identity info array
	 */
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

	/**
	 * Displays user's login name with the link to their Etsy store
	 *
	 * @param string $serialized_userinfo Serialized user information array
	 */
	protected function renderUserInfo($serialized_userinfo) {
		$user_info = unserialize($serialized_userinfo);
		?><a href="http://<?php echo UserTools::escape($user_info['name']); ?>.etsy.com/" target="_blank">
<?php echo UserTools::escape($user_info['name']); ?></a><?php
	}
}

/**
 * Etsy user credentials
 *
 * @package StartupAPI
 * @subpackage Authentication\Etsy
 */
class EtsyUserCredentials extends OAuthUserCredentials {
	public function getHTML() {
		return '<a href="http://'.UserTools::escape($this->userinfo['name']).'.etsy.com/" target="_blank">'.UserTools::escape($this->userinfo['name']).'</a>';
	}
}
