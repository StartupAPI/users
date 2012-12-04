<?php

/**
 * @package StartupAPI
 * @subpackage Subscriptions
 */
require_once (dirname(__FILE__) . '/PaymentSchedule.php');

/**
 * Every account is associated with existing Plan using Plan Slug and PaymentSchedule using PaymentSchedule Slug.
 */
class Plan {

	private $slug;

	/**
	 * @var string Plan name
	 */
	private $name;
	private $description;
	private $base_price;
	private $base_period;
	private $details_url;
	private $capabilities;
	private $downgrade_to;
	private $grace_period;
	private $payment_schedules;
	private $user_activate_hook;
	private $user_deactivate_hook;
	private static $Plans = array();

	public function __construct($slug, $a) {

		# Known parameters and their default values listed here:
		$parameters = array(
			'slug' => NULL,
			'name' => NULL,
			'description' => NULL,
			'base_price' => NULL,
			'base_period' => NULL,
			'details_url' => NULL,
			'payment_schedules' => array(),
			'capabilities' => array(),
			'downgrade_to' => UserConfig::$default_plan_slug,
			'grace_period' => NULL,
			'user_activate_hook' => NULL,
			'user_deactivate_hook' => NULL
		);

		if ($slug === NULL) {
			throw new Exception("slug required");
		}

		if (!is_array($a)) {
			throw new Exception("configuration array required");
		}

		$a['slug'] = $slug;

		# Mandatory parameters are those whose default value is NULL
		$mandatory = array('slug', 'name');

		$missing = array_diff($mandatory, array_keys($a));
		if (count($missing)) {
			throw new Exception("Following mandatory parameters were not found in init array for plan $slug: " . implode(',', $missing));
		}

		# Set attributes according to init array
		foreach ($parameters as $p => $v) {
			if (isset($a[$p])) {
				$this->$p = $a[$p];
			} else {
				$this->$p = $v;
			}
		}

		# If downgrade_to has the same slug as we have, reset it to null
		if ($this->slug == $this->downgrade_to) {
			$this->downgrade_to = NULL;
		}

		# Instantiate PaymentSchedules, replacing stored parameters arrays with actual objects
		if (is_array($this->payment_schedules)) {
			$schedules = array();
			foreach ($this->payment_schedules as $slug => $s) {
				$schedules[] = new PaymentSchedule($slug, $s);
			}

			$this->payment_schedules = $schedules;

			if (!$this->getDefaultPaymentSchedule() && count($this->payment_schedules)) {
				$this->payment_schedules[0]->setAsDefault();
			}
		}

		# Check user hooks
		if ($this->user_activate_hook != '' && !function_exists($this->user_activate_hook)) {
			throw new Exception("Activate hook function " . $this->user_activate_hook . " is not defined");
		}

		if ($this->user_deactivate_hook != '' && !function_exists($this->user_deactivate_hook)) {
			throw new Exception("Deactivate hook function " . $this->user_deactivate_hook . " is not defined");
		}

		self::$Plans[] = $this;

		# We are all set
	}

	/**
	 * Makes private variables visible, but read-only
	 */
	public function __get($v) {
		return (!in_array($v, array('instance')) && isset($this->$v)) ? $this->$v : false;
	}

	public function getPaymentScheduleSlugs() {
		$slugs = array();

		foreach ($this->payment_schedules as $schedule) {
			$slugs[] = $schedule->slug;
		}

		return $slugs;
	}

	/**
	 * Returns schedule slugs
	 *
	 * @param string $slug Schedule slug
	 *
	 * @return PaymentSchedule|null|false Returns false if null slug is passed,
	 * null if there is no schedule with such slug and PaymentSchedule if found
	 */
	public function getPaymentScheduleBySlug($slug) {

		if ($slug === NULL) {
			return FALSE;
		}

		if (is_array($this->payment_schedules)) {
			foreach ($this->payment_schedules as $x => $s) {
				if ($s->slug == $slug) {
					return $s;
				}
			}
		}
		return NULL;
	}

	public function getDefaultPaymentSchedule() {

		if (is_array($this->payment_schedules))
			foreach ($this->payment_schedules as $x => $s) {
				if ($s->is_default) {
					return $s;
				}
			}

		return NULL;
	}

	public function activate_hook($account_id, $plan_slug, $schedule_slug) {

		if ($this->user_activate_hook == '') {
			return;
		}

		call_user_func_array($this->user_activate_hook, array(
			'AccountID' => $account_id,
			'OldPlanSlug' => $plan_slug,
			'OldScheduleSlug' => $schedule_slug,
		));
	}

	public function deactivate_hook($account_id, $plan_slug, $schedule_slug) {

		if ($this->user_deactivate_hook == '') {
			return;
		}

		call_user_func_array($this->user_deactivate_hook, array(
			'AccountID' => $account_id,
			'NewPlanSlug' => $plan_slug,
			'NewScheduleSlug' => $schedule_slug,
		));
	}

	public static function init($a) {

//    if (count(self::$Plans))
//      throw new Exception("Already initialized");

		self::$Plans = array(); // Isn't it an init?

		if (!is_array($a)) {
			throw new Exception("Parameter is not an array");
		}

		foreach ($a as $slug => $p) {
			new self($slug, $p);
		}
	}

	/**
	 * Returns plan based on a slug string
	 *
	 * @param string $slug Plan slug
	 *
	 * @return Plan|false|null Returns false if null slug is passed,
	 * null if there is no such plan with a slug and Plan object if found
	 */
	public static function getPlanBySlug($slug) {

		if ($slug === NULL || !count(self::$Plans)) {
			return FALSE;
		}

		foreach (self::$Plans as $p) {
			if ($p->slug == $slug) {
				return $p;
			}
		}

		return NULL;
	}

	public static function getPlanSlugs() {

		if (!count(self::$Plans)) {
			return FALSE;
		}

		$slugs = array();
		foreach (self::$Plans as $p) {
			$slugs[] = $p->slug;
		}

		return $slugs;
	}

}
