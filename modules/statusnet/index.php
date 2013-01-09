<?php
require_once(dirname(dirname(__DIR__)).'/classes/OAuthModule.php');

/**
 * StatusNet authentication module
 *
 * Provides authentication using StatusNet open source microblogging platform
 * that powers http://identi.ca and other public and private sites.
 *
 * Provides access to Twitter-based and other APIs using OAuth
 *
 * @package StartupAPI
 * @subpackage Authentication\StatusNet
 */
class StatusNetAuthenticationModule extends OAuthAuthenticationModule
{
	/**
	 * @var string Provider name
	 */
	private $title;

	/**
	 * @var string Root URL of StatusNet installation
	 */
	private $rootURL;

	/**
	 * @var string Root URL of API endpoint
	 */
	private $APIRootURL;

	protected $userCredentialsClass = 'StatusNetUserCredentials';

	/**
	 * Instantiates StatusNet authentication module and registers it with the system
	 *
	 * Defaults are for Identi.ca server, but it can be used with any StatusNet instance
	 *
	 * @param string $oAuthConsumerKey OAuth Consumer Key
	 * @param string $oAuthConsumerSecret OAuth Consumer Secret
	 * @param string $title Provider name
	 * @param string $rootURL Root URL of StatusNet installation
	 * @param string $APIrootURL Root URL of API endpoint
	 */
	public function __construct($oAuthConsumerKey, $oAuthConsumerSecret, $title = 'Identi.ca', $rootURL = 'https://identi.ca/', $APIrootURL = null)
	{
		/** Support auth through multiple statusnet services in the same instance */

		$this->title = $title;
		$this->rootURL = $rootURL;

		if (is_null($APIrootURL)) {
			$this->APIRootURL = $rootURL.'api/';
		} else {
			$this->APIRootURL = $APIrootURL;
		}

		parent::__construct(
			$this->title,
			$this->APIRootURL,
			$oAuthConsumerKey,
			$oAuthConsumerSecret,
			$this->APIRootURL.'oauth/request_token',
			$this->APIRootURL.'oauth/access_token',
			$this->APIRootURL.'oauth/authorize',
			array('HMAC-SHA1'),
			$this->APIRootURL,
			UserConfig::$USERSROOTURL.'/modules/statusnet/StatusNet_badge_green.png',
			UserConfig::$USERSROOTURL.'/modules/statusnet/StatusNet_badge_green.png',
			UserConfig::$USERSROOTURL.'/modules/statusnet/StatusNet_badge_green.png',
			array(
				array(4501, 'Logged in using '.$this->title.' account', 1),
				array(4502, 'Added '.$this->title.' account', 1),
				array(4503, 'Removed '.$this->title.' account', 0),
				array(4504, 'Registered using '.$this->title.' account', 1),
			)
		);
	}

	public function getID()
	{
		return "statusnet";
	}

	public function getLegendColor()
	{
		return "91a93b";
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function getIdentity($oauth_user_id) {
		// get twitter handle
		$request = new OAuthRequester($this->APIRootURL.'account/verify_credentials.json', 'GET');
		$result = $request->doRequest($oauth_user_id);

		if ($result['code'] == 200) {
			$data = json_decode($result['body'], true);
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

			if (!is_null($data) && array_key_exists('id', $data) && array_key_exists('name', $data)) {
				return $data;
			}
		}

		return null;
	}

	protected function renderUserInfo($serialized_userinfo) {
		$user_info = unserialize($serialized_userinfo);
		?>@<a href="<?php echo $this->rootURL . UserTools::escape($user_info['screen_name']); ?>" target="_blank"><?php echo UserTools::escape($user_info['screen_name']); ?></a><br/><?php
	}

	/**
	 * Calls Status.Net Twitter API using OAuth
	 *
	 * @param string $path API method / URL path
	 * @param string $method HTTP method, e.g. GET/POST/PUT and etc.
	 * @param array $params Parameters to pass
	 * @param string $body Request body, if any
	 * @param array $files Array of file names to upload (if API supports it)
	 *
	 * @return mixed API response
	 */
	protected function api_call($path, $method = "GET", $params = null, $body = null, $files = null) {
		return makeOAuthRequest(
			$this->$APIRootURL.$path,
			$method,
			$params,
			$body,
			$files);
	}
}

/**
 * StatusNet credentials
 *
 * @package StartupAPI
 * @subpackage Authentication\StatusNet
 */
class StatusNetUserCredentials extends OAuthUserCredentials {
	public function getHTML() {
		return '@<a href="'.$this->rootURL . UserTools::escape($this->userinfo['screen_name']).'" target="_blank">'.$this->userinfo['screen_name'].'</a>';
	}
}
