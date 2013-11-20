<?php

/**
 * @package StartupAPI
 * @subpackage Subscriptions
 */
require_once (__DIR__ . '/PaymentSchedule.php');

/**
 * Every account is associated with existing Plan using Plan Slug and PaymentSchedule using PaymentSchedule Slug.
 */
class Plan {

	/**
	 * @var string Plan slug
	 */
	private $slug;

	/**
	 * @var string Plan name
	 */
	private $name;

	/**
	 * @var string Plan description
	 */
	private $description;

	/**
	 * @var int Base price to display for the plan (actual charges are managed by PaymentSchedule class)
	 */
	private $base_price;

	/**
	 * @var string Base plan price period to display for the plan (actual charge intervals managed by PaymentSchedule class)
	 */
	private $base_period;

	/**
	 * @var string Plan details page URL
	 */
	private $details_url;

	/**
	 * Capabilities supported by accounts that subscribe to this plan.
	 *
	 * You can test for those capabilities in your code.
	 *
	 * StartupAPI also supports following capabilities out of the box:
	 * - individual: true / false (indicates that account only allows for one member)
	 *
	 * @var mixed[] Array of capabilities
	 */
	private $capabilities;

	/**
	 * @var string Slug of the plan to downgrade to when subscription ends or cancelled
	 */
	private $downgrade_to;

	/**
	 * @var int Amount of days to wait after due payment is not recieved before downgrading the account
	 */
	private $grace_period;

	/**
	 * @var PaymentSchedule[] Array of payment schedules available for a plan
	 */
	private $payment_schedules;

	/**
	 * @var callable Plan activation hook, called when plan is activated for account
	 */
	private $user_activate_hook;

	/**
	 * @var callable Plan deactivation hook, called when plan is deactivated for account
	 */
	private $user_deactivate_hook;

	/**
	 * @var Plan[] Array of currently registered plans in the system
	 */
	private static $Plans = array();

	/**
	 * Creates new plan
	 *
	 * @param string $slug Plan slug
	 * @param mixed[] $a Array of plan options
	 *
	 * @throws Exception
	 */
	public function __construct($slug, $a) {

		# Known parameters and their default values listed here:
		$parameters = array(
			'slug' => NULL,
			'name' => NULL,
			'available' => TRUE,
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
		if (!is_null($this->user_activate_hook) && !is_callable($this->user_activate_hook)) {
			throw new Exception("Activate hook defined, but is not callable");
		}

		if (!is_null($this->user_deactivate_hook) && !is_callable($this->user_deactivate_hook)) {
			throw new Exception("Deactivate hook defined, but is not callable");
		}

		self::$Plans[] = $this;

		# We are all set
	}

	/**
	 * Makes private properties visible, but read-only
	 *
	 * @param string $v Name of property to return
	 *
	 * @return mixed Value of property
	 */
	public function __get($v) {
		return (!in_array($v, array('instance')) && isset($this->$v)) ? $this->$v : false;
	}

	/**
	 * Returns and array of payment schedule slugs available for this plan
	 *
	 * @return string[]
	 */
	public function getPaymentScheduleSlugs() {
		$slugs = array();

		foreach ($this->payment_schedules as $schedule) {
			$slugs[] = $schedule->slug;
		}

		return $slugs;
	}

	/**
	 * Checks if plan has particular feature enabled
	 *
	 * @param Feature $feature Feature to check
	 *
	 * @return boolean Enabled / Disabled
	 */
	public function hasFeatureEnabled($feature) {
		if (array_key_exists('enable_features', $this->capabilities) && is_array($this->capabilities['enable_features'])) {
			return in_array($feature->getID(), $this->capabilities['enable_features']);
		}

		return false;
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

	/**
	 * Returns default payment schedule
	 *
	 * @return PaymentSchedule Default payment schedule
	 */
	public function getDefaultPaymentSchedule() {

		if (is_array($this->payment_schedules))
			foreach ($this->payment_schedules as $x => $s) {
				if ($s->is_default) {
					return $s;
				}
			}

		return NULL;
	}

	/**
	 * Calls account activation hooks registered for the plan
	 *
	 * @param int $account_id Account ID
	 * @param string $plan_slug Plan slug
	 * @param string $schedule_slug Payment schedule slug
	 */
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

	/**
	 * Calls account activation hooks registered for the plan
	 *
	 * @param int $account_id Account ID
	 * @param string $plan_slug Plan slug
	 * @param string $schedule_slug Payment schedule slug
	 */
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

	/**
	 * Initializes all plans based on parameters array
	 *
	 * @param mixed[] $plan_parameters Array of parameters for all plans
	 *
	 * @throws Exception
	 */
	public static function init($plan_parameters) {

//    if (count(self::$Plans))
//      throw new Exception("Already initialized");

		self::$Plans = array(); // Isn't it an init?

		if (!is_array($plan_parameters)) {
			throw new Exception("Parameter is not an array");
		}

		foreach ($plan_parameters as $slug => $param) {
			new self($slug, $param);
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

	/**
	 * Returns an array of slugs for all plans in the system
	 *
	 * @return string[]|FALSE Array of slug strings for plans in the system or FALSE if no plans are registered
	 */
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

	/**
	 * Checks if user actually has plan assigned and if not,
	 * redirects user to a page where they can get one
	 *
	 * @param Plan $plan Plan object or null / false to indicate that there is no plan
	 */
	public static function enforcePlan($plan) {
		if (!UserConfig::$IGNORE_CURRENT_ACCOUNT_PLAN_VERIFICATION && !$plan) {
			header('Location: ' . UserConfig::$USERSROOTURL . '/plans.php');
			exit;
		}
	}

}
