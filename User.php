<?php
/*
 * User class
*/
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/Account.php');
require_once(dirname(__FILE__).'/CookieStorage.php');
require_once(dirname(__FILE__).'/CampaignTracker.php');

class User
{
	/*
	 * Checks if user is logged in and returns use object or redirects to login page
	 */
	public static function require_login($impersonate = true)
	{
		$user = self::get($impersonate);

		if (!is_null($user))
		{
			return $user;
		}
		else
		{
			User::redirectToLogin();
		}
	}

	/*
	 * Checks if user is logged in and returns use object or null if user is not logged in
	 * Disabled users are not allowed to login unless they are being impersonated
	 */
	public static function get($impersonate = true)
	{
		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
			'path' => UserConfig::$SITEROOTURL,
			'httponly' => true
		));

		$userid = $storage->fetch(UserConfig::$session_userid_key);

		if (is_numeric($userid)) {
			$user = self::getUser($userid);

			if (is_null($user)) {
				return null;
			}

			// only forsing password reset on non-impersonated users
			if ($user->requiresPasswordReset() &&
				!UsernamePasswordAuthenticationModule::$IGNORE_PASSWORD_RESET)
			{
				User::redirectToPasswordReset();
			}

			// don't event try impersonating if not admin
			if (!$impersonate || !$user->isAdmin()) {
				if ($user->isDisabled()) {
					return null;
				}

				return $user;
			}

			// now, let's check impersonation
			$impersonated_userid = $storage->fetch(UserConfig::$impersonation_userid_key);
			$impersonated_user = self::getUser($impersonated_userid);

			// do not impersonate unknown user or the same user
			if (is_null($impersonated_user) || $user->isTheSameAs($impersonated_user)) {
				if ($user->isDisabled()) {
					return null;
				}

				return $user;
			}

			$impersonated_user->impersonator = $user;

			return $impersonated_user;
		} else {
			return null;
		}
	}

	public static function updateReturnActivity() {
		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
			'path' => UserConfig::$SITEROOTURL,
			'httponly' => true
		));

		$last = $storage->fetch(UserConfig::$last_login_key);
		if (!$storage->store(UserConfig::$last_login_key, time())) { 
			throw new Exception(implode('; ', $storage->errors));
		}

		$user = self::get();

		if (!is_null($user) && $last > 0
			&& $last < time() - UserConfig::$last_login_session_length * 60)
		{
			if ($last > time() - 86400) {
				$user->recordActivity(USERBASE_ACTIVITY_RETURN_DAILY);
			} else if ($last > time() - 7 * 86400) {
				$user->recordActivity(USERBASE_ACTIVITY_RETURN_WEEKLY);
			} else if ($last > time() - 30 * 86400) {
				$user->recordActivity(USERBASE_ACTIVITY_RETURN_MONTHLY);
			}
		}
	}

	private function setReferer() {
		$referer = CampaignTracker::getReferer();
		if (is_null($referer)) {
			return;
		}

		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'users SET referer = ? WHERE id = ?'))
		{
			if (!$stmt->bind_param('si', $referer, $this->userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}
	}

	public function getReferer() {
		$db = UserConfig::getDB();

		$referer = null;

		if ($stmt = $db->prepare('SELECT referer FROM '.UserConfig::$mysql_prefix.'users WHERE id = ?'))
		{
			if (!$stmt->bind_param('i', $this->userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->bind_result($referer))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $referer;
	}

	private function setRegCampaign() {
		$campaign = CampaignTracker::getCampaign();
		if (is_null($campaign) || !$campaign) {
			return;
		}

		$db = UserConfig::getDB();

		$cmp_source_id = null;
		if (array_key_exists('cmp_source', $campaign)) {
			$cmp_source_id = CampaignTracker::getCampaignSourceID($campaign['cmp_source']);
		}

		$cmp_medium_id = null;
		if (array_key_exists('cmp_medium', $campaign)) {
			$cmp_medium_id = CampaignTracker::getCampaignMediumID($campaign['cmp_medium']);
		}

		$cmp_keywords_id = null;
		if (array_key_exists('cmp_keywords', $campaign)) {
			$cmp_keywords_id = CampaignTracker::getCampaignKeywordsID($campaign['cmp_keywords']);
		}

		$cmp_content_id = null;
		if (array_key_exists('cmp_content', $campaign)) {
			$cmp_content_id = CampaignTracker::getCampaignContentID($campaign['cmp_content']);;
		}

		$cmp_name_id = null;
		if (array_key_exists('cmp_name', $campaign)) {
			$cmp_name_id = CampaignTracker::getCampaignNameID($campaign['cmp_name']);
		}

		// update user record with compaign IDs
		if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'users SET
			reg_cmp_source_id = ?,
			reg_cmp_medium_id = ?,
			reg_cmp_keywords_id = ?,
			reg_cmp_content_id = ?,
			reg_cmp_name_id = ?
			WHERE id = ?'))
		{
			if (!$stmt->bind_param('sssssi',
				$cmp_source_id,
				$cmp_medium_id,
				$cmp_keywords_id,
				$cmp_content_id,
				$cmp_name_id,
				$this->userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}
	}

	private function init()
	{
		$db = UserConfig::getDB();

		if (UserConfig::$useAccounts) {
			$userid = $this->getID();

			if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'user_preferences (user_id) VALUES (?)'))
			{
				if (!$stmt->bind_param('i', $userid))
				{
					throw new Exception("Can't bind parameter");
				}
				if (!$stmt->execute())
				{
					throw new Exception("Can't update user preferences (set current account)");
				}
				$stmt->close();
			}
			else
			{
				throw new Exception("Can't update user preferences (set current account)");
			}

			$personal = Account::createAccount($this->getName(),UserConfig::$default_plan, NULL, $this, Account::ROLE_ADMIN, NULL);

			$personal->setAsCurrent($this);

		}

		if (!is_null(UserConfig::$onCreate))
		{
			call_user_func_array(UserConfig::$onCreate, array($this));
		}

		if (!is_null(UserConfig::$email_module)) {
			UserConfig::$email_module->registerSubscriber($this);
		}
	}
	/*
	 * create new user based on Google Friend Connect info
	 */
	public static function createNewGoogleFriendConnectUser($name, $googleid, $userpic)
	{
		$name = mb_convert_encoding($name, 'UTF-8');

		$db = UserConfig::getDB();

		$user = null;

		if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix."users (name, regmodule) VALUES (?, 'google' )"))
		{
			if (!$stmt->bind_param('s', $name))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			$id = $stmt->insert_id;

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'googlefriendconnect (user_id, google_id, userpic) VALUES (?, ?, ?)'))
		{
			if (!$stmt->bind_param('iss', $id, $googleid, $userpic))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		$user = self::getUser($id);
		$user->setReferer();
		$user->setRegCampaign();
		$user->init();

		return $user;
	}
	/*
	 * create new user based on facebook info
	 */
	public static function createNewFacebookUser($name, $fb_id, $me = null)
	{
		$name = mb_convert_encoding($name, 'UTF-8');

		$db = UserConfig::getDB();

		$email = null;
		if (array_key_exists('email', $me)) {
			$email = $me['email'];
		}

		$user = null;

		if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix."users (name, regmodule, email, fb_id) VALUES (?, 'facebook', ?, ?)"))
		{
			if (!$stmt->bind_param('ssi', $name, $email, $fb_id))
			{
				throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			$id = $stmt->insert_id;


			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		$user = self::getUser($id);
		$user->setReferer();
		$user->setRegCampaign();
		$user->init();

		return $user;
	}

	/*
	 * create new user without credentials
	 */
	public static function createNewWithoutCredentials($name, $email = null)
	{
		$name = mb_convert_encoding($name, 'UTF-8');

		$db = UserConfig::getDB();

		$user = null;

		$email = filter_var($email, FILTER_VALIDATE_EMAIL);
		if ($email === FALSE) {
			$email = null;
		}

		if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'users (name, email) VALUES (?, ?)'))
		{
			if (!$stmt->bind_param('ss', $name, $email))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			$id = $stmt->insert_id;

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		$user = self::getUser($id);
		$user->setReferer();
		$user->setRegCampaign();
		$user->init();

		return $user;
	}

	/*
	 * create new user
	 */
	public static function createNew($name, $username, $email, $password)
	{
		$name = mb_convert_encoding($name, 'UTF-8');
		$username = mb_convert_encoding($username, 'UTF-8');

		$db = UserConfig::getDB();

		$user = null;

		$salt = uniqid();
		$pass = sha1($salt.$password);

		if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix."users (regmodule, name, username, email, pass, salt) VALUES ('userpass', ?, ?, ?, ?, ?)"))
		{
			if (!$stmt->bind_param('sssss', $name, $username, $email, $pass, $salt))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			$id = $stmt->insert_id;

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		$user = self::getUser($id);
		$user->setReferer();
		$user->setRegCampaign();
		$user->init();

		return $user;
	}

	/*
	 * Returns total number of users in the system
	 */
	public static function getTotalUsers()
	{
		$db = UserConfig::getDB();

		$total = 0;

		if ($stmt = $db->prepare('SELECT COUNT(*) FROM '.UserConfig::$mysql_prefix.'users'))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($total))
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

		return $total;
		
	}

	/*
	 * Returns a number of active users (with activity after one day from registration)
	 */
	public static function getActiveUsers($date = null)
	{
		$db = UserConfig::getDB();

		$total = 0;

		if (UserConfig::$adminActiveOnlyWithPoints) {
			$activities_with_points = array();

			foreach (UserConfig::$activities as $id => $activity) {
				if ($activity[1] > 0) {
					$activities_with_points[] = $id;
				}
			}

			// if there are no activities that can earn points, no users are active
			if (count($activities_with_points) == 0) {
				return 0;
			}

			$in = implode(', ', $activities_with_points);

			$query = 'SELECT count(*) AS total FROM (
					SELECT user_id, count(*)
					FROM '.UserConfig::$mysql_prefix.'activity a
					INNER JOIN '.UserConfig::$mysql_prefix.'users u
						ON a.user_id = u.id
					WHERE a.time > DATE_ADD(u.regtime, INTERVAL 1 DAY)
						AND a.time > DATE_SUB('.
						(is_null($date) ? 'NOW()' : '?').
						', INTERVAL 30 DAY)'.
						(is_null($date) ? '' : ' AND a.time < ?').'
						AND a.activity_id IN ('.$in.')
					GROUP BY user_id
				) AS active';
		} else {
			$query = 'SELECT count(*) AS total FROM (
					SELECT user_id, count(*)
					FROM '.UserConfig::$mysql_prefix.'activity a
					INNER JOIN '.UserConfig::$mysql_prefix.'users u
						ON a.user_id = u.id
					WHERE a.time > DATE_ADD(u.regtime, INTERVAL 1 DAY)
						AND a.time > DATE_SUB('.
						(is_null($date) ? 'NOW()' : '?').
						', INTERVAL 30 DAY)'.
						(is_null($date) ? '' : ' AND a.time < ?').'
					GROUP BY user_id
				) AS active';
		}

		if ($stmt = $db->prepare($query))
		{
			if (!is_null($date)) {
				if (!$stmt->bind_param('ss', $date, $date))
				{
					 throw new Exception("Can't bind parameter".$stmt->error);
				}
			}

			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($total))
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

		return $total;
	}

	/*
	 * retrieves daily active users based on algorythm defined in getActiveUsers($date)
	 */
	public static function getDailyActiveUsers($lastndays = null)
	{
		$db = UserConfig::getDB();

		$daily_activity = array();

		$start_date = null;
		$start_day = null;
		$start_month = null;
		$start_year = null;

		// getting start date
		if ($stmt = $db->prepare('SELECT CAST(MIN(time) AS DATE) AS activity_date,
			DAYOFMONTH(MIN(time)) as day,
			MONTH(MIN(time)) as month,
			YEAR(MIN(time)) as year
			FROM '.UserConfig::$mysql_prefix.'activity'.
			((!is_null($lastndays) && is_int($lastndays)) ?
				' WHERE time > DATE_SUB(NOW(), INTERVAL '.$lastndays.' DAY)' : '')
		))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($start_date, $start_day, $start_month, $start_year))
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

		// no activities recorded yet
		if (is_null($start_date)) {
			return array();
		}

		// now getting all cached numbers
		if ($stmt = $db->prepare('SELECT day, active_users
			FROM '.UserConfig::$mysql_prefix.'admin_daily_stats_cache'.
			((!is_null($lastndays) && is_int($lastndays)) ?
				' WHERE day > DATE_SUB(NOW(), INTERVAL '.$lastndays.' DAY)' : '')))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($date, $active_users))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$daily_activity[$date] = $active_users;
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		$timestamp = mktime(0, 0, 1, $start_month, $start_day, $start_year);
		$current_timestamp = time();

		$updates = array();

		while($timestamp < $current_timestamp) {
			$date = date('Y-m-d', $timestamp);

			if (!array_key_exists($date, $daily_activity)) {
				$active_users = self::getActiveUsers($date);

				$daily_activity[$date] = $active_users;
				$updates[$date] = $active_users;
			}

			$timestamp = strtotime("+1 day", $timestamp);
		}

		// saving newly calculated values into cache
		$totalupdates = count($updates);

		if ($totalupdates > 0) {
			$query = 'REPLACE INTO '.UserConfig::$mysql_prefix.'admin_daily_stats_cache
				(day, active_users) VALUES';

			$first = true;
			foreach ($updates as $date => $active_users) {
				if (!$first) {
					$query .= ',';
				}
				$query .= " ('$date', $active_users)";

				$first = false;
			}

			if ($stmt = $db->prepare($query))
			{
				if (!$stmt->execute())
				{
					throw new Exception("Can't execute statement: ".$stmt->error);
				}

				$stmt->close();
			}
			else
			{
				throw new Exception("Can't prepare statement: ".$db->error);
			}
		}

		return $daily_activity;
	}
	/*
	 * retrieves daily active users by activity
	 */
	public static function getDailyPointsByActivity($activityid)
	{
		$db = UserConfig::getDB();

		$daily_activity = array();

		if ($stmt = $db->prepare('SELECT CAST(time AS DATE) AS activity_date, count(*) AS cnt FROM '.UserConfig::$mysql_prefix.'activity WHERE activity_id = ? GROUP BY activity_date'))
		{
			if (!$stmt->bind_param('i', $activityid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($date, $cnt))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$daily_activity[$date] = $cnt;
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $daily_activity;
	}
	/*
	 * retrieves aggregated activity points 
	 */
	public static function getDailyActivityPoints($user)
	{
		$db = UserConfig::getDB();

		$daily_activity = array();

		$where = '';
		if (!is_null($user)) {
			$where = ' WHERE user_id = '.$user->getID().' ';
		} else if (count(UserConfig::$dont_display_activity_for) > 0) {
			$where = ' WHERE user_id NOT IN('.join(', ', UserConfig::$dont_display_activity_for).') ';
		}

		if ($stmt = $db->prepare('SELECT CAST(time AS DATE) AS activity_date, activity_id, count(*) AS total FROM '.UserConfig::$mysql_prefix.'activity '.$where.'GROUP BY activity_date, activity_id'))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($date, $id, $total))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$daily_activity[] = array('date' => $date, 'activity' => $id, 'total' => $total);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $daily_activity;
	}
	/*
	 * retrieves aggregated registrations numbers 
	 */
	public static function getDailyRegistrations()
	{
		$db = UserConfig::getDB();

		$dailyregs = array();

		if ($stmt = $db->prepare('SELECT CAST(regtime AS DATE) AS regdate, count(*) AS regs FROM '.UserConfig::$mysql_prefix.'users GROUP BY regdate'))
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

	/*
	 * retrieves aggregated registrations numbers by module
	 */
	public static function getDailyRegistrationsByModule()
	{
		$db = UserConfig::getDB();

		$dailyregs = array();

		if ($stmt = $db->prepare('SELECT CAST(regtime AS DATE) AS regdate, regmodule, count(*) AS reg FROM '.UserConfig::$mysql_prefix.'users GROUP BY regdate, regmodule'))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($date, $module, $regs))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch()) {
				$dailyregs[$date][$module] = $regs;
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $dailyregs;
	}

	/*
	 * retrieves aggregated recent registrations numbers by module
	 */
	public static function getRecentRegistrationsByModule()
	{
		$db = UserConfig::getDB();

		$regs = array();

		if ($stmt = $db->prepare('SELECT regmodule, count(*) AS reg FROM '.UserConfig::$mysql_prefix.'users u WHERE regtime > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY regmodule'))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($module, $reg))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch()) {
				$regs[$module] = $reg;
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $regs;
	}

	/*
	 * retrieves user credentials for all authentication modules
	 */
	public function getUserCredentials($requested_module_id = null)
	{
		$credentials = array();

		foreach (UserConfig::$authentication_modules as $module) {
			if (is_null($requested_module_id)) {
				$credentials[$module][] = $module->getUserCredentials($this);
			} else {
				if ($requested_module_id == $module->getID()) {
					return $module->getUserCredentials($this);
				}
			}
		}

		return $credentials;
	}

	/*
	 * retrieves paged list of users
	 */
	public static function getUsers($pagenumber = 0, $perpage = 20, $sort = 'registration')
	{
		$db = UserConfig::getDB();

		$users = array();

		$first = $perpage * $pagenumber;

		$orderby = 'regtime';
		if ($sort == 'activity') {
			$orderby = 'points';
		}

		if ($stmt = $db->prepare('SELECT id, status, name, username, email, requirespassreset, fb_id, UNIX_TIMESTAMP(regtime), points FROM '.UserConfig::$mysql_prefix.'users ORDER BY '.$orderby.' DESC LIMIT ?, ?'))
		{
			if (!$stmt->bind_param('ii', $first, $perpage))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $regtime, $points))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$users[] = new self($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $regtime, $points);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $users;
	}
	/*
	 * searches for users matching the query
	 */
	public static function searchUsers($search, $pagenumber = 0, $perpage = 20)
	{
		$db = UserConfig::getDB();

		$users = array();

		$first = $perpage * $pagenumber;

		// TODO Replace with real, fast and powerful full-text search
		if ($stmt = $db->prepare('SELECT id, status, name, username, email, requirespassreset, fb_id, UNIX_TIMESTAMP(regtime) FROM '.UserConfig::$mysql_prefix.'users WHERE INSTR(name, ?) > 0 OR INSTR(username, ?) > 0 OR INSTR(email, ?) > 0 ORDER BY regtime DESC LIMIT ?, ?'))
		{
			if (!$stmt->bind_param('sssii', $search, $search, $search, $first, $perpage))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $regtime))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$users[] = new self($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $regtime);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $users;
	}
	/*
	 * retrieves a list of latest activities 
	 */
	public static function getUsersActivity($all, $pagenumber = 0, $perpage = 20)
	{
		$activities = array();

		$exclude = '';
		if (count(UserConfig::$dont_display_activity_for) > 0) {
			$exclude = ' user_id NOT IN('.join(', ', UserConfig::$dont_display_activity_for).') ';
		}

		if ($all) {
			$query = 'SELECT UNIX_TIMESTAMP(time) as time, user_id, activity_id FROM '.UserConfig::$mysql_prefix.'activity '.($exclude != '' ? 'WHERE '.$exclude : '').' ORDER BY time DESC LIMIT ?, ?';
		} else {
			$ids = array();

			foreach (UserConfig::$activities as $id => $activity) {
				if ($activity[1] > 0) {
					$ids[] = $id;
				}
			}

			if (count($ids) == 0) {
				return $activities; // no activities are configured to be worthy
			}

			$query = 'SELECT UNIX_TIMESTAMP(time) as time, user_id, activity_id FROM '.UserConfig::$mysql_prefix.'activity WHERE activity_id IN ('.implode(', ', $ids).') '.($exclude != '' ? 'AND '.$exclude : '').'ORDER BY time DESC LIMIT ?, ?';
		}

		$db = UserConfig::getDB();

		$first = $perpage * $pagenumber;

		if ($stmt = $db->prepare($query))
		{
			if (!$stmt->bind_param('ii', $first, $perpage))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($time, $user_id, $activity_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$activities[] = array('time' => $time, 'user_id' => $user_id, 'activity_id' => $activity_id);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $activities;
	}

	/*
	 * retrieves a list of users by activity
	 */
	public static function getUsersByActivity($activityid, $pagenumber = 0, $perpage = 20)
	{
		$activities = array();

		$exclude = '';
		if (count(UserConfig::$dont_display_activity_for) > 0) {
			$exclude = ' AND user_id NOT IN('.join(', ', UserConfig::$dont_display_activity_for).') ';
		}

		$query = 'SELECT UNIX_TIMESTAMP(time) as time, user_id FROM '.UserConfig::$mysql_prefix.'activity WHERE activity_id = ? '.$exclude.' ORDER BY time DESC LIMIT ?, ?';

		$db = UserConfig::getDB();

		$first = $perpage * $pagenumber;

		if ($stmt = $db->prepare($query))
		{
			if (!$stmt->bind_param('iii', $activityid, $first, $perpage))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($time, $user_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$activities[] = array('time' => $time, 'user_id' => $user_id);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $activities;
	}

	public static function getUsersByEmailOrUsername($nameoremail)
	{
		$db = UserConfig::getDB();

		$nameoremail = trim($nameoremail);

		$users = array();

		if ($stmt = $db->prepare('SELECT id, status, name, username, email, requirespassreset, fb_id, UNIX_TIMESTAMP(regtime), points FROM '.UserConfig::$mysql_prefix.'users WHERE username = ? OR email = ?'))
		{
			if (!$stmt->bind_param('ss', $nameoremail, $nameoremail))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $regtime, $points))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while ($stmt->fetch() === TRUE)
			{
				$users[] = new self($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $regtime, $points);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $users;
	}

	/*
	 * retrieve activity statistics 
	 */
	public static function getActivityStatistics()
	{
		$stats = array();

		$where = '';
		if (count(UserConfig::$dont_display_activity_for) > 0) {
			$where = ' WHERE user_id NOT IN('.join(', ', UserConfig::$dont_display_activity_for).') ';
		}

		$query = 'SELECT activity_id, count(*) as cnt FROM '.UserConfig::$mysql_prefix."activity $where GROUP BY activity_id";

		$db = UserConfig::getDB();

		if ($stmt = $db->prepare($query))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($activity_id, $cnt))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$stats[$activity_id] = $cnt;
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $stats;
	}

	/*
	 * retrieves a list of latest activities 
	 */
	public function getActivity($all, $pagenumber = 0, $perpage = 20)
	{
		$activities = array();

		if ($all) {
			$query = 'SELECT UNIX_TIMESTAMP(time) as time, user_id, activity_id FROM '.UserConfig::$mysql_prefix.'activity WHERE user_id = ? ORDER BY time DESC LIMIT ?, ?';
		} else {
			$ids = array();

			foreach (UserConfig::$activities as $id => $activity) {
				if ($activity[1] > 0) {
					$ids[] = $id;
				}
			}

			if (count($ids) == 0) {
				return $activities; // no activities are configured to be worthy
			}

			$query = 'SELECT UNIX_TIMESTAMP(time) as time, user_id, activity_id FROM '.UserConfig::$mysql_prefix.'activity WHERE user_id = ? AND activity_id IN ('.implode(', ', $ids).')  ORDER BY time DESC LIMIT ?, ?';
		}

		$db = UserConfig::getDB();

		$first = $perpage * $pagenumber;

		if ($stmt = $db->prepare($query))
		{
			if (!$stmt->bind_param('iii', $this->userid, $first, $perpage))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($time, $user_id, $activity_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$activities[] = array('time' => $time, 'user_id' => $user_id, 'activity_id' => $activity_id);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $activities;
	}

	/*
	 * Generates password recovery code and saves it to the database for later matching
	 */
	public function generateTemporaryPassword()
	{
		$db = UserConfig::getDB();

		$temppass = uniqid();

		if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'users SET temppass = ?, temppasstime = now() WHERE id = ?'))
		{
			if (!$stmt->bind_param('si', $temppass, $this->userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $temppass;
	}

	/*
	 * Resets temporary password
	 */
	public function resetTemporaryPassword()
	{
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'users SET temppass = null, temppasstime = null WHERE id = ?'))
		{
			if (!$stmt->bind_param('s', $this->userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}
	}

	/*
	 * Records user registration module (should be used only once
	 */
	public function setRegistrationModule($module)
	{
		$db = UserConfig::getDB();

		$module_id = $module->getID();

		if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'users SET regmodule = ? WHERE id = ?'))
		{
			if (!$stmt->bind_param('si', $module_id, $this->userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}
	}

	/*
	 * retrieves user information by array of IDs 
	 */
	public static function getUsersByIDs($userids)
	{
		$db = UserConfig::getDB();

		$users = array();

		$ids = array();
		foreach ($userids as $userid) {
			if (is_int($userid)){
				$ids[] = $userid;
			}
		}

		$idlist = join(', ', $ids);
		
		if ($stmt = $db->prepare('SELECT id, status, name, username, email, requirespassreset, fb_id, UNIX_TIMESTAMP(regtime), points FROM '.UserConfig::$mysql_prefix.'users WHERE id IN ('.$idlist.')'))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $regtime, $points))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while ($stmt->fetch() === TRUE)
			{
				$users[] = new self($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $regtime, $points);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $users;
	}

	public function removeGoogleFriendConnectAssociation($google_id)
	{
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('DELETE FROM '.UserConfig::$mysql_prefix.'googlefriendconnect WHERE user_id = ? AND google_id = ?'))
		{
			if (!$stmt->bind_param('is', $this->userid, $google_id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}
		$this->recordActivity(USERBASE_ACTIVITY_REMOVED_GFC);
	}
	public function addGoogleFriendConnectAssociation($google_id, $userpic)
	{
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('INSERT IGNORE INTO '.UserConfig::$mysql_prefix.'googlefriendconnect (user_id, google_id, userpic) VALUES (?, ?, ?)'))
		{
			if (!$stmt->bind_param('iss', $this->userid, $google_id, $userpic))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		$this->recordActivity(USERBASE_ACTIVITY_ADDED_GFC);
	}

	public function getGoogleFriendsConnectAssociations()
	{
		$db = UserConfig::getDB();

		$associations = array();

		if ($stmt = $db->prepare('SELECT google_id, userpic FROM '.UserConfig::$mysql_prefix.'users u INNER JOIN '.UserConfig::$mysql_prefix.'googlefriendconnect g ON u.id = g.user_id WHERE u.id = ?'))
		{
			if (!$stmt->bind_param('i', $this->userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($google_id, $userpic))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while ($stmt->fetch() === TRUE)
			{
				$associations[] = array('google_id' => $google_id, 'userpic' => $userpic);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $associations;
	}

	/*
	 * retrieves user information by username
	 */
	public static function getUserByUsernamePassword($entered_username, $entered_password)
	{
		$db = UserConfig::getDB();

		$user = null;

		if ($stmt = $db->prepare('SELECT id, status, name, username, email, pass, salt, temppass, requirespassreset, fb_id FROM '.UserConfig::$mysql_prefix.'users WHERE username = ?'))
		{
			if (!$stmt->bind_param('s', $entered_username))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($id, $status, $name, $username, $email, $pass, $salt, $temppass, $requirespassreset, $fb_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			if ($stmt->fetch() === TRUE)
			{
				if (sha1($salt.$entered_password) == $pass)
				{
					$user = new self($id, $status, $name, $username, $email, $requirespassreset, $fb_id);

				}
			}

			$stmt->close();

			// if user used password recovery and remembered his old password
			// then clean temporary password and password reset flag
			// (don't reset the flag if was was set for some other reasons)
			if (!is_null($user) && !$user->isDisabled() && !is_null($temppass) && $user->requiresPasswordReset())
			{
				$user->setRequiresPasswordReset(false);
				$user->save();

				$user->resetTemporaryPassword();
			}
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		if (is_null($user))
		{
			if ($stmt = $db->prepare('SELECT id, status, name, username, email, fb_id FROM '.UserConfig::$mysql_prefix.'users WHERE username = ? AND temppass = ? AND temppasstime > DATE_SUB(NOW(), INTERVAL 1 DAY)'))
			{
				if (!$stmt->bind_param('ss', $entered_username, $entered_password))
				{
					 throw new Exception("Can't bind parameter".$stmt->error);
				}
				if (!$stmt->execute())
				{
					throw new Exception("Can't execute statement: ".$stmt->error);
				}
				if (!$stmt->bind_result($id, $status, $name, $username, $email, $fb_id))
				{
					throw new Exception("Can't bind result: ".$stmt->error);
				}

				if ($stmt->fetch() === TRUE)
				{
					$user = new self($id, $status, $name, $username, $email, null, $fb_id);
				}

				$stmt->close();

				if (!is_null($user))
				{
					$user->setRequiresPasswordReset(true);
					$user->save();
				}
			}
			else
			{
				throw new Exception("Can't prepare statement: ".$db->error);
			}
		}
		else
		{
			$user->resetTemporaryPassword();
		}

		if (!is_null($user) && $user->isDisabled()) {
			return null;
		}

		return $user;
	}

	/*
	 * retrieves user information by Google Friend Connect ID
	 */
	public static function getUserByGoogleFriendConnectID($googleid)
	{
		$db = UserConfig::getDB();

		$user = null;

		if ($stmt = $db->prepare('SELECT id, status, name, username, email, requirespassreset, fb_id, UNIX_TIMESTAMP(regtime), points FROM '.UserConfig::$mysql_prefix.'users u INNER JOIN '.UserConfig::$mysql_prefix.'googlefriendconnect g ON u.id = g.user_id WHERE g.google_id = ?'))
		{
			if (!$stmt->bind_param('s', $googleid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $regtime, $points))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			if ($stmt->fetch() === TRUE)
			{
				$user = new self($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $regtime, $points);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $user;
	}
	/*
	 * retrieves user information by Facebook ID
	 */
	public static function getUserByFacebookID($fb_id)
	{
		$db = UserConfig::getDB();

		$user = null;

		if ($stmt = $db->prepare('SELECT id, status, name, username, email, requirespassreset, UNIX_TIMESTAMP(regtime), points FROM '.UserConfig::$mysql_prefix.'users WHERE fb_id = ?'))
		{
			if (!$stmt->bind_param('i', $fb_id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($userid, $status, $name, $username, $email, $requirespassreset, $regtime, $points))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			if ($stmt->fetch() === TRUE)
			{
				$user = new self($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $regtime, $points);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $user;
	}


	/*
	 * retrieves user information from database and constructs
	 */
	public static function getUser($userid)
	{
		$db = UserConfig::getDB();

		$user = null;

		if ($stmt = $db->prepare('SELECT status, name, username, email, requirespassreset, fb_id, UNIX_TIMESTAMP(regtime), points FROM '.UserConfig::$mysql_prefix.'users WHERE id = ?'))
		{
			if (!$stmt->bind_param('i', $userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($status, $name, $username, $email, $requirespassreset, $fb_id, $regtime, $points))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			if ($stmt->fetch() === TRUE)
			{
				$user = new self($userid, $status, $name, $username, $email, $requirespassreset, $fb_id, $regtime, $points);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $user;
	}

	private static function setReturn($return)
	{
		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'path' => UserConfig::$SITEROOTURL,
			'expire' => 0,
			'httponly' => true
		));

		if (!$storage->store(UserConfig::$session_return_key, $return)) {
			throw new Exception(implode('; ', $storage->errors));
		}
	}

	public static function getReturn()
	{
		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'path' => UserConfig::$SITEROOTURL,
			'httponly' => true
		));

		$return = $storage->fetch(UserConfig::$session_return_key);

		if (is_string($return)) {
			return $return;
		} else {
			return null;
		}
	}

	public static function clearReturn()
	{
		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'path' => UserConfig::$SITEROOTURL,
			'httponly' => true
		));

		$storage->delete(UserConfig::$session_return_key);
	}

	public static function redirectToLogin()
	{
		self::setReturn($_SERVER['REQUEST_URI']);
		
		header('Location: '.UserConfig::$USERSROOTURL.'/login.php');
		exit;
	}

	private static function redirectToPasswordReset()
	{
		self::setReturn($_SERVER['REQUEST_URI']);

		header('Location: '.UserConfig::$USERSROOTURL.'/modules/usernamepass/passwordreset.php');
		exit;
	}

	// statics are over - things below are for objects.
	private $userid;
	private $status;
	private $name;
	private $username;
	private $email;
	private $requirespassreset;
	private $fbid;
	private $regtime;
	private $points;
	private $impersonator;

	function __construct($userid, $status = 1, $name, $username = null, $email = null, $requirespassreset = false, $fbid = null, $regtime = null, $points = 0)
	{
		$this->userid = $userid;
		$this->status = $status;
		$this->name = $name;
		$this->username = $username;
		$this->email = $email;
		$this->requirespassreset = $requirespassreset ? true : false;
		$this->fbid = $fbid;
		$this->regtime = $regtime;
		$this->points = $points;
	}

	public function requiresPasswordReset()
	{
		return $this->requirespassreset;
	}

	public function setRequiresPasswordReset($requires)
	{
		$this->requirespassreset = $requires;
	}

	public function getID()
	{
		return $this->userid;
	}
	public function getName()
	{
		return $this->name;
	}
	public function setName($name)
	{
		$this->name = $name;
	}
	public function getUsername()
	{
		return $this->username;
	}
	public function setUsername($username)
	{
		if (is_null($this->username))
		{
			$this->username = $username;
		} else {
			throw new Exception('This user already has username set.');
		}
	}
	public function getEmail()
	{
		return $this->email;
	}
	public function setEmail($email)
	{
		$this->email = $email;
	}
	public function getFacebookID()
	{
		return $this->fbid;
	}
	public function setFacebookID($fbid)
	{
		$this->fbid = $fbid;
	}
	public function setStatus($status) {
		$this->status = $status ? 1 : 0;
	}
	public function getRegTime()
	{
		return $this->regtime;
	}
	public function getPoints()
	{
		return $this->points;
	}
	public function isTheSameAs($user)
	{
		return $this->getID() == $user->getID();
	}
	public function isDisabled()
	{
		return ($this->status == 0 ? true : false);
	}

	public function checkPass($password)
	{
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('SELECT pass, salt FROM '.UserConfig::$mysql_prefix.'users WHERE id = ?'))
		{
			if (!$stmt->bind_param('i', $this->userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($pass, $salt))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			if ($stmt->fetch() === TRUE)
			{
				return ($pass == sha1($salt.$password));
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return false;
	}

	public function setPass($password)
	{
		$db = UserConfig::getDB();

		$salt = uniqid();
		$pass = sha1($salt.$password);

		if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'users SET pass = ?, salt = ? WHERE id = ?'))
		{
			if (!$stmt->bind_param('ssi', $pass, $salt, $this->userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return;
	}

	public function save()
	{
		$db = UserConfig::getDB();

		$passresetnum = $this->requirespassreset ? 1 : 0;
		$status = $this->status == 0 ? 0 : 1;

		if (!is_null(UserConfig::$email_module)) {
			// !WARNING! it's not safe to do anything with this user except reading it's built-in
			// properties
			// TODO implement some protection from reading or writing to DB based on this user's info,
			// just reading object properties.

			// creating a copy of the user in case we need to update their email subscription
			$old_user = User::getUser($this->getID());
		}

		$username = is_null($this->username) || $this->username == '' ? null
			: mb_convert_encoding($this->username, 'UTF-8');
		$name = is_null($this->name) || $this->name == '' ? null
			: mb_convert_encoding($this->name, 'UTF-8');
		$email = is_null($this->email) || $this->email == '' ? null
			: mb_convert_encoding($this->email, 'UTF-8');

		if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'users SET status = ?, username = ?, name = ?, email = ?, requirespassreset = ?, fb_id = ? WHERE id = ?'))
		{
			if (!$stmt->bind_param('isssiii', $status, $username, $name, $email, $passresetnum, $this->fbid, $this->userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		if (!is_null(UserConfig::$email_module)) {
			// it's up to email module to decide what to do
			UserConfig::$email_module->userChanged($old_user, $this);
		}

		return;
	}

	public function setSession($remember)
	{
		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
			'path' => UserConfig::$SITEROOTURL,
			'expire' => UserConfig::$allowRememberMe && $remember
				? time() + UserConfig::$rememberMeTime : 0,
			'httponly' => true
		));

		if (!$storage->store(UserConfig::$session_userid_key, $this->userid)) {
			throw new Exception(implode('; ', $storage->errors));
		}
	}

	public static function clearSession()
	{
		self::stopImpersonation();

		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
			'path' => UserConfig::$SITEROOTURL
		));

		$storage->delete(UserConfig::$session_userid_key);
	}

	/**
	 * This method turns on impersonation of particular user (instead of just becoming one)
	 */
	public function impersonate($user)
	{
		if (is_null($user) || $user->isTheSameAs($this)) {
			return null;
		}

		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
			'path' => UserConfig::$SITEROOTURL,
			'httponly' => true
		));

		if (!$this->isAdmin()) {
			throw new Exception('Not admin (userid: '.$this->userid.') is trying to impersonate another user (userid: '.$user->userid.')');
		}

		if (!$storage->store(UserConfig::$impersonation_userid_key, $user->userid)) {
			throw new Exception(implode('; ', $storage->errors));
		}

		$user->impersonator = $this;

		return $user;
	}

	/**
	 * Stops impersonation
	 */
	public static function stopImpersonation()
	{
		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
			'path' => UserConfig::$SITEROOTURL
		));

		$storage->delete(UserConfig::$impersonation_userid_key);
	}

	/*
	 * records user activity
	 * @activity_id:	ID of activity performed by the user
	 */
	public function recordActivity($activity_id)
	{
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'activity (user_id, activity_id) VALUES (?, ?)'))
		{
			if (!$stmt->bind_param('ii', $this->userid, $activity_id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'users SET points = points + ? WHERE id = ?'))
		{
			if (!$stmt->bind_param('ii', UserConfig::$activities[$activity_id][1], $this->userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}
	}
 
	/*
	 * Returns a list of user's accounts
	 */
	public function getAccounts()
	{
		return Account::getUserAccounts($this);
	}

	/*
	 * Returns user's current account
	 */
	public function getCurrentAccount()
	{
		return Account::getCurrentAccount($this);
	}

	/*
	 * Returns true if user has requested feature enabled
	 */
	public function hasFeature($feature) {
		// checking if we got feature ID instead of object for backwards compatibility
		if (is_int($feature)) {
			$feature = Feature::getByID($feature);
		}

		return $feature->isEnabledForUser($this);
	}

	public function setFeatures($features) {
		$all_features = Feature::getAll();

		foreach ($all_features as $id => $feature) {

			if ($feature->isEnabled() && in_array($feature, $features)) {
				$feature->enableForUser($this);
			} else {
				$feature->disableForUser($this);
			}
		}
	}

	/**
	 * Returns true if user is the admin of the instance
	 */
	public function isAdmin() {
		return in_array($this->getID(), UserConfig::$admins);
	}

	/**
	 * Returns true if user is being impersonated by another user
	 */
	public function isImpersonated() {
		return !is_null($this->impersonator);
	}

	/**
	 * Returns impersonator object (not actual, but a copy to avoid fiddling with real object)
	 */
	public function getImpersonator() {
		// do not return
		return clone($this->impersonator);
	}
}
