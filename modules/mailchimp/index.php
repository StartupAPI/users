<?php
class MailChimpModule extends EmailModule
{
	private $apiKey;
	private $listID;

	private $endpoint;

	public function getID()
	{
		return "mailchimp";
	}

	public function getTitle()
	{
		return "MailChimp";
	}

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
	 * This function should be called when new user is created
	 * or email is recorded for the user for the first time
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

	/**
	 * This function should be called when user information has changed
	 * e.g. email address or additional information passed to provider like name or gender and etc.
	 */
	public function updateSubscriber($old_user, $new_user) {
		// TODO Implement subscriber info update
		error_log("MailChimp::updateSubscriber method is not implemented yet");
	}

	/**
	 * This function should be called when user chose to unsubscribe from the mailing list
	 */
	public function removeSubscriber($user) {
		// TODO Implement subscriber removal
		error_log("MailChimp::removeSubscriber method is not implemented yet");
	}

	/**
	 * This method is called by userChanged (implemented in parent class)
	 * @return boolean Returns true if user's information has changed and needs to be synced
	 */
	public function hasUserInfoChanged($old_user, $new_user) {
		// we only track user name change so far
		return ($old_user->getName() != $new_user->getName());
	}
}

class MailChimpException extends Exception { }
