<?php
namespace StartupAPI\CohortAnalysis;

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
	 * @throws Exceptions\DBException
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
		$query = 'SELECT regmodule AS cohort_id, COUNT(*) AS totals FROM u_users';

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
				throw new Exceptions\DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($cohort_id, $total))
			{
				throw new Exceptions\DBBindResultException($db, $stmt);
			}

			while($stmt->fetch() === TRUE)
			{
				$cohorts[] = new Cohort($cohort_id, $cohort_titles[$cohort_id], $total);
			}
			$stmt->close();
		}
		else
		{
			throw new Exceptions\DBPrepareStmtException($db);
		}

		return $cohorts;
	}

	/**
	 * SQL statement for separation of users into buckets by registration module
	 *
	 * @return string SQL statement for generating a resultset with id, regtime and cohort_id
	 */
	public function getCohortSQL() {
		return 'SELECT id, regtime, regmodule AS cohort_id FROM u_users';
	}
}
