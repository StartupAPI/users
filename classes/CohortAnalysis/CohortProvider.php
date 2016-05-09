<?php
namespace StartupAPI\CohortAnalysis;

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
	 * @throws Exceptions\DBException
	 */
	public function getActivityRate($activityid, $actnum)
	{
		$db = \StartupAPI\UserConfig::getDB();

		$siteadminsstring = null;
		if (count(\StartupAPI\UserConfig::$admins) > 0) {
			$siteadminsstring = implode(", ", \StartupAPI\UserConfig::$admins);
		}

		$aggregates = array();

		$query = 'SELECT u.cohort_id AS cohort_id,
			FLOOR(DATEDIFF(a.time, u.regtime) / ?) AS actperiod,
			COUNT(DISTINCT u.id) AS total
			FROM `u_activity` AS a
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
					throw new \StartupAPI\Exceptions\DBBindParamException($db, $stmt);
				}
			} else {
				if (!$stmt->bind_param('i', $actnum))
				{
					throw new \StartupAPI\Exceptions\DBBindParamException($db, $stmt);
				}
			}
			if (!$stmt->execute())
			{
				throw new \StartupAPI\Exceptions\DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($cohort_id, $actperiod, $activeusers))
			{
				throw new \StartupAPI\Exceptions\DBBindResultException($db, $stmt);
			}

			while($stmt->fetch() === TRUE)
			{
				$aggregates[$cohort_id][$actperiod] = $activeusers;
			}

			$stmt->close();
		}
		else
		{
			throw new \StartupAPI\Exceptions\DBPrepareStmtException($db);
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
