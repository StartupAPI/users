<?php
namespace StartupAPI\Modules;

/**
 * Authenticates users using Etsy (etsy.com) and providers access to their API using OAuth
 *
 * Register your app here: https://www.etsy.com/developers/register to get OAuth key and secret
 *
 * @package StartupAPI
 * @subpackage Authentication\Etsy
 */
class EtsyAuthenticationModule extends \StartupAPI\OAuthAuthenticationModule
{
	protected $userCredentialsClass = '\StartupAPI\Modules\EtsyAuthenticationModule\EtsyUserCredentials';

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
			\StartupAPI\UserConfig::$USERSROOTURL.'/modules/etsy/login-button.png',
			\StartupAPI\UserConfig::$USERSROOTURL.'/modules/etsy/login-button.png',
			\StartupAPI\UserConfig::$USERSROOTURL.'/modules/etsy/login-button.png',
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

	public static function getModulesTitle() {
		return "Etsy";
	}

	public static function getModulesDescription() {
		return "<p>Authenticates users using Etsy (etsy.com) and providers access to their API using OAuth</p>";
	}

	public function getDescription() {
		return self::getModulesDescription();
	}

	public static function getModulesLogo($size = 100) {
		if ($size == 100) {
			return \StartupAPI\UserConfig::$USERSROOTURL . '/modules/etsy/images/logo_100x.png';
		}
	}

	public static function getSignupURL() {
		return 'https://www.etsy.com/developers/register';
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
		$request = new \OAuthRequester($this->base_url.'/private/users/__SELF__', 'GET');
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
	 *
	 * @return string Rendered user information HTML
	 */
	protected function renderUserInfo($serialized_userinfo) {
		$template_info = unserialize($serialized_userinfo);
		if (!is_array($template_info)) {
			$template_info = array();
		}
		return \StartupAPI\StartupAPI::$template->render("@startupapi/modules/etsy/user_info.html.twig", $template_info);
	}
}
