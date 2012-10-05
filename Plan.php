<?php
/**
 * Subscription plan
 *
 * Allows defining custom plans for accounts, including default free plan
 *
 * Quite rudimentary at the moment, will be replaced by detailed implementation
 * as part of Subscriptions implementation project
 *
 * @package StartupAPI
 * @subpackage Subscriptions
 */
class Plan {
	/**
	 * @var Plan Default free plan object
	 */
	private static $FREE;

	/**
	 * @var array All plans registered with the system
	 */
	private static $plans = array();

	/**
	 * @var int Numeric plan ID
	 */
	private $id;

	/**
	 * @var string Plan name
	 */
	private $name;

	/**
	 * @var boolean Allows some simplified behavior for accounts with one user
	 */
	private $individual;

	/**
	 * Registers plan in the system
	 *
	 * @param Plan $plan Plan object to register
	 *
	 * @deprecated
	 */
	private static function addPlan($plan) {
		$plans[$plan->getID()] = $plan;
	}

	/**
	 * Returns plan by ID
	 *
	 * @param int $id Plan ID
	 *
	 * @return Plan Plan object
	 */
	public static function getByID($id) {
		return self::$plans[$id];
	}

	/**
	 * Returns all plans registered in the system
	 *
	 * @return array All plans in the system
	 */
	public static function getPlans() {
		return self::$plans;
	}

	/**
	 * Static initializer, registers default Free plan with ID of zero
	 */
	public static function init() {
		self::$FREE = new Plan(0, 'Free', true);
	}

	/**
	 * Returns free plan
	 *
	 * @return Plan
	 */
	public static function getFreePlan() {
		return self::$FREE;
	}

	/**
	 * Creates a new plan and registers it in the system
	 *
	 * @param int $id Plan ID
	 * @param string $name Plan name
	 * @param boolean $individual Set to true if plan is for a single user
	 */
	public function __construct($id, $name, $individual = false) {
		$this->id = $id;
		$this->name = $name;
		$this->individual = $individual ? true : false;

		self::$plans[$id] = $this;
	}

	/**
	 * Returns true if plan is for a single user
	 *
	 * @return boolean True if plan is for a single user
	 */
	public function isIndividual() {
		return $this->individual;
	}

	/**
	 * Returns Plan ID
	 * @return int Plan ID
	 */
	public function getID() {
		return $this->id;
	}

	/**
	 * Returns plan name
	 *
	 * @return string Plan name
	 */
	public function getName() {
		return $this->name;
	}
}
Plan::init();
