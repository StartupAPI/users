<?php
namespace StartupAPI;

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
