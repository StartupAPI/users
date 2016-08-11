<?php

/**
 * This class represents gamification badges people can uses
 *
 * @package StartupAPI
 * @subpackage Gamification
 */
class Badge {

	/**
	 * @var int Badge ID
	 */
	private $id;

	/**
	 * @var string Badge slug used in file and page names
	 */
	private $slug;

	/**
	 * @var string Badge title
	 */
	private $title;

	/**
	 * @var string Badge description
	 */
	private $description;

	/**
	 * @var string Hint for unlocking this badge
	 */
	private $hint;

	/**
	 * @var string[] Array of badge call to action messages for each level
	 */
	private $calls_to_action;

	/**
	 * @var string Badge set slug
	 */
	private $set;

	/**
	 * @var BadgeActivityTrigger[] A list of activity triggers
	 */
	private static $activityTriggers = array();

	/**
	 * @var Badge[] Array of badges registered in the system preserving the sequence
	 */
	private static $badges = array();

	/**
	 * @var srray Helper array of badges keyed by badge ID
	 */
	private static $badge_dictionary = array();

	/**
	 * Creates a badge and registers it in the system
	 *
	 * @param int $id Badge ID
	 * @param string $set
	 * @param string $slug Badge slug
	 * @param string $title Badge title
	 * @param string $description Badge description
	 * @param string $hint Hint for unlocking this badge
	 * @param string[] $calls_to_action Array of calls to action (one for each level)
	 */
	public function __construct($id, $set, $slug, $title, $description, $hint = null, $calls_to_action = null) {
		$this->id = $id;
		$this->set = $set;
		$this->slug = $slug;
		$this->title = $title;
		$this->description = $description;
		$this->hint = $hint;
		$this->calls_to_action = $calls_to_action;

		self::$badges[] = $this;
		self::$badge_dictionary[$id] = $this;
	}

	/**
	 * Returns all available badges
	 *
	 * @return Badge[] Array of badges available in the system
	 */
	public static function getAvailableBadges() {
		return self::$badges;
	}

	/**
	 * Returns badge ID
	 *
	 * @return int Badge ID
	 */
	public function getID() {
		return $this->id;
	}

	/**
	 * Returns badge slug to be used in URLs
	 *
	 * @return string Badge slug
	 */
	public function getSlug() {
		return $this->slug;
	}

	/**
	 * Returns badge title
	 *
	 * @return string Badge title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Returns badge description
	 *
	 * @return string Badge description
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Returns a hint for unlocking the badge
	 *
	 * @return string Hint for unlocking the badge
	 */
	public function getHint() {
		return $this->hint;
	}

	/**
	 * Returns call to action for particular level
	 *
	 * @param int $level Badge level to get a hint for
	 *
	 * @return string|null Call to action for getting to next level or null if none defined
	 */
	public function getCallToAction($level = 1) {
		if (!is_array($this->calls_to_action) || !array_key_exists($level - 1, $this->calls_to_action)) {
			return null;
		}

		return $this->calls_to_action[$level - 1];
	}

	/**
	 * Returns calls to actions for all levels
	 *
	 * @return string[] Array of calls to action for each level
	 */
	public function getCallsToAction() {
		return $this->calls_to_action;
	}

	/**
	 * Returns URL of Badge image
	 *
	 * You can specify a size you're looking for, sizes are standard:
	 * - 32: 32px x 32px
	 * - 100: 100px x 100px
	 * - 200: 200px x 200px (default)
	 * - 300: 300px x 300px
	 * - 400: 400px x 400px
	 *
	 * Set second parameter to true to return absolute URL (to use in emails and such)
	 *
	 * @param int $size Image size
	 * @param int $level Badge level
	 * @param boolean $full Set to true for an absolute URL
	 *
	 * @return string Badge image URL
	 */
	public function getImageURL($size = 200, $level = 1, $full = false) {
		$multiplier = $level > 1 ? '_' . $level . 'x' : '';

		return ($full ? UserConfig::$USERSROOTFULLURL : UserConfig::$USERSROOTURL) .
				'/themes/' . UserConfig::$theme .
				'/badges/' . $this->set .
				'/' . $this->slug . $multiplier .
				'_' . $size .
				'.png';
	}

	/**
	 * Returns a badge placeholder URL
	 *
	 * You can specify a size you're looking for, sizes are standard:
	 * - 32: 32px x 32px
	 * - 100: 100px x 100px
	 * - 200: 200px x 200px (default)
	 * - 300: 300px x 300px
	 * - 400: 400px x 400px
	 *
	 * Set second parameter to true to return absolute URL (to use in emails and such)
	 *
	 * @param int $size Image size
	 * @param boolean $full Set to true for an absolute URL
	 *
	 * @return string Badge placeholder image URL
	 */
	public static function getPlaceholderImageURL($size = 200, $full = false) {
		return ($full ? UserConfig::$USERSROOTFULLURL : UserConfig::$USERSROOTURL) .
				'/themes/' . UserConfig::$theme .
				'/badges/placeholder_' . $size . '.png';
	}

	/**
	 * Register badge to trigger on activity
	 *
	 * Registers a badge to trigger if activity was triggered a particular number
	 * of times in activity period.
	 *
	 * If activity period is null, count activities for all time.
	 *
	 * @param int[] $activity_ids Array of numeric activity IDs that trigger the badge
	 * @param int $activity_count Number of activities to trigger the badge
	 * @param int $badge_level Level of the badge to trigger
	 * @param int $activity_period Number of days in activity window (null for all time)
	 */
	public function registerActivityTrigger($activity_ids, $activity_count, $badge_level = 1, $activity_period = null) {
		// in case only one activity ID is passed in, upgrade it to array
		if (is_int($activity_ids)) {
			$activity_ids = array($activity_ids);
		}

		foreach ($activity_ids as $activity_id) {
			self::$activityTriggers[$activity_id][] = new BadgeActivityTrigger(
							$this, $activity_ids, $activity_count, $badge_level, $activity_period
			);
		}
	}

	/**
	 * Returns a badge for ID provided
	 *
	 * @param int $id Badge ID
	 * @return Badge Badge object or null if no badge with this ID exists in the system
	 */
	public static function getByID($id) {
		if (array_key_exists($id, self::$badge_dictionary)) {
			return self::$badge_dictionary[$id];
		} else {
			return null;
		}
	}

	/**
	 * Returns a list of badges for the user
	 *
	 * @param User $user User object
	 *
	 * @return array Array of user badges with badge IDs as keys and arrays of Badge object and maximum level as value
	 *
	 * @throws DBException
	 */
	public static function getUserBadges(User $user) {
		$db = UserConfig::getDB();

		$user_badges = array();

		$user_id = $user->getID();

		if ($stmt = $db->prepare('SELECT badge_id, MAX(badge_level) as level, time FROM u_user_badges WHERE user_id = ? GROUP BY badge_id')) {
			if (!$stmt->bind_param('i', $user_id)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($id, $level, $time)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$user_badges[$id] = array(Badge::getByID($id), $level, $time);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $user_badges;
	}

	/**
	 * Returns user counts for each level of the badge
	 *
	 * @return array Array of user counts for each level (0 if not present)
	 *
	 * @throws DBException
	 */
	public function getUserCounts() {
		$db = UserConfig::getDB();

		$counts = array();

		if ($stmt = $db->prepare('SELECT badge_level, count(user_id) as total FROM u_user_badges WHERE badge_id = ? GROUP BY badge_level')) {
			if (!$stmt->bind_param('i', $this->id)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($level, $total)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$counts[$level] = $total;
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $counts;
	}

	/**
	 * Returns a list of users who gt the badge, ordered by time, most recent first
	 *
	 * @param int $level Badge level
	 * @param int $pagenumber Page number
	 * @param int $perpage Page size
	 *
	 * @return User[] Array of users objects ordered by time
	 *
	 * @throws DBException
	 */
	public function getBadgeUsers($level = null, $pagenumber = 0, $perpage = 20) {
		$db = UserConfig::getDB();

		$user_ids = array();

		if (is_null($level)) {
			$query = 'SELECT user_id FROM u_user_badges
				WHERE badge_id = ?
				ORDER BY time DESC
				LIMIT ?, ?';
		} else {
			$query = 'SELECT user_id FROM u_user_badges
				WHERE badge_id = ? AND badge_level = ?
				ORDER BY time DESC
				LIMIT ?, ?';
		}

		if ($stmt = $db->prepare($query)) {
			$start = $pagenumber * $perpage;

			if (is_null($level)) {
				$result = $stmt->bind_param('iii', $this->id, $start, $perpage);
			} else {
				$result = $stmt->bind_param('iiii', $this->id, $level, $start, $perpage);
			}
			if (!$result) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($user_id)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$user_ids[] = $user_id;
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		$badge_users = User::getUsersByIDs($user_ids);

		return $badge_users;
	}

	/**
	 * Register a badge for the user
	 *
	 * @param User $user User who got a badge
	 * @param int $level Badge level
	 *
	 * @throws DBException
	 */
	public function registerForUser(User $user, $level) {
		$db = UserConfig::getDB();

		$user_id = $user->getID();

		if ($stmt = $db->prepare('INSERT IGNORE INTO u_user_badges
									(user_id, badge_id, badge_level, time) VALUES (?, ?, ?, NOW())')) {
			if (!$stmt->bind_param('iii', $user_id, $this->id, $level)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt, "Can't register a badge for a user");
			}
			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db, "Can't register a badge for a user");
		}
	}

	/**
	 * Gives user a badge based on activity
	 *
	 * @param User $user User object
	 * @param int $activity_id Activity ID
	 */
	public static function triggerActivityBadge(User $user, $activity_id) {
		if (!array_key_exists($activity_id, self::$activityTriggers)) {
			return;
		}

		$activityTriggers = self::$activityTriggers[$activity_id];

		foreach ($activityTriggers as $activityTrigger) {
			// TODO cache the counts between different badges or badge levels
			// that trigger for the same set of actions
			$count = $user->getActivitiesCount($activityTrigger->activity_ids, $activityTrigger->activity_period);

			if ($count == $activityTrigger->activity_count) {
				$activityTrigger->badge->registerForUser($user, $activityTrigger->badge_level);
			}
		}
	}

}

/**
 * Basic data structure to hold information about activity triggers
 *
 * @package StartupAPI
 * @subpackage Gamification
 *
 * @internal Used only for data management in Badge class
 */
class BadgeActivityTrigger {

	/**
	 * @var Badge Badge to give if triggered
	 */
	public $badge;

	/**
	 * @var int[] Array of activity IDs that would activate the trigger
	 */
	public $activity_ids = array();

	/**
	 * @var int Number of activities to activate the trigger
	 */
	public $activity_count = 0;

	/**
	 * @var int Badge level to grant if triggered
	 */
	public $badge_level = 1;

	/**
	 * @var int Amount of days in activity window to cause the trigger
	 */
	public $activity_period = null;

	/**
	 * Creates a badge activity trigger
	 *
	 * @param Badge $badge Badge to give if triggered
	 * @param int[] $activity_ids Array of activity IDs that would activate the trigger
	 * @param int $activity_count Number of activities to activate the trigger
	 * @param int $badge_level Badge level to grant if triggered
	 * @param int $activity_period Amount of days in activity window to cause the trigger
	 */
	public function __construct($badge, $activity_ids, $activity_count, $badge_level, $activity_period = null) {
		$this->badge = $badge;
		$this->activity_ids = $activity_ids;
		$this->activity_count = $activity_count;
		$this->badge_level = $badge_level;
		$this->activity_period = $activity_period;
	}

}
