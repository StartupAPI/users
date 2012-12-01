<?php

require_once(dirname(__FILE__) . '/Plan.php');

/**
 * Account class represents accounts in the system.
 *
 * Each account can have multiple users and usually it's a good idea to assign application items to accounts and not users.
 * Navigation bar will show account picker when user has multiple accounts assugned to them,
 *
 * Usage:
 * <code>
 * // Getting currently logged in user
 * $user = StartupAPI::requireLogin();
 *
 * // Getting currently selected account
 * $account = $user->getCurrentAccount();
 * </code>
 *
 * @package StartupAPI
 */
class Account {

	private $slug;

	/**
	 * @var string Account name
	 */
	private $name;

	/**
	 * @var Plan Subscription plan
	 */
	private $plan;
	private $schedule;
	private $charges;
	private $nextCharge;
	private $nextPlan;
	private $nextSchedule;
	private $paymentEngine;
	private $isIndividual;
	private $active;
	private $lastTransactionID;

	/**
	 * @var int Constant defining user role
	 */

	const ROLE_USER = 0;

	/**
	 * @var int Constant defining administrator role
	 */
	const ROLE_ADMIN = 1;

	/**
	 * Gets Account by ID
	 *
	 * @param int $id Account id
	 * @return Account Account associated with specified ID
	 * @throws DBException
	 */
	public static function getByID($id) {
		$db = UserConfig::getDB();
		$account = null;

		if (!($stmt = $db->prepare('SELECT name, plan_slug, schedule_slug, engine_slug,
			active, next_charge, next_plan_slug, next_schedule_slug FROM ' .
				UserConfig::$mysql_prefix . 'accounts WHERE id = ?'))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('i', $id)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		if (!$stmt->store_result()) {
			throw new DBException($db, $stmt, "Can't store result");
		}

		if (!$stmt->bind_result($name, $plan_slug, $schedule_slug, $engine_slug, $active, $next_charge, $next_plan_slug, $next_schedule_slug)) {
			throw new DBBindResultException($db, $stmt);
		}

		if ($stmt->fetch() === TRUE) {
			$charges = self::fillCharges($id);
			$account = new self($id, $name, $plan_slug, $schedule_slug, $engine_slug,
							$charges, $active, $next_charge, $next_plan_slug, $next_schedule_slug);
		}

		$stmt->free_result();
		$stmt->close();

		return $account;
	}

	/**
	 * Gets all accounts associated with the user
	 *
	 * @param User $user User we need accounts for
	 *
	 * @return array Array of user accounts
	 *
	 * @throws DBException
	 * @throws StartupAPIException
	 */
	public static function getUserAccounts(User $user) {
		$db = UserConfig::getDB();
		$accounts = array();
		$userid = $user->getID();

		if (!($stmt = $db->prepare(
				'SELECT a.id, a.name, a.plan_slug, a.schedule_slug, a.engine_slug, a.active, ' .
				'a.next_charge, a.next_plan_slug, a.next_schedule_slug, au.role  FROM ' .
				UserConfig::$mysql_prefix . 'accounts a INNER JOIN ' .
				UserConfig::$mysql_prefix . 'account_users au ON a.id = au.account_id ' .
				'WHERE au.user_id = ?'))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('i', $userid)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		if (!$stmt->store_result()) {
			throw new DBException($db, $stmt, "Can't store result");
		}

		if (!$stmt->bind_result($id, $name, $plan_slug, $schedule_slug, $engine_slug, $active, $next_charge, $role, $next_plan_slug, $next_schedule_slug)) {
			throw new DBBindResultException($db, $stmt);
		}

		while ($stmt->fetch() === TRUE) {
			$charges = self::fillCharges($id);
			$accounts[] = new self($id, $name, $plan_slug, $schedule_slug, $engine_slug,
							$charges, $active, $next_charge, $next_plan_slug, $next_schedule_slug);
		}

		$stmt->free_result();
		$stmt->close();

		if (count($accounts) == 0) {
			// there must be at least one personal account for each user
			throw new StartupAPIException("No accounts are set for the user");
		}

		return $accounts;
	}

	/**
	 * Gets all accounts associated with the user
	 *
	 * @param User $user User we need accounts for
	 *
	 * @return array Array of User, role pairs
	 *
	 * @throws DBException
	 * @throws StartupAPIException
	 */
	public static function getUserAccountsAndRoles(User $user) {
		$db = UserConfig::getDB();
		$accounts = array();
		$userid = $user->getID();

		if (!($stmt = $db->prepare(
				'SELECT a.id, a.name, a.plan_slug, a.schedule_slug, a.engine_slug, a.active, ' .
				'a.next_charge, a.next_plan_slug, a.next_schedule_slug, au.role  FROM ' .
				UserConfig::$mysql_prefix . 'accounts a INNER JOIN ' .
				UserConfig::$mysql_prefix . 'account_users au ON a.id = au.account_id ' .
				'WHERE au.user_id = ?'))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('i', $userid)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		if (!$stmt->store_result()) {
			throw new DBException($db, $stmt, "Can't store result");
		}

		if (!$stmt->bind_result($id, $name, $plan_slug, $schedule_slug, $engine_slug, $active, $next_charge, $next_plan_slug, $next_schedule_slug, $role)) {
			throw new DBBindResultException($db, $stmt);
		}

		while ($stmt->fetch() === TRUE) {
			$charges = self::fillCharges($id);
			$accounts[] = array(
				new self($id, $name, $plan_slug, $schedule_slug, $engine_slug,
						$charges, $active, $next_charge, $next_plan_slug, $next_schedule_slug),
				$role
			);
		}

		$stmt->free_result();
		$stmt->close();

		if (count($accounts) == 0) {
			// there must be at least one personal account for each user
			throw new StartupAPIException("No accounts are set for the user");
		}

		return $accounts;
	}

	/**
	 * Returns total number of accounts in the system
	 *
	 * @return int Total number of accounts in the system
	 *
	 * @throws DBException
	 */
	public static function getTotalAccounts() {
		$db = UserConfig::getDB();

		$total = 0;

		if ($stmt = $db->prepare('SELECT COUNT(*) FROM ' . UserConfig::$mysql_prefix . 'accounts')) {
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($total)) {
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}

		return $total;
	}

	/**
	 * Creates new Account object
	 *
	 * @param int $id
	 * @param string $name
	 * @param Plan $plan
	 * @param int $role
	 */
	private function __construct($id, $name, $plan_slug, $schedule_slug = NULL, $engine_slug = NULL, $charges = NULL, $active = TRUE, $next_charge = NULL, $next_plan_slug = NULL, $next_schedule_slug = NULL) {
		$this->id = $id;
		$this->name = $name;

		$this->plan = is_null($plan_slug) ? NULL : Plan::getPlanBySlug($plan_slug);
		if (is_null($this->plan)) {
			$this->plan = Plan::getPlanBySlug(UserConfig::$default_plan_slug);
		}
		$this->schedule = is_null($schedule_slug) || is_null($this->plan) ?
				NULL : $this->plan->getPaymentScheduleBySlug($schedule_slug);
		$this->nextCharge = is_null($this->schedule) ? NULL : $next_charge;
		$this->active = $active;
		$this->nextPlan = is_null($next_plan_slug) ? NULL : Plan::getPlanBySlug($next_plan_slug);
		$this->nextSchedule = is_null($next_schedule_slug) || is_null($this->nextPlan) ?
				NULL : $this->nextPlan->getPaymentScheduleBySlug($next_schedule_slug);

		if ($engine_slug !== NULL) {
			UserConfig::loadModule($engine_slug);
			$this->paymentEngine = new $engine_slug;
		}

		$this->charges = is_null($charges) ? array() : $charges;
		$this->lastTransactionID = NULL;
	}

	/**
	 * Returns account ID
	 *
	 * @return int Account ID
	 */
	public function getID() {
		return $this->id;
	}

	/**
	 * Returns Accoutn name
	 *
	 * @return string Account Name
	 */
	public function getName() {
		if ($this->isIndividual) {
			$users = $this->getUsers();
			return $users[0][0]->getName();
		} else {
			return $this->name;
		}
	}

	/**
	 * Returns a list of account users
	 *
	 * @return array Array of User, role pairs
	 *
	 * @throws DBException
	 */
	public function getUsers() {
		$db = UserConfig::getDB();
		$roles = array();

		if (!($stmt = $db->prepare('SELECT user_id, role FROM ' . UserConfig::$mysql_prefix .
				'account_users WHERE account_id = ?'))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('i', $this->id)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		if (!$stmt->bind_result($userid, $role)) {
			throw new DBBindResultException($db, $stmt);
		}

		while ($stmt->fetch() === TRUE) {
			$roles[$userid] = $role;
		}

		$stmt->close();

		$users = User::getUsersByIDs(array_keys($roles));
		$users_and_roles = array();

		foreach ($users as $user) {
			$users_and_roles[] = array($user, $roles[$user->getID()]);
		}

		return $users_and_roles;
	}


	/**
	 * Returns user's role for this account
	 *
	 * @param User User
	 *
	 * @return int|null User's role or null if user is not a member of the account
	 *
	 * @throws DBException
	 */
	public function getUserRole($user) {
		$db = UserConfig::getDB();
		$role = null;

		if (!($stmt = $db->prepare('SELECT role FROM ' . UserConfig::$mysql_prefix .
				'account_users WHERE account_id = ? AND user_id = ?'))) {
			throw new DBPrepareStmtException($db);
		}

		$user_id = $user->getID();

		if (!$stmt->bind_param('ii', $this->id, $user_id)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		if (!$stmt->bind_result($role)) {
			throw new DBBindResultException($db, $stmt);
		}

		$stmt->fetch();
		$stmt->close();

		return $role;
	}


	/**
	 * Adds a user to account
	 *
	 * @param User $user User to add
	 * @param int $role User role in the account
	 *
	 * @throws DBException
	 */
	public function addUser($user, $role = self::ROLE_USER) {
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('INSERT IGNORE INTO ' . UserConfig::$mysql_prefix . 'account_users (account_id, user_id, role) VALUES (?, ?, ?)')) {
			if (!$stmt->bind_param('iii', $this->id, $userid, $role)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}
	}

	/**
	 * Remove user from account
	 *
	 * @param User $user User to add
	 *
	 * @throws DBException
	 */
	public function removeUser($user) {
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('DELETE FROM ' . UserConfig::$mysql_prefix . 'account_users WHERE account_id = ? AND user_id = ?')) {
			if (!$stmt->bind_param('ii', $this->id, $userid)) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
		}
	}

	/**
	 * Returns account's subscription plan
	 *
	 * @return Plan Subscription Plan
	 */
	public function getPlan() {
		return $this->plan;
	}

	public function getSchedule() {
		return $this->schedule;
	}

	public function isActive() {
		return $this->active;
	}

	public function getCharges() {
		return $this->charges;
	}

	public function getNextCharge() {
		return $this->nextCharge;
	}

	public function getNextPlan() {
		return $this->nextPlan;
	}

	public function getNextSchedule() {
		return $this->nextSchedule;
	}

	/**
	 * Creates new account and optionally adds a user to it
	 *
	 * @param string $name Account Name
	 * @param Plan $plan Subscription Plan
	 * @param User $user User to add to account
	 * @param integer $role User's role in the account (either Account::ROLE_USER or Account::ROLE_ADMIN)
	 *
	 * @return Account Newly created account
	 *
	 * @throws DBException
	 */
	public static function createAccount($name, $plan_slug, $schedule_slug = null, $user = null, $role = Account::ROLE_USER, $engine_slug = null) {
		$name = mb_convert_encoding($name, 'UTF-8');

		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('INSERT INTO ' . UserConfig::$mysql_prefix .
				'accounts (name, plan_slug, schedule_slug, engine_slug) VALUES (?, ?, ?, ?)'))) {
			throw new DBPrepareStmtException($db);
		}


		if (!$stmt->bind_param('ssss', $name, $plan_slug, $schedule_slug, $engine_slug)) {
			throw new Exception("Can't bind parameter" . $stmt->error);
		}

		if (!$stmt->execute()) {
			throw new Exception("Can't execute statement: " . $stmt->error);
		}

		$id = $stmt->insert_id;

		$stmt->close();

		$account = new self($id, $name, $plan_slug, NULL, $engine_slug);
		$account->activatePlan($plan_slug, $schedule_slug);
		TransactionLogger::Log($id, is_null($engine_slug) ? NULL : $account->paymentEngine->getSlug(), 0, 'Account created');

		if ($user !== null) {
			$account->addUser($user, $role);
		}

		return $account;
	}

	/**
	 * Returns user's current account
	 *
	 * @param User $user
	 *
	 * @return Account
	 *
	 * @throws DBException
	 * @throws StartupAPIException
	 */
	public static function getCurrentAccount($user) {
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if (!($stmt = $db->prepare(
				'SELECT a.id, a.name, a.plan_slug, a.schedule_slug, a.engine_slug, a.active, ' .
				'a.next_charge, a.next_plan_slug, a.next_schedule_slug, au.role FROM ' .
				UserConfig::$mysql_prefix . 'user_preferences up INNER JOIN ' .
				UserConfig::$mysql_prefix . 'accounts a ON a.id = up.current_account_id INNER JOIN ' .
				UserConfig::$mysql_prefix . 'account_users au ON a.id = au.account_id ' .
				'WHERE up.user_id = ? AND au.user_id = ?'))) {
			throw new DBPrepareStmtException($db);
		}

		$id = null;

		if (!$stmt->bind_param('ii', $userid, $userid)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		if (!$stmt->bind_result($id, $name, $plan_slug, $schedule_slug, $engine_slug, $active, $next_charge, $next_plan_slug, $next_schedule_slug, $role)) {
			throw new DBBindResultException($db, $stmt);
		}

		$stmt->fetch();
		$stmt->close();

		if ($id) {
			$charges = self::fillCharges($id);
			return new self($id, $name, $plan_slug, $schedule_slug, $engine_slug,
							$charges, $active, $next_charge,
							$next_plan_slug, $next_schedule_slug);
		} else {
			$user_accounts = self::getUserAccounts($user);

			if (count($user_accounts) > 0) {
				$user_accounts[0]->setAsCurrent($user);
				return $user_accounts[0];
			}
		}

		throw new Exception("No accounts are set for the user");
	}

	/**
	 * Setts this account as current for the user
	 *
	 * @param User $user
	 *
	 * @throws DBException
	 */
	public function setAsCurrent($user) {
		$db = UserConfig::getDB();

		$accounts = self::getUserAccounts($user);

		$valid_account = false;
		foreach ($accounts as $account) {
			if ($this->isTheSameAs($account)) {
				$valid_account = true;
				break;
			}
		}

		if (!$valid_account) {
			return; // silently ignore if user is not connected to this account
		}

		if ($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
				'user_preferences SET current_account_id = ? WHERE user_id = ?')) {
			$userid = $user->getID();

			if (!$stmt->bind_param('ii', $this->id, $userid)) {
				throw new DBBindParamException($db, $stmt);
			}

			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt, "Can't update user preferences (set current account)");
			}
			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db, "Can't update user preferences (set current account)");
		}

		$this->lastTransactionID =
				TransactionLogger::Log($this->id, is_null($this->paymentEngine) ?
								NULL : $this->paymentEngine->getSlug(), 0, 'Account set as current');
	}

	/**
	 * Compares two account objects
	 *
	 * @param Account $account
	 *
	 * @return boolean True if two account objects refer to the same account
	 */
	public function isTheSameAs($account) {
		if (is_null($account)) {
			return false;
		}

		return $this->getID() == $account->getID();
	}

	/**
	 * Returns true if account has requested feature enabled
	 *
	 * @param Feature feature Feature object representing on of the features that can be enabled for the account
	 *
	 * @return boolean True if account has requested feature enabled
	 */
	public function hasFeature($feature) {
		// checking if we got feature ID instead of object for backwards compatibility
		if (is_int($feature)) {
			$feature = Feature::getByID($feature);
		}

		return $feature->isEnabledForAccount($this);
	}

	/**
	 * @todo rework to just be part of or called from constructor
	 */
	private static function fillCharges($account_id) {

		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('SELECT date_time, amount FROM ' .
				UserConfig::$mysql_prefix . 'account_charge WHERE account_id = ? ' .
				'ORDER BY date_time'))) {
			throw new Exception("Can't prepare statement: " . $db->error);
		}

		if (!$stmt->bind_param('i', $account_id)) {
			throw new Exception("Can't bind parameter" . $stmt->error);
		}

		if (!$stmt->execute()) {
			throw new Exception("Can't execute statement: " . $stmt->error);
		}

		if (!$stmt->bind_result($datetime, $amount)) {
			throw new Exception("Can't bind result: " . $stmt->error);
		}

		$charges = array();
		while ($stmt->fetch() === TRUE) {
			$charges[] = array('datetime' => $datetime, 'amount' => sprintf("%.2f", $amount));
		}

		$stmt->close();
		return $charges;
	}

	public function paymentIsDue($refund = NULL) { // refund is almost the same, as general payment
		if (is_null($this->schedule)) {
			return;
		}

		if (is_null($this->schedule) && is_null($refund)) {
			return;
		}

		$charge_amount = is_null($refund) ? $this->schedule->charge_amount : $refund;
		// Look if there is a positive charge (actually, account surplus), it should be a single element
		$c = reset(array_keys($this->charges));

		$db = UserConfig::getDB();

		// Lock tables
		$db->query("LOCK TABLES " . UserConfig::$mysql_prefix . "account_charge WRITE");

		if ($c !== FALSE && $this->charges[$c]['amount'] > 0) {
			if ($this->charges[$c]['amount'] - $charge_amount < 0) {
				// This charge is greater than we owe to user

				$charge_amount -= $this->charges[$c]['amount'];

				if (!($stmt = $db->prepare('DELETE FROM ' . UserConfig::$mysql_prefix .
						'account_charge WHERE account_id = ?'))) {
					throw new Exception("Can't prepare statement: " . $db->error);
				}

				if (!$stmt->bind_param('i', $this->id)) {
					throw new Exception("Can't bind parameter" . $stmt->error);
				}

				if (!$stmt->execute()) {
					throw new Exception("Can't execute statement: " . $stmt->error);
				}

				$this->charges = array();
				$stmt->close();
			} else { // We still owe to user
				if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
						'account_charge SET amount = ? WHERE account_id = ?'))) {
					throw new Exception("Can't prepare statement: " . $db->error);
				}

				$amt = $this->charges[$c]['amount'] - $charge_amount;
				if (!$stmt->bind_param('di', $amt, $this->id)) {
					throw new Exception("Can't bind parameter" . $stmt->error);
				}

				if (!$stmt->execute()) {
					throw new Exception("Can't execute statement: " . $stmt->error);
				}

				// Put into the object
				$this->charges[$c]['amount'] -= $charge_amount;
				$stmt->close();

				// ???
				// $charge_amount += $this->charges[$c]['amount'];
				$charge_amount = 0;
			}
		}

		// Rest of $charge_amount should be charged
		if ($charge_amount > 0) {

			$charge = array('datetime' => date('Y-m-d H:i:s'),
				'amount' => -$charge_amount);
			$this->charges[] = $charge;

			if (!($stmt = $db->prepare('INSERT INTO ' . UserConfig::$mysql_prefix .
					'account_charge (account_id, date_time, amount) VALUES (?, ?, ?)'))) {
				throw new Exception("Can't prepare statement: " . $db->error);
			}

			if (!$stmt->bind_param('isd', $this->id, $charge['datetime'], $charge['amount'])) {
				throw new Exception("Can't bind parameter" . $stmt->error);
			}

			if (!$stmt->execute()) {
				throw new Exception("Can't execute statement: " . $stmt->error);
			}

			$stmt->close();
		}

		$db->query("UNLOCK TABLES");

		if (is_null($refund)) {
			$this->lastTransactionID =
					TransactionLogger::Log($this->id, is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(), -$this->schedule->charge_amount, 'Payment Schedule charge');
		} else {
			$this->lastTransactionID =
					TransactionLogger::Log($this->id, is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(), -$refund, 'Refund recorded');
		}

		return TRUE;
	}

	public function paymentReceived($amount) {

		$cleared = array();
		$db = UserConfig::getDB();
		$amount_to_log = $amount;

		// Lock tables
		$db->query("LOCK TABLES " . UserConfig::$mysql_prefix .
				"account_charge WRITE");
		foreach (array_reverse(array_keys($this->charges)) as $n => $k) {

			if ($amount <= 0) {
				break;
			}
			if (-$this->charges[$k]['amount'] <= $amount) {
				$amount += $this->charges[$k]['amount'];
				$cleared[] = $this->charges[$k];
				unset($this->charges[$k]);
			} else {
				$this->charges[$k]['amount'] += $amount;

				if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
						'account_charge SET amount = ? ' .
						'WHERE account_id = ? and date_time = ?'))) {
					throw new Exception("Can't prepare statement: " . $db->error);
				}

				if (!$stmt->bind_param('dis', $this->charges[$k]['amount'], $this->id, $this->charges[$k]['datetime'])) {
					throw new Exception("Can't bind parameter" . $stmt->error);
				}

				if (!$stmt->execute()) {
					throw new Exception("Can't execute statement: " . $stmt->error);
				}

				$amount = 0;
				$stmt->close();
			}
		}

		foreach ($cleared as $n => $k) {

			if (!($stmt = $db->prepare('DELETE FROM ' . UserConfig::$mysql_prefix .
					'account_charge WHERE account_id = ? and date_time = ?'))) {
				throw new Exception("Can't prepare statement: " . $db->error);
			}

			if (!$stmt->bind_param('is', $this->id, $k['datetime'])) {
				throw new Exception("Can't bind parameter" . $stmt->error);
			}

			if (!$stmt->execute()) {
				throw new Exception("Can't execute statement: " . $stmt->error);
			}

			$stmt->close();
		}

		// Store excessive payment as positive charge (account surplus)
		if ($amount > 0) {
			$charge = array('datetime' => date('Y-m-d H:i:s'),
				'amount' => $amount);
			$this->charges[] = $charge;

			if (!($stmt = $db->prepare('INSERT INTO ' . UserConfig::$mysql_prefix .
					'account_charge (account_id, date_time, amount) VALUES (?, ?, ?)'))) {
				throw new Exception("Can't prepare statement: " . $db->error);
			}

			if (!$stmt->bind_param('isd', $this->id, $charge['datetime'], $charge['amount'])) {
				throw new Exception("Can't bind parameter" . $stmt->error);
			}

			if (!$stmt->execute()) {
				throw new Exception("Can't execute statement: " . $stmt->error);
			}

			$stmt->close();
		}

		$db->query("UNLOCK TABLES");

		if ($this->getBalance() >= 0 && !$this->active) {
			TransactionLogger::Log($this->id, is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(), 0, 'Account activated due to positive balance');
			$this->activate();
		}

		$this->lastTransactionID =
				TransactionLogger::Log($this->id, is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(), $amount_to_log, 'Payment received');
		return TRUE;
	}

	public function activatePlan($plan_slug, $schedule_slug = NULL) {

		$new_plan = Plan::getPlanBySlug($plan_slug);
		if (is_null($new_plan) || $new_plan === FALSE) {
			return FALSE;
		}

		if (!is_null($schedule_slug)) {
			$new_schedule = $new_plan->getPaymentScheduleBySlug($schedule_slug);
			if (is_null($new_schedule)) {
				$new_schedule = $new_plan->getDefaultPaymentSchedule();
			}
		} else {
			$new_schedule = NULL;
		}

		/* if no schedule specified and no default schedule found
		  and new plan has at least one schedule, fail */
		if (count($new_plan->getPaymentScheduleSlugs()) && is_null($new_schedule)) {
			return FALSE;
		}

		$old_plan_slug = $this->plan->slug;
		$old_schedule_slug = is_null($this->schedule) ? NULL : $this->schedule->slug;
		$this->plan->deactivate_hook($this->id, $plan_slug, $schedule_slug);
		$this->plan = $new_plan;
		$this->schedule = $new_schedule;
		$this->plan->activate_hook($this->id, $old_plan_slug, $old_schedule_slug);
		$this->active = 1;
		$this->nextCharge = is_null($this->schedule) ?
				NULL : date('Y-m-d H:i:s', time() + $this->schedule->charge_period * 86400);

		/*
		 * @TODO
		 * Update db There is a risk that this query fail. If so, object state
		 * will differ from db state. Should be addressed in further releases.
		 */
		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
				'accounts SET plan_slug = ?, schedule_slug = ?, active = 1, next_charge = ?, ' .
				'next_plan_slug = NULL, next_schedule_slug = NULL WHERE id = ?'))) {
			throw new Exception("Can't prepare statement: " . $db->error);
		}

		if (!$stmt->bind_param('sssi', $plan_slug, $schedule_slug, $this->nextCharge, $this->id)) {
			throw new Exception("Can't bind parameter" . $stmt->error);
		}

		if (!$stmt->execute()) {
			throw new Exception("Can't execute statement: " . $stmt->error);
		}

		$this->paymentIsDue();
		$this->lastTransactionID =
				TransactionLogger::Log($this->id, is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(), 0, 'Plan "' . $this->plan->name . '" activated');
		return TRUE;
	}

	public function deactivatePlan() {
		$this->plan->deactivate_hook($this->id, $this->downgrade_to, NULL);

		if (!is_null($this->downgrade_to)) {

			$this->activatePlan($this->downgrade_to);
			$this->lastTransactionID =
					TransactionLogger::Log($this->id, is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(), 0, 'Plan downgraded to "' . $this->plan->name . '"');
			return TRUE;
		} else {

			// Nothing to downgrade to - mark account as not active
			$this->suspend();
			$this->lastTransactionID =
					TransactionLogger::Log($this->id, is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(), 0, 'Account suspended due to plan "' . $this->plan->name . '" deactivation');
			return FALSE;
		}
	}

	public function setPaymentSchedule($schedule_slug) {

		if (!($schedule = $this->plan->getPaymentScheduleBySlug($schedule_slug))) {
			return FALSE;
		}

		$this->schedule = $schedule;
		$this->nextCharge = date('Y-m-d H:i:s', time() + $this->schedule->charge_period * 86400);

		// Update db
		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
				'accounts SET schedule_slug = ?, next_charge = ?, next_plan_slug = NULL, ' .
				'next_schedule_slug = NULL WHERE id = ?'))) {
			throw new Exception("Can't prepare statement: " . $db->error);
		}

		if (!$stmt->bind_param('ssi', $schedule_slug, $this->nextCharge, $this->id)) {
			throw new Exception("Can't bind parameters: " . $stmt->error);
		}

		if (!$stmt->execute()) {
			throw new Exception("Can't execute statement: " . $stmt->error);
		}

		// Bill user
		$this->paymentIsDue();
		$this->lastTransactionID =
				TransactionLogger::Log($this->id, is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(), 0, 'Payment schedule "' . $this->schedule->name . '" set.');
		return TRUE;
	}

	public function getScheduleSlug() {
		return $this->schedule ? $this->schedule->slug : NULL;
	}

	public function getPlanSlug() {
		return $this->plan->slug;
	}

	public function getPaymentEngine() {
		return $this->paymentEngine;
	}

	public function isIndividual() {
		return $this->isIndividual;
	}

	public function setPaymentEngine($engine_slug) {
		if ($engine_slug == NULL) {
			return FALSE;
		}

		UserConfig::loadModule($engine_slug);
		$this->paymentEngine = new $engine_slug;

		// Update db
		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
				'accounts SET engine_slug = ? WHERE id = ?'))) {
			throw new Exception("Can't prepare statement: " . $db->error);
		}

		if (!$stmt->bind_param('si', $engine_slug, $this->id)) {
			throw new Exception("Can't bind parameters: " . $stmt->error);
		}

		if (!$stmt->execute()) {
			throw new Exception("Can't execute statement: " . $stmt->error);
		}

		$this->lastTransactionID =
				TransactionLogger::Log($this->id, is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(), 0, 'Payment engine "' . $this->paymentEngine->getSlug() . '" set.');
		return TRUE;
	}

	public function getBalance() {

		if (is_null($this->charges)) {
			return 0;
		}

		$balance = 0;
		foreach ($this->charges as $c) {
			$balance += floatval($c['amount']);
		}

		return $balance;
	}

	public function planChangeRequest($plan_slug, $schedule_slug) {
		// Sanity checks
		$new_plan = Plan::getPlanBySlug($plan_slug);
		if (is_null($new_plan) || $new_plan === FALSE) {
			return FALSE;
		}
		if (!is_null($schedule_slug)) {
			$new_schedule = $new_plan->getPaymentScheduleBySlug($schedule_slug);
			if (is_null($new_schedule)) {
				$new_schedule = $new_plan->getDefaultPaymentSchedule();
			}
		} else {
			$new_schedule = NULL;
		}
		/* Check, if plan/schedule could be activated immediately
		  It could, if:
		  1. current plan has no schedule
		  2. Account balance is equal or greater than next schedule charge
		  or next plan also has no schedule
		 */

		if (is_null($this->nextCharge) && (is_null($new_schedule) ||
				$this->getBalance() >= $new_schedule->charge_amount)) {

			if (!is_null($this->paymentEngine)) {
				$this->paymentEngine->changeSubscription($plan_slug, $schedule_slug);
			}

			return $this->activatePlan($plan_slug, $schedule_slug);
		}

		// if no schedule specified and no default schedule found
		// and new plan has at least one shcedule, fail
		if (count($new_plan->getPaymentScheduleSlugs()) && is_null($new_schedule)) {
			return FALSE;
		}

		// If requested plan/schedule is same as current plan/schedule, cancel any pending request
		if ($this->plan->slug == $plan_slug && ((!is_null($this->schedule) &&
				$this->schedule->slug == $schedule_slug) || (is_null($this->schedule) && is_null($schedule_slug)))) {
			return $this->cancelChangeRequest();
		}

		// Update db
		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
				'accounts SET next_plan_slug = ?, next_schedule_slug = ? WHERE id = ?'))) {
			throw new Exception("Can't prepare statement: " . $db->error);
		}

		if (!$stmt->bind_param('ssi', $plan_slug, $schedule_slug, $this->id)) {
			throw new Exception("Can't bind parameters: " . $stmt->error);
		}

		if (!$stmt->execute()) {
			throw new Exception("Can't execute statement: " . $stmt->error);
		}

		$this->lastTransactionID =
				TransactionLogger::Log($this->id, is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(), 0, 'Request to change plan to "' . $new_plan->name .
						(is_null($new_schedule) ? '"' : '" and schedule to "' . $new_schedule->name) . '" stored.');
		return TRUE;
	}

	public function scheduleChangeRequest($schedule_slug) {
		if (!($schedule = $this->plan->getPaymentScheduleBySlug($schedule_slug))) {
			return FALSE;
		}

		// Check, if schedule could be activated immediately
		if (is_null($this->nextCharge) &&
				$this->getBalance() >= $schedule->charge_amount) {
			if (!is_null($this->paymentEngine)) {
				$this->paymentEngine->changeSubscription($this->plan, $schedule);
			}

			return $this->setPaymentSchedule($schedule_slug);
		}

		// If requested schedule is same as current schedule, cancel any pending request
		if (!is_null($this->schedule) && $this->schedule->slug == $schedule_slug) {
			return $this->cancelChangeRequest();
		}

		// Update db
		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
				'accounts SET next_plan_slug = plan_slug, next_schedule_slug = ? WHERE id = ?'))) {
			throw new Exception("Can't prepare statement: " . $db->error);
		}

		if (!$stmt->bind_param('si', $schedule_slug, $this->id)) {
			throw new Exception("Can't bind parameters: " . $stmt->error);
		}

		if (!$stmt->execute()) {
			throw new Exception("Can't execute statement: " . $stmt->error);
		}

		$this->lastTransactionID =
				TransactionLogger::Log($this->id, is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(), 0, 'Request to change schedule to "' . $schedule->name . '" stored.');
		return TRUE;
	}

	public function suspend() {
		$this->active = 0;

		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
				'accounts SET active = 0 WHERE id = ?'))) {
			throw new Exception("Can't prepare statement: " . $db->error);
		}

		if (!$stmt->bind_param('i', $this->id)) {
			throw new Exception("Can't bind parameters: " . $stmt->error);
		}

		if (!$stmt->execute()) {
			throw new Exception("Can't execute statement: " . $stmt->error);
		}

		return TRUE;
	}

	public function activate() {
		$this->active = 1;

		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
				'accounts SET active = 1 WHERE id = ?'))) {
			throw new Exception("Can't prepare statement: " . $db->error);
		}

		if (!$stmt->bind_param('i', $this->id)) {
			throw new Exception("Can't bind parameters: " . $stmt->error);
		}

		if (!$stmt->execute()) {
			throw new Exception("Can't execute statement: " . $stmt->error);
		}

		return TRUE;
	}

	public function getLastTransactionID() {
		return $this->lastTransactionID;
	}

	public function cancelChangeRequest() {

		// Cancel any pending request to change plan and/or schedule.

		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
				'accounts SET next_plan_slug = NULL, next_schedule_slug = NULL ' .
				'WHERE id = ?'))) {
			throw new Exception("Can't prepare statement: " . $db->error);
		}

		if (!$stmt->bind_param('i', $this->id)) {
			throw new Exception("Can't bind parameters: " . $stmt->error);
		}

		if (!$stmt->execute()) {
			throw new Exception("Can't execute statement: " . $stmt->error);
		}

		$this->nextPlan = NULL;
		$this->nextSchedule = NULL;

		$this->lastTransactionID =
				TransactionLogger::Log($this->id, is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(), 0, 'Plan/schedule change request cancelled.');

		return TRUE;
	}

}
