<?php

/**
 * @package StartupAPI
 * @subpackage Subscriptions
 */

/**
 * Fake payment engine that just goes to external page and adds value to account when user comes back
 */
class ExternalPaymentEngine extends PaymentEngine {

	/**
	 * @var int Counter of instances created
	 */
	private static $instance_counter = 0;

	/**
	 * @var int Instance number
	 */
	private $instance_number = 0;

	/**
	 * @var string URL to redirect user to when they click sign up button
	 */
	private $url;
	private $button_label;

	/**
	 * @param string $url External URL to send users to when they try to pay
	 */
	public function __construct($url = null, $button_label = null) {
		self::$instance_counter++;
		$this->instance_number = self::$instance_counter;

		$this->slug = 'external_payment_' . $this->instance_number;

		$this->url = $url;
		$this->button_label = $button_label;

		if (is_null($this->url)) {
			$this->url = UserConfig::$USERSROOTFULLURL . '/modules/external_payment/external_page.php';
		}

		$this->url .= (strstr($this->url, '?') === FALSE ? '?' : '&') . 'engine=' . $this->slug;

		if (is_null($this->button_label)) {
			$this->button_label = 'Fake';
		}

		parent::__construct();
	}

	/**
	 * Returns a URL for external payment UI
	 *
	 * @param Plan $plan Plan to swtitch to
	 * @param PaymentSchedule $schedule Payment schedule to use
	 * @param Account $account Account being upgraded
	 *
	 * @return string
	 */
	public function getActionURL($plan = null, $schedule = null, $account = null) {
		return $this->url . (strstr($this->url, '?') === FALSE ? '?' : '&') .
				'plan=' . $plan->getSlug() .
				'&schedule=' . $schedule->slug .
				'&account=' . $account->getID();
	}

	/**
	 * Returns button label to use for payment engine sign up
	 *
	 * @param Plan $plan Payment plan user is trying to switch to
	 * @param PaymentSchedule $schedule Payment schedule user is trying to use
	 * @param Account $account Account to change subscription for
	 *
	 * @return string
	 */
	public function getActionButtonLabel($plan = null, $schedule = null, $account = null) {
		return $this->button_label;
	}

	public static function getModulesTitle() {
		return "External Payment Processing";
	}

	public function getTitle() {
		return "External Payment Processing #" . $this->instance_number . ' (' . $this->button_label . ')';
	}

	public static function getModulesDescription() {
		return "<p>Fake payment engine that just goes to external page and adds value to account when user comes back</p>";
	}

	public function getDescription() {
		return self::getModulesDescription();
	}

	/**
	 * Called when subscription changes
	 *
	 * @param int $account_id Account ID
	 * @param string $plan_slug Plan slug
	 * @param string $schedule_slug Payment schedule slug
	 *
	 * @return true
	 */
	public function changeSubscription($account_id, $plan_slug, $schedule_slug) {

		// Okay
		return TRUE;
	}

}
