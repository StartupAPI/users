<?php
require_once(dirname(dirname(__DIR__)) . '/classes/OAuthModule.php');

/**
 * Ohloh authentication module
 *
 * Provides authentication using Ohloh.com accounts and API access using OAuth
 *
 * @package StartupAPI
 * @subpackage Authentication\Ohloh
 */
class OhlohAuthenticationModule extends OAuthAuthenticationModule {

	protected $userCredentialsClass = 'OhlohUserCredentials';

	/**
	 * Instantiates Ohloh authentication module and registers it with the system
	 *
	 * @param string $oAuthConsumerKey OAuth Consumer Key
	 * @param string $oAuthConsumerSecret OAuth Consumer Secret
	 */
	public function __construct($oAuthConsumerKey, $oAuthConsumerSecret) {
		parent::__construct(
			'Ohloh',
			'http://www.ohloh.com',
			$oAuthConsumerKey,
			$oAuthConsumerSecret,
			'http://www.ohloh.net/oauth/request_token',
			'https://api.twitter.com/oauth/access_token',
			'http://www.ohloh.net/oauth/access_token',
			array('HMAC-SHA1'),
			'http://www.ohloh.com',
			null,
			null,
			null,
#			UserConfig::$USERSROOTURL.'/modules/twitter/login-button.png',
#			UserConfig::$USERSROOTURL.'/modules/twitter/login-button.png',
#			UserConfig::$USERSROOTURL.'/modules/twitter/login-button.png',
			array(
				array(5001, "Logged in using Ohloh account", 1),
				array(5002, "Added Ohloh account", 1),
				array(5003, "Removed Ohloh account", 0),
				array(5004, "Registered using Ohloh account", 1),
			)
		);
	}

	public function getID() {
		return "ohloh";
	}

	public function getLegendColor() {
		return "868686";
	}

	public static function getModulesTitle() {
		return "Ohloh";
	}

	public function getIdentity($oauth_user_id) {
		// get twitter handle
		$request = new OAuthRequester('http://www.ohloh.net/accounts/me.xml', 'GET');
		$result = $request->doRequest($oauth_user_id);

		if ($result['code'] == 200) {
			$raw_xml = $result['body'];
			$xml = new SimpleXMLElement($raw_xml);

			// todo add more fields
			return array(
				'id' => (string) $xml->id,
				'name' => (string) $xml->name
			);
		}

		return null;
	}

	public static function getModulesDescription() {
		return <<<EOF
			<p>Ohloh authentication module</p>
			<p>Provides authentication using Ohloh.com accounts and API access using OAuth</p>
EOF;
	}

	protected function renderUserInfo($serialized_userinfo) {
		$user_info = unserialize($serialized_userinfo);
		?><a href="http://www.ohloh.net/accounts/<?php echo UserTools::escape($user_info['id']); ?>" target="_blank"><?php echo UserTools::escape($user_info['name']); ?></a>
		<?php
	}

}

/**
 * Ohloh user credentials class
 *
 * @package StartupAPI
 * @subpackage Authentication\Ohloh
 */
class OhlohUserCredentials extends OAuthUserCredentials {

	public function getHTML() {
		return '<a href="http://www.ohloh.net/accounts/' . UserTools::escape($user_info['id']) . '" target="_blank">@' . $this->userinfo['name'] . '</a>';
	}

}
