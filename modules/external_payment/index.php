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

	public function __construct() {

		$this->slug = 'external_payment';
		if (!self::$loaded) {
			parent::__construct();
			self::$loaded = true;
		}
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
