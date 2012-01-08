<?php
/**
 * @package StartupAPI
 * @subpackage Subscriptions
 */

/**
 * Every account is associated with existing Plan using Plan ID and PaymentSchedule using PaymentScheduleID.
 */
class Plan {
	public $id;
	public $name;
	public $description;
	public $basePrice;
	public $period;
	public $detailsURL;
	public $capabilities;
	public $downgradeTo;
	public $gracePeriod;
}
