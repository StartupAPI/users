<?php
/**
 * Represents a group of users in the system
 *
 * @package StartupAPI
 * @subpackage Analytics\CohortAnalysis
 */
class Cohort {
	/**
	 * @var string Cohort identifier to be used in query string parameters and slugs
	 */
	private $id;
	/**
	 * @var string Cohort display name
	 */
	private $title;
	/**
	 * @var int Total number of members in the cohort
	 */
	private $total;

	/**
	 * Creates new cohort
	 *
	 * @param string $id Cohort ID string
	 * @param string $title Cohort display name
	 * @param int $total Total number of members
	 */
	public function __construct($id, $title, $total) {
		$this->id = $id;
		$this->title = $title;
		$this->total = $total;
	}

	/**
	 * Returns cohort ID string
	 *
	 * @return string
	 */
	public function getID() {
		return $this->id;
	}

	/**
	 * Returns cohort display name
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Returns total number of members in the cohort
	 *
	 * @return int
	 */
	public function getTotal() {
		return $this->total;
	}
}

/**
 * Defines a method to break down all users into cohorts
 *
 * Whatever you want to split them by registration date, by gender or by salary
 * range, just subclass this class and add it to UserConfig::$cohort_providers array
 *
 * <code>
 * UserConfig::$cohort_providers[] = new MyOwnCohortProvider();
 * </code>
 *
 * @package StartupAPI
 * @subpackage Analytics\CohortAnalysis
 *
 * @see UserConfig::$cohort_providers
 */
abstract class CohortProvider {
	/**
	 * @var string Cohort provider ID string
	 */
	private $id;
	/**
	 * @var string Cohort provider display name
	 */
	private $title;

	/**
	 * Whatever or not cohorts created by this provider create a sequence
	 * and have previous cohort to compare to.
	 *
	 * Most of cohort lists don't create cohorts that are sequential, no reason to compare them
	 *
	 * @var boolean
	 */
	protected $compare_to_previous_cohort = false;

	/**
	 * Creates a cohort provider object
	 *
	 * Add them to UserConfig::$cohort_providers[] array to be used in admin UI
	 *
	 * @param string $id ID of the provider
	 * @param string $title Display name of the provider
	 *
	 * @see UserConfig::$cohort_providers
	 */
	public function __construct($id, $title) {
		$this->id = $id;
		$this->title = $title;
	}

	/**
	 * Returns provider ID
	 *
	 * @return string Provider ID
	 */
	public function getID() {
		return $this->id;
	}

	/**
	 * Returns provider display name
	 *
	 * @return string Provider display name
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Returns the cohort comparison flag
	 *
	 * @return boolean Can we compare to previous cohort or not
	 */
	public function canCompareToPreviousCohort() {
		return $this->compare_to_previous_cohort;
	}

	/**
	 * Returns a list of cohorts tracked by specific CohortProvider
	 *
	 * @return array $cohorts an array of Cohort objects
	 */
	public abstract function getCohorts();

	/**
	 * Returns the name of dimension this provider separates users by (e.g. "registration date" or "gender")
	 *
	 * @return string Name of the dimension
	 */
	public abstract function getDimensionTitle();

	/**
	 * Returns a number of users who performed each activity for each period (specified the activity period length)
	 *
	 * If activity ID is provided, returns levels only for specific activity, otherwise for all activities.
	 *
	 * @param int $activityid Activity ID (null for all activities)
	 * @param int $actnum Number of days in activity period
	 *
	 * @return array Two dimensional array of buckets by cohort_id and activity period as keys and number of users in the bucket as values
	 *
	 * @throws DBException
	 */
	public function getActivityRate($activityid, $actnum)
	{
		$db = UserConfig::getDB();

		$siteadminsstring = null;
		if (count(UserConfig::$admins) > 0) {
			$siteadminsstring = implode(", ", UserConfig::$admins);
		}

		$aggregates = array();

		$query = 'SELECT u.cohort_id AS cohort_id,
			FLOOR(DATEDIFF(a.time, u.regtime) / ?) AS actperiod,
			COUNT(DISTINCT u.id) AS total
			FROM `'.UserConfig::$mysql_prefix.'activity` AS a
				INNER JOIN ('.$this->getCohortSQL().') AS u
					ON a.user_id = u.id';
		if (!is_null($activityid)) {
			$query .= '	WHERE `activity_id` = ?';
		}

		if (!is_null($siteadminsstring)) {
			$query .= "\nAND u.id NOT IN ($siteadminsstring)";
		}

		$query .= '
			GROUP BY cohort_id, actperiod
			ORDER BY actperiod ASC';

		if ($stmt = $db->prepare($query))
		{
			if (!is_null($activityid)) {
				if (!$stmt->bind_param('ii', $actnum, $activityid))
				{
					throw new DBBindParamException($db, $stmt);
				}
			} else {
				if (!$stmt->bind_param('i', $actnum))
				{
					throw new DBBindParamException($db, $stmt);
				}
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($cohort_id, $actperiod, $activeusers))
			{
				throw new DBBindResultException($db, $stmt);
			}

			while($stmt->fetch() === TRUE)
			{
				$aggregates[$cohort_id][$actperiod] = $activeusers;
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return $aggregates;
	}

	/**
	 * Returns SQL statement used to separate users into cohorts
	 *
	 * Subclasses must override this method and provide an SQL statement returning 3 columns:
	 * - id (ser ID, from users table)
	 * - re_time (user registration datetime, from users table)
	 * - cohort_id (cohort ID specific to your provider
	 *
	 * Where cohort_id is the main information provided by this method
	 * and used to put users into different buckets.
	 */
	abstract function getCohortSQL();

}

/**
 * This class allows dropping users into cohorts by registration date
 *
 * @package StartupAPI
 * @subpackage Analytics\CohortAnalysis
 *
 * @todo Rework into three separate classes subclassing a parent to avoid a contraption with constants
 */
class GenerationCohorts extends CohortProvider {
	/**
	 * @var int DescriptionDefines a period constant: year
	 */
	const YEAR = 0;
	/**
	 * @var int Defines a period constant: month
	 */
	const MONTH = 1;
	/**
	 * @var int Defines a period constant: week
	 */
	const WEEK = 2;

	/**
	 * A period of time between generations
	 *
	 * @var int One of GenerationCohorts::YEAR, GenerationCohorts::MONTH or GenerationCohorts::WEEK
	 */
	private $period;

	/**
	 * Creates a cohort provider to separate users by generation
	 *
	 * Most commonly used for analysis, usually for month-to-month comparison
	 *
	 * @param int $period One of GenerationCohorts::YEAR, GenerationCohorts::MONTH (default) or GenerationCohorts::WEEK
	 *
	 * @throws StartupAPIException
	 */
	public function __construct($period = self::MONTH) {
		$title = 'User genrations by ';
		$id = 'gen';

		switch ($period) {
			case self::MONTH:
				$title .= "month";
				$id .= "month";
				break;
			case self::YEAR:
				$title .= "year";
				$id .= "year";
				break;
			case self::WEEK:
				$title .= "week";
				$id .= "week";
				break;
			default:
				throw new StartupAPIException('Wrong generation period');
		}

		parent::__construct($id, $title);

		// cohorts are chronological so it makes sense to compare to previous
		$this->compare_to_previous_cohort = true;

		$this->period = $period;
	}

	/**
	 * Always returns "Reg. date"
	 *
	 * @return string
	 */
	public function getDimensionTitle() {
		return 'Reg. date';
	}

	/**
	 * Returns a list of generation cohorts for all generations of users registered so far
	 *
	 * @return array $cohorts An array of Cohort objects for each generation
	 *
	 * @throws StartupAPIException
	 * @throws DBException
	 */
	public function getCohorts() {
		$db = UserConfig::getDB();

		// an array of cohorts to return
		$cohorts = array();

		/**
		 * The query must return a unique cohort_id, title and total members
		 */
		switch ($this->period) {
			case self::MONTH:
				$query = "SELECT EXTRACT(YEAR_MONTH FROM regtime) AS cohort_id,
					DATE_FORMAT(regtime, '%b %Y') AS title,
					COUNT(*) AS totals
					FROM ".UserConfig::$mysql_prefix.'users';
				break;
			case self::YEAR:
				$query = "SELECT YEAR(regtime) AS cohort_id,
					YEAR(regtime) AS title,
					COUNT(*) AS totals
					FROM ".UserConfig::$mysql_prefix.'users';
				break;
			case self::WEEK:
				$query = "SELECT YEARWEEK(regtime) AS cohort_id,
					CONCAT(
						DATE_FORMAT(
							DATE(DATE_SUB(regtime, INTERVAL WEEKDAY(regtime) DAY)),
							'%b %e, %Y'
						), ' - ',
						DATE_FORMAT(
							DATE(DATE_ADD(regtime, INTERVAL 6-WEEKDAY(regtime) DAY)),
							'%b %e, %Y'
						)
					) AS title,
					COUNT(*) AS totals
					FROM ".UserConfig::$mysql_prefix.'users';
				break;
			default:
				throw new StartupAPIException('Wrong generation period');
		}

		// Excluding site administrators
		if (count(UserConfig::$admins) > 0) {
			$query .= "\nWHERE id NOT IN (" . implode(", ", UserConfig::$admins) . ")";
		}

		$query .= ' GROUP BY cohort_id ORDER BY regtime DESC';

		if ($stmt = $db->prepare($query))
		{
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($cohort_id, $title, $total))
			{
				throw new DBBindResultException($db, $stmt);
			}

			while($stmt->fetch() === TRUE)
			{
				$cohorts[] = new Cohort($cohort_id, $title, $total);
			}
			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return $cohorts;
	}

	/**
	 * SQL statement for separation of users into generations
	 *
	 * @return string SQL statement for generating a resultset with id, regtime and cohort_id
	 *
	 * @throws StartupAPIException
	 */
	public function getCohortSQL() {
		switch ($this->period) {
			case self::MONTH:
				$query = 'SELECT id, regtime, EXTRACT(YEAR_MONTH FROM regtime) AS cohort_id
					FROM '.UserConfig::$mysql_prefix.'users';
				break;
			case self::YEAR:
				$query = 'SELECT id, regtime, YEAR(regtime) AS cohort_id
					FROM '.UserConfig::$mysql_prefix.'users';
				break;
			case self::WEEK:
				$query = 'SELECT id, regtime, YEARWEEK(regtime) AS cohort_id
					FROM '.UserConfig::$mysql_prefix.'users';
				break;
			default:
				throw new StartupAPIException('Wrong generation period');
		}

		return $query;
	}
}

/**
 * This class allows dropping users into cohorts by registration method / module
 *
 * @package StartupAPI
 * @subpackage Analytics\CohortAnalysis
 */
class RegMethodCohorts extends CohortProvider {
	/**
	 * Creates new cohort provider based on user's method of registration (Authentication Module)
	 */
	public function __construct() {
		parent::__construct('byregmethod', 'Users registration method');
	}

	/**
	 * Always returns "Reg. module"
	 *
	 * @return string "Reg. module"
	 */
	public function getDimensionTitle() {
		return 'Reg. module';
	}

	/**
	 * Returns a list of registration module cohorts for all modules users actually regusterd with
	 *
	 * @return array $cohorts An array of Cohort objects
	 *
	 * @throws DBException
	 */
	public function getCohorts() {
		$db = UserConfig::getDB();

		$cohort_titles = array();

		foreach (UserConfig::$authentication_modules as $module) {
			$cohort_titles[$module->getID()] = $module->getTitle();
		}

		/**
		 * The query must return a unique cohort_id, title and total members
		 */
		$query = "SELECT regmodule AS cohort_id, COUNT(*) AS totals
			FROM ".UserConfig::$mysql_prefix.'users';

		$siteadminsstring = null;
		if (count(UserConfig::$admins) > 0) {
			$siteadminsstring = implode(", ", UserConfig::$admins);
		}

		if (!is_null($siteadminsstring)) {
			$query .= "\nWHERE id NOT IN ($siteadminsstring)";
		}

		$cohorts = array();

		$query .= ' GROUP BY cohort_id ORDER BY regtime DESC';

		if ($stmt = $db->prepare($query))
		{
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($cohort_id, $total))
			{
				throw new DBBindResultException($db, $stmt);
			}

			while($stmt->fetch() === TRUE)
			{
				$cohorts[] = new Cohort($cohort_id, $cohort_titles[$cohort_id], $total);
			}
			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return $cohorts;
	}

	/**
	 * SQL statement for separation of users into buckets by registration module
	 *
	 * @return string SQL statement for generating a resultset with id, regtime and cohort_id
	 */
	public function getCohortSQL() {
		return 'SELECT id, regtime, regmodule AS cohort_id FROM '.UserConfig::$mysql_prefix.'users';
	}
}
