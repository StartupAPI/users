<?php
/**
 * Represents application feature
 *
 * Usage:
 *
 * First define in your configuration file using a numeric ID
 * <code>
 * define('MY_FIRST_FEATURE_ID', 1);
 * new Feature(MY_FIRST_FEATURE_ID, 'My first feature');
 * </code>
 *
 * and then use it when implementing the feature in your code
 * <code>
 * if ($user->hasFeature(MY_FIRST_FEATURE_ID)) {
 *		echo "Hey, you can use my first feature!";
 * }
 * </code>
 *
 * @package StartupAPI
 */
class Feature {
	/**
	 * @var int Numeric feature ID
	 */
	private $id;

	/**
	 * @var string Feature display name
	 */
	private $name;

	/**
	 * @var boolean Is feature enabled or not
	 */
	private $enabled;

	/**
	 * @var boolean Is feature is rolled out to all users or not
	 */
	private $rolled_out_to_all_users;

	/**
	 * @var int Shutdown priority, can be used to automate shutdown in case of system problems
	 */
	private $shutdown_priority;

	/**
	 * @var boolean Indicates that this feature was shut down due to system problems
	 */
	private $emergency_shutdown;

	/**
	 * @var array An array of features registered in the system
	 */
	private static $features = array();

	/**
	 * Creates a new feature object and registers it in the system
	 *
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

	/**
	 * Returns feature ID
	 *
	 * @return int Feature ID
	 */
	public function getID() {
		return $this->id;
	}

	/**
	 * Returns feature display name
	 *
	 * @return string Feature display name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Checks if feature is enabled or not
	 *
	 * @return boolean Enabled / disabled
	 */
	public function isEnabled() {
		return $this->enabled ? true : false;
	}

	/**
	 * Returns whatever this feature is rolled out to all users already
	 *
	 * @return boolean
	 */
	public function isRolledOutToAllUsers() {
		return $this->rolled_out_to_all_users ? true : false;
	}

	/**
	 * Returns shutdown priority
	 *
	 * @return int Shutdown priority
	 */
	public function getShutdownPriority() {
		return $this->shutdown_priority;
	}

	/**
	 * Checks if system is shut down
	 *
	 * @return boolean Up / Down
	 */
	public function isShutDown() {
		return $this->emergency_shutdown;
	}

	/**
	 * Returns all features
	 *
	 * @return Feature[] Array of Feature objects configured in the system
	 */
	public static function getAll() {
		return self::$features;
	}

	/**
	 * Returns feature for ID provided, or null if no feature with this ID exists
	 *
	 * @param int $id Feature ID
	 *
	 * @return Feature|null
	 */
	public static function getByID($id) {
		if (array_key_exists($id, self::$features)) {
			return self::$features[$id];
		}

		return null;
	}

	//
	/**
	 * Initializes features based on old configuration
	 *
	 * Used for backwards compatibility
	 *
	 * @deprecated
	 */
	public static function init() {
		foreach (UserConfig::$features as $id => $details) {
			new Feature($id, $details[0], $details[1], $details[2]);
		}
	}

	/**
	 * Checks if feature is enabled for particular account
	 *
	 * @param Account $account Account to check
	 *
	 * @return boolean Enabled / Disabled
	 *
	 * @throws DBException
	 */
	public function isEnabledForAccount($account){
		if ($this->emergency_shutdown || !$this->enabled) {
			return false;
		}

		// if feature is forced, return true
		if ($this->rolled_out_to_all_users) {
			return true;
		}

		// now, let's see if account has it enabled
		$db = UserConfig::getDB();

		$accountid = $account->getID();

		if ($stmt = $db->prepare('SELECT COUNT(*) FROM '.UserConfig::$mysql_prefix.'account_features WHERE account_id = ? AND feature_id = ?'))
		{
			if (!$stmt->bind_param('ii', $accountid, $this->id))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($enabled))
			{
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();

			return $enabled > 0 ? true : false;
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}
	}

	/**
	 * Enables feature for particular account
	 *
	 * @param Account $account Account to enable this feature for
	 *
	 * @throws DBException
	 */
	public function enableForAccount($account) {
		$db = UserConfig::getDB();

		$account_id = $account->getID();

		if ($stmt = $db->prepare('REPLACE INTO '.UserConfig::$mysql_prefix.'account_features (account_id, feature_id) VALUES (?, ?)'))
		{
			if (!$stmt->bind_param('ii', $account_id, $this->id))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}
	}

	/**
	 * Removes feature for particular user (global roll-out will still apply)
	 *
	 * @param Account $account Account to remove this feature for
	 *
	 * @throws DBException
	 */
	public function removeForAccount($account) {
		$db = UserConfig::getDB();

		$account_id = $account->getID();

		if ($stmt = $db->prepare('DELETE FROM '.UserConfig::$mysql_prefix.'account_features WHERE account_id = ? AND feature_id = ?'))
		{
			if (!$stmt->bind_param('ii', $account_id, $this->id))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}
	}

	/**
	 * Checks if feature is enabled for particular user
	 *
	 * @param User $user User to check
	 *
	 * @return boolean Enabled / Disabled
	 *
	 * @throws DBException
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
		if ($user->getCurrentAccount()->hasFeature($this)) {
			return true;
		}

		// now, let's see if user has it enabled
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('SELECT COUNT(*) FROM '.UserConfig::$mysql_prefix.'user_features WHERE user_id = ? AND feature_id = ?'))
		{
			if (!$stmt->bind_param('ii', $userid, $this->id))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($enabled))
			{
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();

			return $enabled > 0 ? true : false;
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return false;
	}

	/**
	 * Returns a number of users this feature is enabled for
	 *
	 * @return int Number of users this feature is enabled for
	 *
	 * @throws DBException
	 */
	public function getUserCount() {
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('SELECT COUNT(*) FROM '.UserConfig::$mysql_prefix.'user_features WHERE feature_id = ?'))
		{
			if (!$stmt->bind_param('i', $this->id))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($rolledout))
			{
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();

			return $rolledout;
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return false;
	}

	/**
	 * Returns a number of accounts this feature is enabled for
	 *
	 * @return int Number of accounts this feature is enabled for
	 *
	 * @throws DBException
	 */
	public function getAccountCount() {
		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('SELECT COUNT(*) FROM '.UserConfig::$mysql_prefix.'account_features WHERE feature_id = ?'))
		{
			if (!$stmt->bind_param('i', $this->id))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($rolledout))
			{
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();

			return $rolledout;
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return false;
	}

	/**
	 * Removes feature for particular user (account preferences and global roll-out will still apply)
	 *
	 * @param User $user User to remove this feature for
	 *
	 * @throws DBException
	 */
	public function removeForUser($user) {
		// now, let's see if user has it enabled
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('DELETE FROM '.UserConfig::$mysql_prefix.'user_features WHERE user_id = ? AND feature_id = ?'))
		{
			if (!$stmt->bind_param('ii', $userid, $this->id))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}
	}

	/**
	 * Enables feature for particular user
	 *
	 * @param User $user User to enables this feature for
	 *
	 * @throws DBException
	 */
	public function enableForUser($user) {
		// now, let's see if user has it enabled
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('REPLACE INTO '.UserConfig::$mysql_prefix.'user_features (user_id, feature_id) VALUES (?, ?)'))
		{
			if (!$stmt->bind_param('ii', $userid, $this->id))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}
	}
}

Feature::init();
