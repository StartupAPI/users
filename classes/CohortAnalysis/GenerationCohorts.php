<?php
namespace StartupAPI\CohortAnalysis;

/**
 * This class allows dropping users into cohorts by registration date
 *
 * @package StartupAPI
 * @subpackage Analytics\CohortAnalysis
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
	 * @throws \StartupAPI\Exceptions\StartupAPIException
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
				throw new \StartupAPI\Exceptions\StartupAPIException('Wrong generation period');
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
	 * @throws \StartupAPI\Exceptions\StartupAPIException
	 * @throws \StartupAPI\Exceptions\DBException
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
					FROM u_users";
				break;
			case self::YEAR:
				$query = "SELECT YEAR(regtime) AS cohort_id,
					YEAR(regtime) AS title,
					COUNT(*) AS totals
					FROM u_users";
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
					FROM u_users";
				break;
			default:
				throw new \StartupAPI\Exceptions\StartupAPIException('Wrong generation period');
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
				throw new \StartupAPI\DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($cohort_id, $title, $total))
			{
				throw new \StartupAPI\DBBindResultException($db, $stmt);
			}

			while($stmt->fetch() === TRUE)
			{
				$cohorts[] = new Cohort($cohort_id, $title, $total);
			}
			$stmt->close();
		}
		else
		{
			throw new \StartupAPI\DBPrepareStmtException($db);
		}

		return $cohorts;
	}

	/**
	 * SQL statement for separation of users into generations
	 *
	 * @return string SQL statement for generating a resultset with id, regtime and cohort_id
	 *
	 * @throws \StartupAPI\Exceptions\StartupAPIException
	 */
	public function getCohortSQL() {
		switch ($this->period) {
			case self::MONTH:
				$query = 'SELECT id, regtime, EXTRACT(YEAR_MONTH FROM regtime) AS cohort_id
					FROM u_users';
				break;
			case self::YEAR:
				$query = 'SELECT id, regtime, YEAR(regtime) AS cohort_id
					FROM u_users';
				break;
			case self::WEEK:
				$query = 'SELECT id, regtime, YEARWEEK(regtime) AS cohort_id
					FROM u_users';
				break;
			default:
				throw new \StartupAPI\Exceptions\StartupAPIException('Wrong generation period');
		}

		return $query;
	}
}
