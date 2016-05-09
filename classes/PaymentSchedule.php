<?php
namespace StartupAPI;

/**
 * @package StartupAPI
 * @subpackage Subscriptions
 */

/**
 * Every account is associated with existing Plan using Plan ID and PaymentSchedule using PaymentScheduleID.
 */
class PaymentSchedule {

	/**
	 * @var string Payment schedule slug
	 */
	private $slug;

	/**
	 * @var string Payment schedule name
	 */
	private $name;

	/**
	 * @var string Patment schedule description
	 */
	private $description;

	/**
	 * @var int Charge amount
	 */
	private $charge_amount;

	/**
	 * @var int Number of days between charges
	 */
	private $charge_period;

	/**
	 * @var boolean Is this schedule a default one
	 */
	private $is_default;

	/**
	 * Creates a new schedule based on parameters passed
	 *
	 * @param string $slug Schedule slug
	 * @param mixed[] $a Array of schedule parameters
	 *
	 * @throws Exception
	 */
	public function __construct($slug, $settings) {

		if (!$slug) {
			throw new Exception("slug required");
		}

		if (!is_array($settings)) {
			throw new Exception("configuration array required");
		}

		if (!isset($settings['name'])) {
			throw new Exception("Name is a required parameter for a payment schedule");
		}

		if (!isset($settings['charge_amount']) || $settings['charge_amount'] <= 0) {
			throw new Exception("Positive charge amount must be defined for a payment schedule");
		}

		if (!isset($settings['charge_period']) || $settings['charge_period'] < 1) {
			throw new Exception("Charge period of 1 or more days must be defined for a payment schedule");
		}

		// mandatory slug checked above
		$this->slug = $slug;
		$this->name = $settings['name'];
		$this->charge_amount = $settings['charge_amount'];
		$this->charge_period = $settings['charge_period'];

		// these are initialized to non-null value if not set
		$this->description = isset($settings['description']) ? $settings['description'] : '';
		$this->is_default = isset($settings['charge_period']) && $settings['charge_period'] ? TRUE : FALSE;
	}

	/**
	 * @return string Payment schedule slug
	 */
	public function getSlug() {
		return $this->slug;
	}

	/**
	 * @return string Payment schedule name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string Payment schedule description
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return int Charge amount
	 */
	public function getChargeAmount() {
		return $this->charge_amount;
	}

	/**
	 * @return int Charge period
	 */
	public function getChargePeriod() {
		return $this->charge_period;
	}

	/**
	 * @return boolean True if this is default schedule for plan
	 */
	public function isDefault() {
		return $this->is_default ? TRUE : FALSE;
	}

	/**
	 * Sets this schedule as default
	 */
	public function setAsDefault() {
		$this->is_default = TRUE;
	}

}
