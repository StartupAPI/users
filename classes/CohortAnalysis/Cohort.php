<?php
namespace StartupAPI\CohortAnalysis;

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
