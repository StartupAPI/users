<?php
/*
 * User class
*/
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/Account.php');
require_once(dirname(__FILE__).'/CookieStorage.php');

class User
{
	/*
	 * Checks if user is logged in and returns use object or redirects to login page
	 */
	public static function require_login()
	{
		$user = self::get();

		if (!is_null($user))
		{
			if ($user->requiresPasswordReset())
			{
				User::redirectToPasswordReset();
			}
			else
			{
				return $user;
			}
		}
		else
		{
			User::redirectToLogin();
		}
	}

	/*
	 * Checks if user is logged in and returns use object or null if user is not logged in
	 */
	public static function get()
	{
		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
			'path' => UserConfig::$SITEROOTURL
		));

		$userid = $storage->fetch(UserConfig::$session_userid_key);

		$last = $storage->fetch(UserConfig::$last_login_key);
		if (!$storage->store(UserConfig::$last_login_key, time())) {
			throw Exception($storage->errors);
		}

		if (is_string($userid)) {
			$user = self::getUser($userid);

			// only check if user has returned after some session time, e.g. 30 minutes
			if ($last > 0 && $last < time() - UserConfig::$last_login_session_length * 60) {
				if ($last > time() - 86400) {
					$user->recordActivity(USERBASE_ACTIVITY_RETURN_DAILY);
				} else if ($last > time() - 7 * 86400) {
					$user->recordActivity(USERBASE_ACTIVITY_RETURN_WEEKLY);
				} else if ($last > time() - 30 * 86400) {
					$user->recordActivity(USERBASE_ACTIVITY_RETURN_MONTHLY);
				} 
			}

			return $user;
		} else {
			return null;
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

			$personal = Account::createAccount('FREE ('.$this->getName().')',
							Plan::getFreePlan(), $this, Account::ROLE_ADMIN);

			$personal->setAsCurrent($this);
		}

		if (!is_null(UserConfig::$onCreate))
		{
			eval(userConfig::$onCreate.'($this);');
		}
	}

	/*
	 * create new user based on Google Friend Connect info
	 */
	public static function createNewGoogleFriendConnectUser($name, $googleid, $userpic)
	{
		$db = UserConfig::getDB();

		$user = null;

		if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'users (name) VALUES (?)'))
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

			$user = new User($id, $name);

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

		$user->init();

		return $user;
	}
	/*
	 * create new user based on facebook info
	 */
	public static function createNewFacebookUser($name, $fb_id)
	{
		$db = UserConfig::getDB();

		$user = null;

		if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'users (name, fb_id) VALUES (?, ?)'))
		{
			if (!$stmt->bind_param('si', $name, $fb_id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			$id = $stmt->insert_id;

			$user = new User($id, $name, null, null, $fb_id);

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		$user->init();

		return $user;
	}
	/*
	 * create new user
	 */
	public static function createNew($name, $username, $email, $password)
	{
		$db = UserConfig::getDB();

		$user = null;

		$salt = uniqid();
		$pass = sha1($salt.$password);

		if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'users (name, username, email, pass, salt) VALUES (?, ?, ?, ?, ?)'))
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

			$user = new User($id, $name, $username, $email);

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

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
	 * retrieves daily active users
	 */
	public static function getDailyActiveUsers()
	{
		$db = UserConfig::getDB();

		$daily_activity = array();

		if ($stmt = $db->prepare('SELECT CAST(time AS DATE) AS activity_date, user_id FROM '.UserConfig::$mysql_prefix.'activity GROUP BY activity_date, user_id'))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($date, $user_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$daily_activity[] = array('date' => $date, 'user' => $user_id);
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
		$dailyregs = array();

		foreach (UserConfig::$modules as $module) {
			foreach ($module->getDailyRegistrations() as $reg) {
				$dailyregs[$reg['regdate']][$module->getID()] = $reg['regs'];
			}
		}

		return $dailyregs;
	}
	/*
	 * retrieves paged list of users
	 */
	public static function getUsers($pagenumber = 0, $perpage = 20)
	{
		$db = UserConfig::getDB();

		$users = array();

		$first = $perpage * $pagenumber;

		if ($stmt = $db->prepare('SELECT id, name, username, email, requirespassreset, fb_id, UNIX_TIMESTAMP(regtime) FROM '.UserConfig::$mysql_prefix.'users ORDER BY regtime DESC LIMIT ?, ?'))
		{
			if (!$stmt->bind_param('ii', $first, $perpage))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($userid, $name, $username, $email, $requirespassreset, $fb_id, $regtime))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$users[] = new self($userid, $name, $username, $email, $requirespassreset, $fb_id, $regtime);
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
		if ($stmt = $db->prepare('SELECT id, name, username, email, requirespassreset, fb_id, UNIX_TIMESTAMP(regtime) FROM '.UserConfig::$mysql_prefix.'users WHERE INSTR(name, ?) > 0 OR INSTR(username, ?) > 0 OR INSTR(email, ?) > 0 ORDER BY regtime DESC LIMIT ?, ?'))
		{
			if (!$stmt->bind_param('sssii', $search, $search, $search, $first, $perpage))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($userid, $name, $username, $email, $requirespassreset, $fb_id, $regtime))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$users[] = new self($userid, $name, $username, $email, $requirespassreset, $fb_id, $regtime);
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
			$exclude = ' user_id NOT IN('.join(', ', UserConfig::$dont_display_activity_for).') ';
		}

		$query = 'SELECT UNIX_TIMESTAMP(time) as time, user_id FROM '.UserConfig::$mysql_prefix.'activity '.($exclude != '' ? 'WHERE activity_id = ? AND '.$exclude : '').' ORDER BY time DESC LIMIT ?, ?';

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

		if ($stmt = $db->prepare('SELECT id, name, username, email, requirespassreset, fb_id FROM '.UserConfig::$mysql_prefix.'users WHERE username = ? OR email = ?'))
		{
			if (!$stmt->bind_param('ss', $nameoremail, $nameoremail))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($userid, $name, $username, $email, $requirespassreset, $fb_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while ($stmt->fetch() === TRUE)
			{
				$users[] = new User($userid, $name, $username, $email, $requirespassreset, $fb_id);
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

		$where = ' WHERE user_id NOT IN('.join(', ', UserConfig::$dont_display_activity_for).') ';

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
		
		if ($stmt = $db->prepare('SELECT id, name, username, email, requirespassreset, fb_id FROM '.UserConfig::$mysql_prefix.'users WHERE id IN ('.$idlist.')'))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($userid, $name, $username, $email, $requirespassreset, $fb_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while ($stmt->fetch() === TRUE)
			{
				$users[] = new User($userid, $name, $username, $email, $requirespassreset, $fb_id);
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
	 * retrieves user information by Google Facebook Connect ID
	 */
	public static function getUserByGoogleFriendConnectID($googleid)
	{
		$db = UserConfig::getDB();

		$user = null;

		if ($stmt = $db->prepare('SELECT id, name, username, email, requirespassreset, fb_id FROM '.UserConfig::$mysql_prefix.'users u INNER JOIN '.UserConfig::$mysql_prefix.'googlefriendconnect g ON u.id = g.user_id WHERE g.google_id = ?'))
		{
			if (!$stmt->bind_param('s', $googleid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($userid, $name, $username, $email, $requirespassreset, $fb_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			if ($stmt->fetch() === TRUE)
			{
				$user = new User($userid, $name, $username, $email, $requirespassreset, $fb_id);
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

		if ($stmt = $db->prepare('SELECT id, name, username, email, requirespassreset FROM '.UserConfig::$mysql_prefix.'users WHERE fb_id = ?'))
		{
			if (!$stmt->bind_param('i', $fb_id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($userid, $name, $username, $email, $requirespassreset))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			if ($stmt->fetch() === TRUE)
			{
				$user = new User($userid, $name, $username, $email, $requirespassreset, $fb_id);
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

		if ($stmt = $db->prepare('SELECT name, username, email, requirespassreset, fb_id FROM '.UserConfig::$mysql_prefix.'users WHERE id = ?'))
		{
			if (!$stmt->bind_param('i', $userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($name, $username, $email, $requirespassreset, $fb_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			if ($stmt->fetch() === TRUE)
			{
				$user = new User($userid, $name, $username, $email, $requirespassreset, $fb_id);
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
			'expire' => 0
		));

		if (!$storage->store(UserConfig::$session_return_key, $return)) {
			throw Exception($storage->errors);
		}
	}

	public static function getReturn()
	{
		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'path' => UserConfig::$SITEROOTURL
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
			'path' => UserConfig::$SITEROOTURL
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
	private $name;
	private $username;
	private $email;
	private $requirespassreset;
	private $fbid;
	private $regtime;

	function __construct($userid, $name, $username = null, $email = null, $requirespassreset = false, $fbid = null, $regtime = null)
	{
		$this->userid = $userid;
		$this->name = $name;
		$this->username = $username;
		$this->email = $email;
		$this->requirespassreset = $requirespassreset ? true : false;
		$this->fbid = $fbid;
		$this->regtime = $regtime;
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
	public function getRegTime()
	{
		return $this->regtime;
	}
	public function isTheSameAs($user)
	{
		return $this->getID() == $user->getID();
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

		if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'users SET username = ?, name = ?, email = ?, requirespassreset = ?, fb_id = ? WHERE id = ?'))
		{
			if (!$stmt->bind_param('sssiii', $this->username, $this->name, $this->email, $passresetnum, $this->fbid, $this->userid))
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

	public function setSession($remember)
	{
		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
			'path' => UserConfig::$SITEROOTURL,
			'expire' => UserConfig::$allowRememberMe && $remember
				? time() + UserConfig::$rememberMeTime : 0 
		));

		if (!$storage->store(UserConfig::$session_userid_key, $this->userid)) {
			throw Exception($storage->errors);
		}
	}

	public static function clearSession()
	{
		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
			'path' => UserConfig::$SITEROOTURL
		));

		$storage->delete(UserConfig::$session_userid_key);
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
	}

	/*
	 * Returns a list of user's accounts
	 */
	public function getAccounts()
	{
		return Account::getUserAccounts($this);
	}
}
