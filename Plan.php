<?php
require_once(dirname(__FILE__).'/config.php');
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
            $plan_free_id = UserConfig::$plan_free;
            self::$FREE = new Plan($plan_free_id, UserConfig::$PLANS[$plan_free_id]['name'], true);
            self::addPlan(self::$FREE);
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

        public function getFreePlan() {
                return self::$FREE;
        }

}
Plan::init();
