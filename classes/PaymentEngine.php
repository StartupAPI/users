<?php
namespace StartupAPI;

/**
 * Abstract class representing payment engines users can use to pay for subscription
 *
 * @package StartupAPI
 * @subpackage Subscriptions
 */
abstract class PaymentEngine extends StartupAPIModule {

	/**
	 * @var string Payment engine slug
	 */
	protected $slug;

	/**
	 * Creates pagement engine and registers it in the system
	 */
	public function __construct() {
		parent::__construct();
		UserConfig::$payment_modules[] = $this;
	}

	/**
	 * Returns true if module requires prepayment to be used.
	 *
	 * Modules that require pre-payment will only enable payment schedules that
	 * require payment that is less then account balance.
	 *
	 * @return boolean True if module requires prepayment
	 */
	public function requiresPrePayment() {
		return FALSE;
	}

	/**
	 * Returns action URL to send user to to accept payments for specific plan and schedule.
	 *
	 * Must be defined by all engines that do not require prepayment.
	 *
	 * @param Plan $plan Payment plan user is trying to switch to
	 * @param PaymentSchedule $schedule Payment schedule user is trying to use
	 * @param Account $account Account to change subscription for
	 *
	 * @return null
	 */
	public function getActionURL($plan = null, $schedule = null, $account = null) {
		return null;
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
		return "Sign Up";
	}

	/**
	 * Called when subscription for account changes
	 *
	 * @param int $account_id Account ID
	 * @param string $plan_slug Plan slug
	 * @param string $schedule_slug Payment schedule slug
	 */
	public abstract function changeSubscription($account_id, $plan_slug, $schedule_slug);

	/**
	 * Returns engine slug
	 *
	 * @return string Engine slug
	 */
	public function getSlug() {
		return $this->slug;
	}

	/**
	 * Returns payment engine for a specififed slug
	 *
	 * @param string $slug Payment engine slug
	 *
	 * @return PaymentEngine|null Payment engine or null if payment engine is not registered
	 */
	public static function getEngineBySlug($slug) {
		return StartupAPIModule::get($slug);
	}

	/**
	 * Records transaction details, must be overriden if there are any details available
	 *
	 * @param int $transaction_id Transaction ID
	 * @param mixed[] $details Transaction details array
	 */
	public function storeTransactionDetails($transaction_id, $details) {
		return TRUE;
	}

	/**
	 * Returns details for transaction, specific to particular payment engine
	 *
	 * @param int $transaction_id Transaction ID
	 *
	 * @return mixed[]|false Retrieves array of transaction details or false if none available
	 */
	public function expandTransactionDetails($transaction_id) {
		return FALSE;
	}

	/**
	 * Renders transation details if they are available
	 *
	 * @param int $transaction_id Transaction ID
	 *
	 * @return string HTML rendering of transaction details
	 */
	public function renderTransactionLogDetails($transaction_id) {
		return "";
	}

	/**
	 * Indicates that user interaction required and therefore admins
	 * can't just switch plans through admin UI
	 *
	 * For most classes it would return TRUE (default), but for manual (pre-payment / invoicing)
	 * engine, which process payments offline, this will return FALSE.
	 *
	 * @return boolean
	 */
	public function isUserInteractionRequired() {
		return TRUE;
	}

	/**
	 * This method should be called by subsclasses which actually receives information about payment
	 *
	 * @param array $data Array with "account_id" and "amount" keys
	 *
	 * @return int|false Transaction id or false if account ID not specified
	 *
	 * @throws Exception
	 */
	public function paymentReceived($data) {
		// data is array with following required keys:
		// account_id
		// amount
		//

		if (!(isset($data['account_id']) && isset($data['amount']))) {
			throw new Exception("No account_id in element " . print_r($data, 1));
		}

		$account = Account::getByID($data['account_id']);
		if (is_null($account)) {
			return FALSE;
		}
		$account->paymentReceived($data['amount']);

		return $account->getLastTransactionID();
	}

	/**
	 * Process refund
	 *
	 * @param array $data Array with "account_id" and "amount" keys
	 *
	 * @return int|false Transaction id or false if account ID not specified
	 *
	 * @throws Exception
	 */
	public function refund($data) {
		// refund is generally the same, as payment

		if (!(isset($data['account_id']) && isset($data['amount']))) {
			throw new Exception("No account_id in element " . print_r($data, 1));
		}

		$account = Account::getByID($data['account_id']);
		if (is_null($account)) {
			return FALSE;
		}
		$account->paymentIsDue($data['amount']);

		return $account->getLastTransactionID();
	}

	/**
	 * Unsubscribes user from all plans
	 *
	 * @param type $account_id
	 *
	 * @return false if account does not exist
	 */
	public function unsubscribe($account_id) {
		$account = Account::getByID($account_id);
		if (is_null($account)) {
			return FALSE;
		}
		while ($account->deactivatePlan()) {

		}
	}

	/**
	 * Returns payment engine slug (not ID)
	 *
	 * @return string Payment engine slug
	 *
	 * @todo Rename getID in StartupAPIModule to getSlug()
	 */
	public function getID() {
		return $this->getSlug();
	}

	/**
	 * This method is called daily to initiate payments and update accounts
	 *
	 * @throws Exceptions\DBException
	 */
	public function cronHandler() {
		$db = UserConfig::getDB();

		// Deactivate all accounts where grace period is expired
		// Those accounts should have negative balance, so we can restrict 'where' condition

		if (!($stmt = $db->prepare('SELECT a.id, UNIX_TIMESTAMP(MIN(ac.date_time)) FROM u_accounts a INNER JOIN u_account_charge ac ON a.id = ac.account_id AND ac.amount > 0 AND engine_slug = ? GROUP BY a.id'))) {
			throw new Exceptions\DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('s', $this->slug)) {
			throw new Exceptions\DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new Exceptions\DBExecuteStmtException($db, $stmt);
		}

		if (!$stmt->store_result()) {
			throw new Exceptions\DBException($db, $stmt, "Can't store result");
		}

		if (!$stmt->bind_result($account_id, $date_time)) {
			throw new Exceptions\DBBindResultException($db, $stmt);
		}

		while ($stmt->fetch() === TRUE) {

			if (is_null($account = Account::getByID($account_id))) {
				continue;
			}

			$plan = $account->getPlan(); // can be FALSE
			if (!$plan) {
				continue;
			}

			$grace_period = $plan->getGracePeriod();
			if ($grace_period && $grace_period * 86400 + $date_time > time()) {
				$account->deactivatePlan();
			}
		}

		// Find all accounts which are served by this Payment Engine and which has next_charge date earlier than now

		if (!($stmt = $db->prepare('SELECT id, plan_slug, next_plan_slug, schedule_slug, next_schedule_slug, next_engine_slug ' .
				'FROM u_accounts ' .
				'WHERE engine_slug = ? AND active = 1 AND next_charge < ?'))) {
			throw new Exceptions\DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('ss', $this->slug, date('Y-m-d H:i:s'))) {
			throw new Exceptions\DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new Exceptions\DBExecuteStmtException($db, $stmt);
		}

		if (!$stmt->store_result()) {
			throw new Exceptions\DBException($db, $stmt, "Can't store result");
		}

		if (!$stmt->bind_result($account_id, $plan_slug, $next_plan_slug, $schedule_slug, $next_schedule_slug, $next_engine_slug)) {
			throw new Exceptions\DBBindResultException($db, $stmt);
		}

		while ($stmt->fetch() === TRUE) {

			// If plan/schedule change requested, perform
			// Again, check if user balance is sufficient to do this change

			if (is_null($account = Account::getByID($account_id))) {
				continue;
			}

			$billed = 0;
			if (!is_null($next_plan_slug) || !is_null($next_schedule_slug) || !is_null($next_engine_slug)) {

				$next_plan = Plan::getPlanBySlug($next_plan_slug);
				$next_schedule = is_null($next_plan) ? NULL : $next_plan->getPaymentScheduleBySlug($next_schedule_slug);
				$next_engine = PaymentEngine::getEngineBySlug($next_schedule_slug);

				// ok if either no next_schedule, or balance is sufficient
				$ok = is_null($next_schedule)
					|| $account->getBalance() >= $next_schedule->getChargeAmount() && !is_null($next_engine);

				if (!is_null($next_plan) && $ok) { // next_plan found, change could be performed
					if ($next_plan_slug == $plan_slug) { // only change schedule
						$account->setPaymentSchedule($next_schedule_slug, $next_engine_slug); // schedule changed, user billed automatically
						$billed = 1;
					} else { // change plan and schedule
						$account->activatePlan($next_plan_slug, $next_schedule_slug, $next_engine_slug);
						$billed = 1;
					}
				} // othrewise plan and schedule left as it was before
			}

			// Bill user if not yet billed
			if (!$billed) {

				$account->paymentIsDue();

				$plan = $account->getPlan(); // can be FALSE
				if ($plan && !is_null($schedule = $account->getSchedule())) {

					// Set new next_charge
					if (!($stmt2 = $db->prepare('UPDATE u_accounts SET next_charge = next_charge + INTERVAL ? DAY WHERE id = ?'))) {
						throw new Exceptions\DBPrepareStmtException($db);
					}

					if (!$stmt2->bind_param('ii', $schedule->getChargePeriod(), $account->getID())) {
						throw new Exceptions\DBBindParamException($db, $stmt);
					}

					if (!$stmt2->execute()) {
						throw new Exceptions\DBExecuteStmtException($db, $stmt);
					}

					$stmt2->close();
				}
			}
		} // end of while
	}

}
