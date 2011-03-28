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
		// handles module registration in UserBase
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
		$data = array(
			'output' => 'json',
			'apikey' => $this->apiKey,

			'method' => 'listSubscribe',

			'id' => $this->listID,
			'email_address' => $user->getEmail(),
			'merge' => array(
				'NAME' => $user->getName()
			)
		);

		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $this->endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

		$result = curl_exec($ch); 

		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
			throw new MailChimpException("API call failed: ".curl_error($ch));
		}
		curl_close($ch);

		if ($result !== 'true') {
			throw new EmailSubscriptionException("MailChimp subscription failed");
		}
	}

	/**
	 * This function should be called when user information has changed
	 * e.g. email address or additional information passed to provider like name or gender and etc.
	 */
	public function updateSubscriber($user) {
		// TODO Implement subscriber info update
	}

	/**
	 * This function should be called when user chose to unsubscribe from the mailing list
	 */
	public function removeSubscriber($user) {
		// TODO Implement subscriber removal
	}
}

class MailChimpException extends Exception { }
