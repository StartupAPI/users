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

		if ($stmt = $db->prepare('SELECT count(*) AS reqs FROM (SELECT u.id FROM '.UserConfig::$mysql_prefix.'users u LEFT JOIN '.UserConfig::$mysql_prefix.'user_oauth_identity oa ON u.id = oa.user_id WHERE regtime > DATE_SUB(NOW(), INTERVAL 30 DAY) AND oa.oauth_user_id IS NOT NULL GROUP BY id) AS agg'))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($regs))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $regs;
	}

	/*
	 * retrieves aggregated registrations numbers
	 */
	public function getDailyRegistrations()
	{
		$db = UserConfig::getDB();

		$dailyregs = array();

		if ($stmt = $db->prepare('SELECT regdate, count(*) AS reqs FROM (SELECT CAST(regtime AS DATE) AS regdate, id AS regs FROM '.UserConfig::$mysql_prefix.'users u LEFT JOIN '.UserConfig::$mysql_prefix.'user_oauth_identity oa ON u.id = oa.user_id WHERE oa.oauth_user_id IS NOT NULL GROUP BY id) agg group by agg.regdate'))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($regdate, $regs))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$dailyregs[] = array('regdate' => $regdate, 'regs' => $regs);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $dailyregs;
	}
}
