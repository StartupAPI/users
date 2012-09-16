<?php
/**
 * @package StartupAPI
 * @subpackage Subscriptions
 */
class Plan {
	private static $FREE;

	private static $plans = array();

	private $id;
	private $name;

	// allows some simplified behavior for accounts with one user
	private $individual;

	private static function addPlan($plan) {
		$plans[$plan->getID()] = $plan;
	}

	public static function getByID($id) {
		return self::$plans[$id];
	}

	public static function getPlans() {
		return self::$plans;
	}

	public static function init() {
		self::$FREE = new Plan(0, 'Free', true);
		self::addPlan(self::$FREE);
	}

	public static function getFreePlan() {
		return self::$FREE;
	}

	public function __construct($id, $name, $individual = false) {
		$this->id = $id;
		$this->name = $name;
		$this->individual = $individual ? true : false;

		self::$plans[$id] = $this;
	}

	public function isIndividual() {
		return $this->individual;
	}

	public function getID() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}
}
Plan::init();
