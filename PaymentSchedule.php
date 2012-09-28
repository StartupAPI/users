<?php
/**
 * Every account is associated with existing Plan using Plan ID and PaymentSchedule using PaymentScheduleID.
 *
 * @package StartupAPI
 * @subpackage Subscriptions
 */
class PaymentSchedule {
	public $id;
	public $name;
	public $description;
	public $chargeAmount;
	public $chargePeriod;
}
