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
		$serialized_userinfo = $this->getUserInfo($user);
		if (is_null($serialized_userinfo)) {
			return null;
		}

		$userinfo = unserialize($serialized_userinfo);


		return '<a href="'.UserTools::escape($userinfo['link']).'" target="_blank">'.$userinfo['name'].'</a>';
	}

	public function getIdentity($oauth_user_id) {
		// get meetup user id
		$request = new OAuthRequester('https://api.meetup.com/members.json/?relation=self', 'GET');
		$result = $request->doRequest($oauth_user_id);

		if ($result['code'] == 200) {
			$userdata = json_decode($result['body'], true);

			// array includes 'id' parameter which uniquely identifies a user
			if (array_key_exists('id', $userdata['results'][0])
				&& array_key_exists('name', $userdata['results'][0])
			) {
				return $userdata['results'][0];
			}
		}

		return null;
	}

	protected function renderUserInfo($serialized_userinfo) {
		$user_info = unserialize($serialized_userinfo);
		?><a href="<?php echo UserTools::escape($user_info['link']); ?>" target="_blank"><?php echo UserTools::escape($user_info['name']); ?></a><br/>
		<img src="<?php echo UserTools::escape($user_info['photo_url']); ?>" style="max-width: 60px; max-height: 60px"/><?php
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
