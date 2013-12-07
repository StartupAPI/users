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
	 * @var boolean Singleton flag
	 */
	private static $loaded = false;

	/**
	 * @var string URL to redirect user to when they click sign up button
	 */
	private $url;

	private $button_label;

	/**
	 * @param string $url External URL to send users to when they try to pay
	 */
	public function __construct($url = null, $button_label = null) {
		$this->url = $url;
		$this->button_label = $button_label;

		if (is_null($this->url)) {
			$this->url = UserConfig::$USERSROOTFULLURL . '/modules/external_payment/external_page.php';
		}

		if (is_null($this->button_label)) {
			$this->button_label = 'Fake';
		}

		$this->slug = 'external_payment';
		if (!self::$loaded) {
			parent::__construct();
			self::$loaded = true;
		}
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
				return $this->url .
						'?plan=' . $plan->slug .
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
