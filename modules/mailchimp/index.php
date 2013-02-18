<?php
/**
 * MailChip mailing list integration module (UNFINISHED)
 *
 * Syncronizes user information with MailChimp mailing list
 *
 * @todo Finish the implementation
 *
 * @package StartupAPI
 * @subpackage Email\MailChimp
 */
class MailChimpModule extends EmailModule
{
	/**
	 * @var string MailChimp API key
	 */
	private $apiKey;

	/**
	 * @var string Mail list ID to syncronize with
	 */
	private $listID;

	/**
	 * @var string API Endpoint URL (based on datacenter)
	 */
	private $endpoint;

	public function getID()
	{
		return "mailchimp";
	}

	public static function getModulesTitle() {
		return "MailChimp";
	}

	public function getDescription() {
		return "<p>MailChip mailing list integration module (UNFINISHED)</p>
				<p>Syncronizes user information with MailChimp mailing list.</p>";
	}

	public function getLogo($size = 100) {
		if ($size == 100) {
			return UserConfig::$USERSROOTURL . '/modules/mailchimp/images/logo_100x.png';
		}
	}

	/**
	 * Instantiates MailChimp module and registers it with the system
	 *
	 * @param string $apiKey MailChimp API key
	 * @param string $listID Mail list ID
	 *
	 * @throws MailChimpException
	 */
	public function __construct($apiKey, $listID) {
		// handles module registration in Startup API
		parent::__construct();

		$this->apiKey = $apiKey;
		$this->listID = $listID;

		$pos = strpos($apiKey, '-');

		if ($pos === FALSE) {
			throw new MailChimpException("Wrong API key. Expected '-' in it somewhere.");
		}

		$datacenter = substr($apiKey, $pos + 1);

		$this->endpoint = 'http://'.$datacenter.'.api.mailchimp.com/1.3/';
	}

	/**
	 * Adds new subscriber to MailChimp mailing list
	 *
	 * @param User $user Subscriber's User object
	 *
	 * @throws MailChimpException
	 * @throws EmailSubscriptionException
	 */
	public function registerSubscriber($user) {
		error_log("Registering new MailChimp subscriber: ".$user->getName().' <'.$user->getEmail().'>');

		$url = $this->endpoint.'?output=json&method=listSubscribe&apikey='.urlencode($this->apiKey)
			. '&id='.urlencode($this->listID)
			. '&email_address='.urlencode($user->getEmail())
			. '&merge_vars='.urlencode(json_encode(array('NAME' => $user->getName())));

		error_log('URL: '.var_export($url, true));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);

		$result = curl_exec($ch);

		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
			throw new MailChimpException("API call failed: ".curl_error($ch));
		}
		curl_close($ch);

		if ($result !== 'true') {
			throw new EmailSubscriptionException("MailChimp subscription failed: ".var_export($result, true));
		}
	}

	public function updateSubscriber($old_user, $new_user) {
		// TODO Implement subscriber info update
		error_log("MailChimp::updateSubscriber method is not implemented yet");
	}

	public function removeSubscriber($user) {
		// TODO Implement subscriber removal
		error_log("MailChimp::removeSubscriber method is not implemented yet");
	}

	/**
	 * This method is called by userChanged (implemented in parent class)
	 *
	 * @return boolean Returns true if user's information has changed and needs to be sync'ed
	 */
	public function hasUserInfoChanged($old_user, $new_user) {
		// we only track user name change so far
		return ($old_user->getName() != $new_user->getName());
	}
}

/**
 * MailChimp integration exception
 *
 * @package StartupAPI
 * @subpackage Email\MailChimp
 */
class MailChimpException extends StartupAPIException { }
