<?php
/**
 * @package StartupAPI
 */
class Feature {
	private $id;
	private $name;
	private $enabled;
	private $rolled_out_to_all_users;
	private $shutdown_priority;
	private $emergency_shutdown;

	// moved feature array from users_config to here
	private static $features = array();

	/**
	 * @param integer Unique numeric ID of the feature
	 * @param string Human readable name of the feature
	 * @param boolean Flag indicating that feature is currently active
	 * @param boolean Flag indicating that feature is rolled out to all users
	 * @param integer Priority number indicating that feature
	 *			should be shut down (automatically or manually) in case of system oveload
	 * @param boolean Flag indicating that this feature is temporarily shut down
	 *			due to operational emergency (e.g. system overload)
	 */
	public function __construct($id, $name,
		$enabled = true,
		$rolled_out_to_all_users = false,
		$shutdown_priority = null,
		$emergency_shutdown = false
	) {
		$this->id = $id;
		$this->name = $name;
		$this->enabled = $enabled;
		$this->rolled_out_to_all_users = $rolled_out_to_all_users;

		if (is_null($shutdown_priority)) {
			// make it higher then current maximum (first features are first to shut-down)
			$this->shutdown_priority = 1;
			foreach(self::$features as $feature) {
				$priority = $feature->getShutdownPriority();
				if ($priority > $this->shutdown_priority) {
					$this->shutdown_priority = $priority;
				}
				$this->shutdown_priority += 1;
			}
		} else {
			$this->shutdown_priority = $shutdown_priority;
		}

		$this->emergency_shutdown = $emergency_shutdown;

		self::$features[$id] = $this;
	}

	public function getID() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function isEnabled() {
		return $this->enabled ? true : false;
	}

	public function isRolledOutToAllUsers() {
		return $this->rolled_out_to_all_users;
	}

	public function getShutdownPriority() {
		return $this->shutdown_priority;
	}

	public function isShutDown() {
		return $this->emergency_shutdown;
	}

	public static function getAll() {
		return self::$features;
	}

	public static function getByID($id) {
		if (array_key_exists($id, self::$features)) {
			return self::$features[$id];
		}

		return null;
	}

	// used for backwards compatibility
	public static function init() {
		foreach (UserConfig::$features as $id => $details) {
			new Feature($id, $details[0], $details[1], $details[2]);
		}
	}

	public function isEnabledForAccount($account){
		if ($this->emergency_shutdown || !$this->enabled) {
			return false;
		}

		// if feature is forced, return true
		if (!$this->rolled_out_to_all_users) {
			return true;
		}

		// now, let's see if account has it enabled
		$db = UserConfig::getDB();

		$accountid = $account->getID();

		if ($stmt = $db->prepare('SELECT COUNT(*) FROM '.UserConfig::$mysql_prefix.'account_features WHERE account_id = ? AND feature_id = ?'))
		{
			if (!$stmt->bind_param('ii', $accountid, $this->id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($enabled))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();

			return $enabled > 0 ? true : false;
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}
	}

	/*
	 * Returns true if user has requested feature enabled
	 */
	public function isEnabledForUser($user) {
		if ($this->emergency_shutdown || !$this->enabled) {
			return false;
		}

		// if feature is forced, return true
		if ($this->rolled_out_to_all_users) {
			return true;
		}

		// if user's account has feature, user has it too
		if (UserConfig::$useAccounts && $user->getCurrentAccount()->hasFeature($this)) {
			return true;
		}

		// now, let's see if user has it enabled
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('SELECT COUNT(*) FROM '.UserConfig::$mysql_prefix.'user_features WHERE user_id = ? AND feature_id = ?'))
		{
			if (!$stmt->bind_param('ii', $userid, $this->id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($enabled))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();

			return $enabled > 0 ? true : false;
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return false;
	}

	/*
	 * Returns a number of users this feature is enabled for
	 */
	public function getUserCount() {
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('SELECT COUNT(*) FROM '.UserConfig::$mysql_prefix.'user_features WHERE feature_id = ?'))
		{
			if (!$stmt->bind_param('i', $this->id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($rolledout))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();

			return $rolledout;
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return false;
	}

	/*
	 * Returns a number of accounts this feature is enabled for
	 */
	public function getAccountCount() {
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('SELECT COUNT(*) FROM '.UserConfig::$mysql_prefix.'account_features WHERE feature_id = ?'))
		{
			if (!$stmt->bind_param('i', $this->id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($rolledout))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();

			return $rolledout;
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return false;
	}


	public function disableForUser($user) {
		// now, let's see if user has it enabled
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('DELETE FROM '.UserConfig::$mysql_prefix.'user_features WHERE user_id = ? AND feature_id = ?'))
		{
			if (!$stmt->bind_param('ii', $userid, $this->id))
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

	public function enableForUser($user) {
		// now, let's see if user has it enabled
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('REPLACE INTO '.UserConfig::$mysql_prefix.'user_features (user_id, feature_id) VALUES (?, ?)'))
		{
			if (!$stmt->bind_param('ii', $userid, $this->id))
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
}

Feature::init();
