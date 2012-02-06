<?php
/**
 * @package StartupAPI
 * @subpackage Subscriptions
 */

/**
 * Every account is associated with existing Plan using Plan ID and PaymentSchedule using PaymentScheduleID.
 */
class PaymentSchedule {
	public $id;
	public $name;
	public $description;
	public $chargeAmount;
	public $chargePeriod;
}
