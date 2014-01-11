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
	 * @var boolean Indicates to users that plan is available for selection
	 */
	private $available;

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
	private $downgrade_to_slug;

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
	private $account_activate_hook;

	/**
	 * @var callable Plan deactivation hook, called when plan is deactivated for account
	 */
	private $account_deactivate_hook;

	/**
	 * @var Plan[] Array of currently registered plans in the system
	 */
	private static $plans = array();

	/**
	 * Creates new plan
	 *
	 * @param string $slug Plan slug
	 * @param mixed[] $a Array of plan options
	 *
	 * @throws Exception
	 */
	public function __construct($slug, $settings) {

		if (!$slug) {
			throw new Exception("Plan slug is required");
		}

		if (!is_array($settings)) {
			throw new Exception("Configuration array required");
		}

		$settings['slug'] = $slug;

		if (!array_key_exists('slug', $settings) && $settings['slug']) {
			throw new Exception("Mandatory parameter 'slug' was not found in init array for plan $slug");
		}

		if (!array_key_exists('name', $settings) && $settings['name']) {
			throw new Exception("Mandatory parameter 'name' was not found in init array for plan $slug");
		}

		// mandatory parameters checked above
		$this->slug = $settings['slug'];
		$this->name = $settings['name'];

		// these are initialized to non-null value if not set
		$this->available = isset($settings['available']) ? $settings['available'] : TRUE;
		$this->payment_schedules = isset($settings['payment_schedules']) ? $settings['payment_schedules'] : array();
		$this->downgrade_to_slug = isset($settings['downgrade_to']) ? $settings['downgrade_to'] : UserConfig::$default_plan_slug;

		// the rest are initialized to null if not set
		$this->description = isset($settings['description']) ? $settings['description'] : NULL;
		$this->base_price = isset($settings['base_price']) ? $settings['base_price'] : NULL;
		$this->base_period = isset($settings['base_period']) ? $settings['base_period'] : NULL;
		$this->details_url = isset($settings['details_url']) ? $settings['details_url'] : NULL;
		$this->capabilities = isset($settings['capabilities']) ? $settings['capabilities'] : NULL;
		$this->grace_period = isset($settings['grace_period']) ? $settings['grace_period'] : NULL;
		$this->account_activate_hook = isset($settings['account_activate_hook']) ? $settings['account_activate_hook'] : NULL;
		$this->account_deactivate_hook = isset($settings['account_deactivate_hook']) ? $settings['account_deactivate_hook'] : NULL;

		// If downgrade_to has the same slug as we have, reset it to null
		if ($this->slug == $this->downgrade_to_slug) {
			$this->downgrade_to_slug = NULL;
		}

		// Instantiate PaymentSchedules, replacing stored parameters arrays with actual objects
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
		if (!is_null($this->account_activate_hook) && !is_callable($this->account_activate_hook)) {
			throw new Exception("Activate hook defined, but is not callable");
		}

		if (!is_null($this->account_deactivate_hook) && !is_callable($this->account_deactivate_hook)) {
			throw new Exception("Deactivate hook defined, but is not callable");
		}

		// register new plan globally
		self::$plans[] = $this;
	}

	/**
	 * @return string Plan slug
	 */
	public function getSlug() {
		return $this->slug;
	}

	/**
	 * @return string Plan name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return boolean True if plan is available and False if it's registered, but currently disabled
	 */
	public function isAvailable() {
		return $this->available ? TRUE : FALSE;
	}

	/**
	 * @return PaymentSchedule[] Array of payment schedules for this plan
	 */
	public function getPaymentSchedules() {
		return $this->payment_schedules;
	}

	/**
	 * @return Plan Plan to downgrade to
	 */
	public function getDowngradeToPlan() {
		return self::getPlanBySlug($this->downgrade_to_slug);
	}

	/**
	 * @return string Plan descriptions
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return int Price to be displayed in UI (actually charged price is defined in payment schedules)
	 */
	public function getBasePrice() {
		return $this->base_price;
	}

	/**
	 * Returns charge period string (associated with base price) to be displayed in UI
	 * (actual periods used are defined in payment schedules)
	 *
	 * @return string Charge period to be displayed in UI
	 */
	public function getBasePeriod() {
		return $this->base_period;
	}

	/**
	 * @return string URL of plan details page
	 */
	public function getDetailsURL() {
		return $this->details_url;
	}

	/**
	 * @return mixed[] Array of capabilities enabled as part of this plan
	 */
	public function getCapabilities() {
		return $this->capabilities;
	}

	/**
	 * @return int Amount of days to wait after due payment is not recieved before downgrading the account
	 */
	public function getGracePeriod() {
		return $this->grace_period;
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
	 * Compares two plans (by matching slugs)
	 *
	 * @param Plan $other_plan Plan to compare to
	 *
	 * @return boolean True if same plan
	 */
	public function isTheSameAs($other_plan) {
		return $this->slug == $other_plan->getSlug();
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
	 * @param string $old_plan_slug Old plan slug
	 * @param string $old_schedule_slug Old payment schedule slug
	 * @param string $old_engine_slug Old payment engine slug
	 */
	public function activate_hook($account_id, $old_plan_slug, $old_schedule_slug, $old_engine_slug) {

		if (!is_callable($this->account_activate_hook)) {
			return;
		}

		call_user_func_array($this->account_activate_hook, array(
			$account_id,
			$old_plan_slug,
			$old_schedule_slug,
			$old_engine_slug
		));
	}

	/**
	 * Calls account activation hooks registered for the plan
	 *
	 * @param int $account_id Account ID
	 * @param string $new_plan_slug New plan slug
	 * @param string $new_schedule_slug New payment schedule slug
	 * @param string $new_engine_slug New payment engine slug
	 */
	public function deactivate_hook($account_id, $new_plan_slug, $new_schedule_slug, $new_engine_slug) {

		if (!is_callable($this->account_deactivate_hook)) {
			return;
		}

		call_user_func_array($this->account_deactivate_hook, array(
			$account_id,
			$new_plan_slug,
			$new_schedule_slug,
			$new_engine_slug,
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

		self::$plans = array(); // Isn't it an init?

		if (!is_array($plan_parameters)) {
			throw new Exception("Plan parameters is not an array");
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
		if ($slug === NULL || !count(self::$plans)) {
			return FALSE;
		}

		foreach (self::$plans as $p) {
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

		if (!count(self::$plans)) {
			return FALSE;
		}

		$slugs = array();
		foreach (self::$plans as $p) {
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
