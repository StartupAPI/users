<?php
/**
 * @package StartupAPI
 * @subpackage Analytics
 */
class Cohort {
	private $id;
	private $title;
	private $total;

	public function __construct($id, $title, $total) {
		$this->id = $id;
		$this->title = $title;
		$this->total = $total;
	}

	public function getID() {
		return $this->id;
	}

	public function getTitle() {
		return $this->title;
	}

	public function getTotal() {
		return $this->total;
	}
}

/**
 * @package StartupAPI
 * @subpackage Analytics
 */
abstract class CohortProvider {
	private $title;
	private $id;

	// most of cohort lists don't create cohorts that are sequential, no reason to compare them
	protected $compare_to_previous_cohort = false;

	/**
	 * @param string $id ID of the provider
	 * @param string $title Display name of the provider
	 */
	public function __construct($id, $title) {
		$this->id = $id;
		$this->title = $title;
	}

	public function getID() {
		return $this->id;
	}

	public function getTitle() {
		return $this->title;
	}

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
	 * Returns the name of dimension specific provider separates users by
	 *
	 * @return string name of dimension
	 */
	public abstract function getDimensionTitle();

	/*
	 * @param int $activityid Activity ID (null for any activity)
	 * @param int $actnum Number of days in activity period
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

}

/**
 * @package StartupAPI
 * @subpackage Analytics
 *
 * This class allows dropping users into cohorts by registration date
 */
class GenerationCohorts extends CohortProvider {
	const YEAR = 0;
	const MONTH = 1;
	const WEEK = 2;

	/**
	 * A period of time between generations
	 * Must be one of GenerationCohorts::MONTH or GenerationCohorts::WEEK
	 */
	private $period;

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

	public function getDimensionTitle() {
		return 'Reg. date';
	}

	/**
	 * Returns a list of generation cohorts
	 *
	 * @return array $cohorts an array of Cohort objects
	 */
	public function getCohorts() {
		$db = UserConfig::getDB();

		$siteadminsstring = null;
		if (count(UserConfig::$admins) > 0) {
			$siteadminsstring = implode(", ", UserConfig::$admins);
		}

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

		if (!is_null($siteadminsstring)) {
			$query .= "\nWHERE id NOT IN ($siteadminsstring)";
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
	 * @return string SQL statement for generating a resultset with id, regtime and cohort_id
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
 * @package StartupAPI
 * @subpackage Analytics
 *
 * This class allows dropping users into cohorts by registration method / module
 */
class RegMethodCohorts extends CohortProvider {
	public function __construct() {
		parent::__construct('byregmethod', 'Users registration method');
	}

	public function getDimensionTitle() {
		return 'Reg. module';
	}

	/**
	 * Returns a list of generation cohorts
	 *
	 * @return array $cohorts an array of Cohort objects
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
	 * @return string SQL statement for generating a resultset with id, regtime and cohort_id
	 */
	public function getCohortSQL() {
		return 'SELECT id, regtime, regmodule AS cohort_id FROM '.UserConfig::$mysql_prefix.'users';
	}
}
