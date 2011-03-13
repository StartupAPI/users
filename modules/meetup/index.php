<?php
require_once(dirname(dirname(dirname(__FILE__))).'/OAuthModule.php');

class MeetupAuthenticationModule extends OAuthAuthenticationModule
{
	public function __construct($oAuthConsumerKey, $oAuthConsumerSecret, $remember = true)
	{
		parent::__construct(
			'Meetup',
			'http://api.meetup.com',
			$oAuthConsumerKey,
			$oAuthConsumerSecret,
			'https://api.meetup.com/oauth/request/',
			'https://api.meetup.com/oauth/access/',
			'http://www.meetup.com/authorize/',
			array('HMAC-SHA1', 'PLAINTEXT'),
			UserConfig::$USERSROOTFULLURL.'/modules/meetup/oauth_callback.php',
			$remember
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

	public function getUserCredentials($user)
	{
		$db = UserConfig::getDB();

		$userid = $user->getID();

		# TODO get user's meetup credentials

		return null;
	}

	public function getIdentity($oauth_user_id) {
		// get meetup user id
		$request = new OAuthRequester('https://api.meetup.com/members.json/?relation=self', 'GET');
		$result = $request->doRequest($oauth_user_id);

		if ($result['code'] == 200) {
			$userdata = json_decode($result['body'], true);

			// array includes 'id' parameter which uniquely identifies a user
			if (array_key_exists('id', $userdata['results'][0])) {
				return $userdata['results'][0];
			}
		}

		return null;
	}

	/*
	 * retrieves recent aggregated registrations numbers
	 */
	public function getRecentRegistrations()
	{
		$db = UserConfig::getDB();

		$regs = 0;

		# TODO Implement getting a number of recent users registered using Meetup

		return $regs;
	}

	/*
	 * retrieves aggregated registrations numbers
	 */
	public function getDailyRegistrations()
	{
		$db = UserConfig::getDB();

		$dailyregs = array();

		# TODO Implement getting a number of users registered using Meetup by day

		return $dailyregs;
	}
}
