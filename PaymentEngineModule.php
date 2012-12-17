<?php
/**
 * @package StartupAPI
 * @subpackage Subscriptions
 */

require_once(dirname(__FILE__) . '/Account.php');
require_once(dirname(__FILE__) . '/StartupAPIModule.php');

/**
 * Abstract class representing payment engines users can use to pay for subscription
 */
abstract class PaymentEngine extends StartupAPIModule {

	/**
	 * @var string Payment engine slug
	 */
	protected $engineSlug;

	/**
	 * Creates pagement engine and registers it in the system
	 */
	public function __construct() {
		parent::__construct();
		UserConfig::$payment_modules[] = $this;
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
	 */
	public abstract function getSlug();

	/**
	 * Records transaction details
	 *
	 * @param int $transaction_id Transaction ID
	 * @param mixed[] $details Transaction details array
	 */
	public abstract function storeTransactionDetails($transaction_id, $details);

	/**
	 * Returns details for transaction, specific to particular payment engine
	 *
	 * @param int $transaction_id Transaction ID
	 */
	public abstract function expandTransactionDetails($transaction_id);

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
	 * @todo Rename to getSlug()
	 */
	public function getID() {
		return $this->getSlug();
	}

	/**
	 * This method is called daily to initiate payments and update accounts
	 *
	 * @throws DBException
	 */
	public function cronHandler() {
		$db = UserConfig::getDB();

		// Deactivate all accounts where grace period is expired
		// Those accounts should have negative balance, so we can restrict 'where' condition

		if (!($stmt = $db->prepare('SELECT a.id, UNIX_TIMESTAMP(MIN(ac.date_time)) FROM ' . UserConfig::$mysql_prefix . 'accounts a INNER JOIN ' .
				UserConfig::$mysql_prefix . 'account_charge ac ON a.id = ac.account_id AND ac.amount > 0 AND engine_slug = ? GROUP BY a.id'))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('s', $this->engineSlug)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		if (!$stmt->store_result()) {
			throw new DBException($db, $stmt, "Can't store result");
		}

		if (!$stmt->bind_result($account_id, $date_time)) {
			throw new DBBindResultException($db, $stmt);
		}

		while ($stmt->fetch() === TRUE) {

			if (is_null($account = Account::getByID($account_id))) {
				continue;
			}

			if (is_null($plan = $account->getPlan())) {
				continue;
			}

			if ($plan->grace_period && $plan->grace_period * 86400 + $date_time > time()) {
				$account->deactivatePlan();
			}
		}

		// Find all accounts which are served by this Payment Engine and which has next_charge date earlier than now

		if (!($stmt = $db->prepare('SELECT id, plan_slug, next_plan_slug, schedule_slug, next_schedule_slug FROM ' .
				UserConfig::$mysql_prefix . 'accounts WHERE engine_slug = ? AND active = 1 AND next_charge < ?'))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('ss', $this->engineSlug, date('Y-m-d H:i:s'))) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		if (!$stmt->store_result()) {
			throw new DBException($db, $stmt, "Can't store result");
		}

		if (!$stmt->bind_result($account_id, $plan_slug, $next_plan_slug, $schedule_slug, $next_schedule_slug)) {
			throw new DBBindResultException($db, $stmt);
		}

		while ($stmt->fetch() === TRUE) {

			// If plan/schedule change requested, perform
			// Again, check if user balance is sufficient to do this change

			if (is_null($account = Account::getByID($account_id))) {
				continue;
			}

			$billed = 0;
			if (!is_null($next_plan_slug) || !is_null($next_schedule_slug)) {

				$next_plan = Plan::getPlanBySlug($next_plan_slug);
				$next_schedule = is_null($next_plan) ? NULL : $next_plan->getPaymentScheduleBySlug($next_schedule_slug);

				// ok if either no next_schedule, or balance is sufficient
				$ok = is_null($next_schedule) || $account->getBalance() >= $next_schedule->charge_amount;

				if (!is_null($next_plan) && $ok) { // next_plan found, change could be performed
					if ($next_plan_slug == $plan_slug) { // only change schedule
						$account->setPaymentSchedule($next_schedule_slug); // schedule changed, user billed automatically
						$billed = 1;
					} else { // change plan and schedule
						$account->activatePlan($next_plan_slug, $next_schedule_slug);
						$billed = 1;
					}
				} // othrewise plan and schedule left as it was before
			}

			// Bill user if not yet billed
			if (!$billed) {

				$account->paymentIsDue();

				$plan = $account->getPlan();
				if (!is_null($schedule = $plan->getPaymentSchedule())) {

					// Set new next_charge
					if (!($stmt2 = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
							'accounts SET next_charge = next_charge + INTERVAL ? DAY WHERE id = ?'))) {
						throw new DBPrepareStmtException($db);
					}

					if (!$stmt2->bind_param('ii', $schedule->charge_period, $account->getID())) {
						throw new DBBindParamException($db, $stmt);
					}

					if (!$stmt2->execute()) {
						throw new DBExecuteStmtException($db, $stmt);
					}

					$stmt2->close();
				}
			}
		} // end of while
	}

}
