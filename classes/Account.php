<?php

require_once(__DIR__ . '/Plan.php');

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

	/**
	 * @var int Account id
	 */
	private $id;

	/**
	 * @var string Account name
	 */
	private $name;

	/**
	 * @var Plan Subscription plan
	 */
	private $plan;

	/**
	 * @var PaymentSchedule Payment schedule
	 */
	private $schedule;

	/**
	 * @var mixed[] Array of charges (datetime, amount)
	 */
	private $charges;

	/**
	 * @var string Date/time of next charge
	 */
	private $nextCharge;

	/**
	 * @var Plan Next Plan object
	 */
	private $nextPlan;

	/**
	 * @var PaymentSchedule Next PaymentSchedule object
	 */
	private $nextSchedule;

	/**
	 * @var PaymentEngine Payment engine used by account
	 */
	private $paymentEngine;

	/**
	 * @var PaymentEngine Next payment engine object
	 */
	private $nextPaymentEngine;

	/**
	 * @var boolean True if account is active and false if disabled
	 */
	private $active;

	/**
	 * @var int ID of last transaction
	 */
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
			active, next_charge, next_plan_slug, next_schedule_slug, next_engine_slug FROM ' .
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

		if (!$stmt->bind_result($name, $plan_slug, $schedule_slug, $engine_slug, $active, $next_charge, $next_plan_slug, $next_schedule_slug, $next_engine_slug)) {
			throw new DBBindResultException($db, $stmt);
		}

		if ($stmt->fetch() === TRUE) {
			$charges = self::fillCharges($id);
			$account = new self($id, $name, $plan_slug, $schedule_slug, $engine_slug,
							$charges, $active, $next_charge, $next_plan_slug, $next_schedule_slug, $next_engine_slug);
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
	 * @return Account[] Array of user accounts
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
				'a.next_charge, a.next_plan_slug, a.next_schedule_slug, a.next_engine_slug, au.role  FROM ' .
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

		if (!$stmt->bind_result($id, $name, $plan_slug, $schedule_slug, $engine_slug, $active, $next_charge, $role, $next_plan_slug, $next_schedule_slug, $next_engine_slug)) {
			throw new DBBindResultException($db, $stmt);
		}

		while ($stmt->fetch() === TRUE) {
			$charges = self::fillCharges($id);
			$accounts[] = new self($id, $name, $plan_slug, $schedule_slug, $engine_slug,
							$charges, $active, $next_charge, $next_plan_slug, $next_schedule_slug, $next_engine_slug);
		}

		$stmt->free_result();
		$stmt->close();

		if (count($accounts) == 0) {
			// Creating personal account if it was never set (e.g. used deprecated UserConfig::$useAccounts = false setting)
			$personal_account = $user->createPersonalAccount();
			$accounts[] = $personal_account;
		}

		return $accounts;
	}

	/**
	 * Gets paged list of accounts in the system
	 *
	 * @return Account[] Array of user accounts
	 *
	 * @throws DBException
	 * @throws StartupAPIException
	 */
	public static function getAccounts($pagenumber = 0, $perpage = 20) {
		$db = UserConfig::getDB();
		$accounts = array();

		$first = $perpage * $pagenumber;

		if (!($stmt = $db->prepare(
				'SELECT id, name, plan_slug, schedule_slug, engine_slug, active,
					next_charge, next_plan_slug, next_schedule_slug, next_engine_slug
					FROM ' . UserConfig::$mysql_prefix . 'accounts
					LIMIT ?, ?'
				))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('ii', $first, $perpage)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		if (!$stmt->store_result()) {
			throw new DBException($db, $stmt, "Can't store result");
		}

		if (!$stmt->bind_result($id, $name, $plan_slug, $schedule_slug, $engine_slug, $active, $next_charge, $next_plan_slug, $next_schedule_slug, $next_engine_slug)) {
			throw new DBBindResultException($db, $stmt);
		}

		while ($stmt->fetch() === TRUE) {
			$accounts[] = new self($id, $name, $plan_slug, $schedule_slug, $engine_slug,
							null, $active, $next_charge, $next_plan_slug, $next_schedule_slug, $next_engine_slug);
		}

		$stmt->close();

		return $accounts;
	}

	/**
	 * Gets paged list of accounts in the system
	 *
	 * @return Account[] Array of user accounts
	 *
	 * @throws DBException
	 * @throws StartupAPIException
	 */
	public static function searchAccounts($search, $pagenumber = 0, $perpage = 20) {
		$db = UserConfig::getDB();
		$accounts = array();

		$first = $perpage * $pagenumber;

		if (!($stmt = $db->prepare(
				'SELECT id, name, plan_slug, schedule_slug, engine_slug, active,
					next_charge, next_plan_slug, next_schedule_slug, next_engine_slug
					FROM ' . UserConfig::$mysql_prefix . 'accounts
					WHERE INSTR(name, ?) > 0
					LIMIT ?, ?'
				))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('sii', $search, $first, $perpage)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		if (!$stmt->store_result()) {
			throw new DBException($db, $stmt, "Can't store result");
		}

		if (!$stmt->bind_result($id, $name, $plan_slug, $schedule_slug, $engine_slug, $active, $next_charge, $next_plan_slug, $next_schedule_slug, $next_engine_slug)) {
			throw new DBBindResultException($db, $stmt);
		}

		while ($stmt->fetch() === TRUE) {
			$accounts[] = new self($id, $name, $plan_slug, $schedule_slug, $engine_slug,
							null, $active, $next_charge, $next_plan_slug, $next_schedule_slug, $next_engine_slug);
		}

		$stmt->close();

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
				'a.next_charge, a.next_plan_slug, a.next_schedule_slug, a.next_engine_slug, au.role  FROM ' .
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

		if (!$stmt->bind_result($id, $name, $plan_slug, $schedule_slug, $engine_slug, $active, $next_charge, $next_plan_slug, $next_schedule_slug, $next_engine_slug, $role)) {
			throw new DBBindResultException($db, $stmt);
		}

		while ($stmt->fetch() === TRUE) {
			$charges = self::fillCharges($id);
			$accounts[] = array(
				new self($id, $name, $plan_slug, $schedule_slug, $engine_slug,
						$charges, $active, $next_charge, $next_plan_slug, $next_schedule_slug, $next_engine_slug),
				$role
			);
		}

		$stmt->free_result();
		$stmt->close();

		if (count($accounts) == 0) {
			// Creating personal account if it was never set (e.g. used deprecated UserConfig::$useAccounts = false setting)
			$personal_account = $user->createPersonalAccount();
			$accounts[] = array($personal_account, self::ROLE_ADMIN);
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
	 * @param int $id Account ID
	 * @param string $name Name of the account
	 * @param string $plan_slug Plan slug
	 * @param string $schedule_slug Payment schedule slug
	 * @param string $engine_slug Payment engine slug
	 * @param array[] $charges Array of charges (datetime, amount)
	 * @param boolean $active True if account is active
	 * @param string $next_charge String representation of next charge
	 * @param string $next_plan_slug Slug of next plan
	 * @param string $next_schedule_slug Slug of next payment schedule
	 * @param string $next_engine_slug Slug of next payment engine
	 */
	private function __construct($id, $name, $plan_slug, $schedule_slug = NULL, $engine_slug = NULL, $charges = NULL, $active = TRUE, $next_charge = NULL, $next_plan_slug = NULL, $next_schedule_slug = NULL, $next_engine_slug = NULL) {
		$this->id = $id;
		$this->name = $name;

		$this->plan = is_null($plan_slug) ? NULL : Plan::getPlanBySlug($plan_slug);
		if (is_null($this->plan)) {
			$this->plan = Plan::getPlanBySlug(UserConfig::$default_plan_slug);
		}

		$this->schedule = is_null($schedule_slug) || is_null($this->plan) ?
				NULL : $this->plan->getPaymentScheduleBySlug($schedule_slug);
		$this->active = $active;
		$this->nextPlan = is_null($next_plan_slug) ? NULL : Plan::getPlanBySlug($next_plan_slug);
		$this->nextSchedule = is_null($next_schedule_slug) || is_null($this->nextPlan) ?
				NULL : $this->nextPlan->getPaymentScheduleBySlug($next_schedule_slug);

		$this->nextPaymentEngine = is_null($next_engine_slug) ?
				NULL : PaymentEngine::getEngineBySlug($next_engine_slug);

		$this->nextCharge = (is_null($this->schedule) && is_null($this->nextSchedule)) ? NULL : $next_charge;

		if ($engine_slug !== NULL) {
			$this->paymentEngine = StartupAPIModule::get($engine_slug);
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
	 * Returns Account name
	 *
	 * @return string Account Name
	 */
	public function getName() {
		if ($this->isIndividual()) {
			$users = $this->getUsers();
			if (count($users) > 0) {
				return $users[0][0]->getName();
			}
		}
		return $this->name;
	}

	/**
	 * Updates account name
	 *
	 * @param string $name Account name
	 */
	public function setName($name) {
		$this->name = $name;

		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix . 'accounts
			SET name = ? WHERE id = ?'))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('si', $this->name, $this->id)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		$stmt->close();
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
	 * Sets user's role for this account
	 *
	 * @param User User
	 * @param boolean $admin Set to true if you want to promote user to admin false if demote to regular user of the account
	 *
	 * @throws DBException
	 */
	public function setUserRole($user, $admin) {
		$db = UserConfig::getDB();
		$role_num = $admin ? 1 : 0;

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix . 'account_users
					SET role = ?
					WHERE account_id = ? AND user_id = ?'
				))) {
			throw new DBPrepareStmtException($db);
		}

		$user_id = $user->getID();

		if (!$stmt->bind_param('iii', $role_num, $this->id, $user_id)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}
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

	/**
	 * Returns account payment schedule
	 *
	 * @return PaymentSchedule Payment Schedule
	 */
	public function getSchedule() {
		return $this->schedule;
	}

	/**
	 * Returns account status
	 *
	 * @return boolean True if account is active
	 */
	public function isActive() {
		return $this->active;
	}

	/**
	 * Returns account charges
	 *
	 * @return array[] Account charges
	 */
	public function getCharges() {
		if (is_null($this->charges)) {
			$this->charges = self::fillCharges($this->id);
		}

		return $this->charges;
	}

	/**
	 * Returns date of next charge
	 *
	 * @return string Date of next charge
	 */
	public function getNextCharge() {
		return $this->nextCharge;
	}

	/**
	 * Returns next plan
	 *
	 * @return Plan Next plan
	 */
	public function getNextPlan() {
		return $this->nextPlan;
	}

	/**
	 * Returns next payment schedule
	 *
	 * @return PaymentSchedule Next payment schedule
	 */
	public function getNextSchedule() {
		return $this->nextSchedule;
	}

	/**
	 * Creates new account and optionally adds a user to it
	 *
	 * @param string $name Account name
	 * @param string $plan_slug Subscription plan slug
	 * @param string $schedule_slug Payment schedule slug
	 * @param User $user User to add to account
	 * @param integer $role User's role in the account (either Account::ROLE_USER or Account::ROLE_ADMIN)
	 * @param string $engine_slug Payment engine slug
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
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		$id = $stmt->insert_id;

		$stmt->close();

		$account = new self($id, $name, $plan_slug, NULL, $engine_slug);
		$account->activatePlan($plan_slug, $schedule_slug, $engine_slug);
		TransactionLogger::Log($id, is_null($engine_slug) ? NULL : $account->paymentEngine->getSlug(), 0, 'Account created');

		if ($user !== null) {
			$account->addUser($user, $role);
		}

		return $account;
	}

	public static function createPersonalAccount($user) {
		return self::createAccount($user->getName(), UserConfig::$default_plan_slug, NULL, $user, self::ROLE_ADMIN, NULL);
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
				'a.next_charge, a.next_plan_slug, a.next_schedule_slug, a.next_engine_slug, au.role FROM ' .
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

		if (!$stmt->bind_result($id, $name, $plan_slug, $schedule_slug, $engine_slug, $active, $next_charge, $next_plan_slug, $next_schedule_slug, $next_engine_slug, $role)) {
			throw new DBBindResultException($db, $stmt);
		}

		$stmt->fetch();
		$stmt->close();

		$account = null;

		if ($id) {
			$charges = self::fillCharges($id);

			$account = new self($id, $name, $plan_slug, $schedule_slug, $engine_slug,
							$charges, $active, $next_charge,
							$next_plan_slug, $next_schedule_slug, $next_engine_slug);
		} else {
			$user_accounts = self::getUserAccounts($user);

			if (count($user_accounts) > 0) {
				$user_accounts[0]->setAsCurrent($user);
				$account = $user_accounts[0];
			}
		}

		if (!is_null($account)) {
			$plan = $account->getPlan(); // can be FALSE
			// redirecting to account UI if plan not set
			Plan::enforcePlan($plan);

			return $account;
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
	 * Explicitly enable features on the list for account and removes the rest of the features
	 * (global roll-out will still apply)
	 *
	 * @param array $features Array of Feature objects)
	 */
	public function setFeatures($features) {
		$all_features = Feature::getAll();

		foreach ($all_features as $id => $feature) {
			if (in_array($feature, $features)) {
				$feature->enableForAccount($this);
			} else {
				$feature->removeForAccount($this);
			}
		}
	}

	/**
	 * Populates charges array from database
	 *
	 * @param int $account_id Account ID
	 *
	 * @return array[] Array of charges (datetime, amount(
	 *
	 * @throws Exception
	 *
	 * @todo rework to just be part of or called from constructor
	 */
	private static function fillCharges($account_id) {

		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('SELECT date_time, amount FROM ' .
				UserConfig::$mysql_prefix . 'account_charge WHERE account_id = ? ' .
				'ORDER BY date_time'))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('i', $account_id)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		if (!$stmt->bind_result($datetime, $amount)) {
			throw new DBBindResultException($db, $stmt);
		}

		$charges = array();
		while ($stmt->fetch() === TRUE) {
			$charges[] = array('datetime' => $datetime, 'amount' => sprintf("%.2f", $amount));
		}

		$stmt->close();
		return $charges;
	}

	/**
	 * Charges the account or applies a refund
	 *
	 * @param int $refund Refund to apply or null if regular charge
	 *
	 * @return boolean True if charge was successful
	 *
	 * @throws DBException
	 */
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
					throw new DBPrepareStmtException($db);
				}

				if (!$stmt->bind_param('i', $this->id)) {
					throw new DBBindParamException($db, $stmt);
				}

				if (!$stmt->execute()) {
					throw new DBExecuteStmtException($db, $stmt);
				}

				$this->charges = array();
				$stmt->close();
			} else { // We still owe to user
				if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
						'account_charge SET amount = ? WHERE account_id = ?'))) {
					throw new DBPrepareStmtException($db);
				}

				$amt = $this->charges[$c]['amount'] - $charge_amount;
				if (!$stmt->bind_param('di', $amt, $this->id)) {
					throw new DBBindParamException($db, $stmt);
				}

				if (!$stmt->execute()) {
					throw new DBExecuteStmtException($db, $stmt);
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
				throw new DBPrepareStmtException($db);
			}

			if (!$stmt->bind_param('isd', $this->id, $charge['datetime'], $charge['amount'])) {
				throw new DBBindParamException($db, $stmt);
			}

			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
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

	/**
	 * This method is called when payment is recieved and should be applied to account
	 *
	 * @param int $amount Amount recieved
	 *
	 * @return boolean True if payment was successfully applied
	 *
	 * @throws DBException
	 */
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
					throw new DBPrepareStmtException($db);
				}

				if (!$stmt->bind_param('dis', $this->charges[$k]['amount'], $this->id, $this->charges[$k]['datetime'])) {
					throw new DBBindParamException($db, $stmt);
				}

				if (!$stmt->execute()) {
					throw new DBExecuteStmtException($db, $stmt);
				}

				$amount = 0;
				$stmt->close();
			}
		}

		foreach ($cleared as $n => $k) {

			if (!($stmt = $db->prepare('DELETE FROM ' . UserConfig::$mysql_prefix .
					'account_charge WHERE account_id = ? and date_time = ?'))) {
				throw new DBPrepareStmtException($db);
			}

			if (!$stmt->bind_param('is', $this->id, $k['datetime'])) {
				throw new DBBindParamException($db, $stmt);
			}

			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
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
				throw new DBPrepareStmtException($db);
			}

			if (!$stmt->bind_param('isd', $this->id, $charge['datetime'], $charge['amount'])) {
				throw new DBBindParamException($db, $stmt);
			}

			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
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

	/**
	 * Activates subscription plan for the account
	 *
	 * @param string $plan_slug Plan slug
	 * @param string $schedule_slug Payment schedule slug
	 * @param string $engine_slug Payment engine slug
	 *
	 * @return boolean True if activation was successful
	 *
	 * @throws DBException
	 */
	public function activatePlan($plan_slug, $schedule_slug = NULL, $engine_slug = NULL) {
		if (!$plan_slug) {
			$plan_slug = null;
		}

		$new_plan = Plan::getPlanBySlug($plan_slug);

		// if default plan slug is set, it means plan must always be assigned
		if (!is_null(UserConfig::$default_plan_slug) && (is_null($new_plan) || $new_plan === FALSE)) {
			return FALSE;
		}

		/**
		 * If subscriptions are not used, then just change plan, otherwise require a schedule and engine
		 */
		$db = UserConfig::getDB();

		if (!$new_plan || !UserConfig::$useSubscriptions) {
			if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
					'accounts SET plan_slug = ?, schedule_slug = NULL, engine_slug = NULL, active = 1, next_charge = NULL, ' .
					'next_plan_slug = NULL, next_schedule_slug = NULL, next_engine_slug = NULL WHERE id = ?'))) {
				throw new DBPrepareStmtException($db);
			}

			if (!$stmt->bind_param('si', $plan_slug, $this->id)) {
				throw new DBBindParamException($db, $stmt);
			}

			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$this->plan = $new_plan;
			$this->active = 1;
			$this->nextCharge = NULL;

			return TRUE;
		} else {
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

			$old_plan_slug = ($this->plan ? TRUE : FALSE) && $this->plan->getSlug();
			$old_schedule_slug = is_null($this->schedule) ? NULL : $this->schedule->slug;
			$old_engine_slug = is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug();

			if ($this->plan) {
				$this->plan->deactivate_hook($this->id, $plan_slug, $schedule_slug);
			}

			$this->plan = $new_plan;
			$this->schedule = $new_schedule;
			$this->plan->activate_hook($this->id, $old_plan_slug, $old_schedule_slug, $old_engine_slug);
			$this->active = 1;
			$this->nextCharge = is_null($this->schedule) ?
					NULL : date('Y-m-d H:i:s', time() + $this->schedule->charge_period * 86400);

			/*
			 * @TODO
			 * Update db There is a risk that this query fail. If so, object state
			 * will differ from db state. Should be addressed in further releases.
			 */
			if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
					'accounts SET plan_slug = ?, schedule_slug = ?, engine_slug = ?, active = 1, next_charge = ?, ' .
					'next_plan_slug = NULL, next_schedule_slug = NULL, next_engine_slug = NULL WHERE id = ?'))) {
				throw new DBPrepareStmtException($db);
			}

			if (!$stmt->bind_param('ssssi', $plan_slug, $schedule_slug, $engine_slug, $this->nextCharge, $this->id)) {
				throw new DBBindParamException($db, $stmt);
			}

			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}

			$this->paymentIsDue();
			$this->lastTransactionID =
					TransactionLogger::Log($this->id, $engine_slug, 0, 'Plan "' . $this->plan->getName() . '" activated');
			return TRUE;
		}
	}

	/**
	 * Deactivates subscription plan and downgrades to plan defined in "downgrade_to"
	 * or suspend the account
	 *
	 * @TODO Something is off here - downgrade_to property is not on account, but on Plan
	 *
	 * @return boolean True if account is still active and false if suspended
	 */
	public function deactivatePlan() {
		$this->plan->deactivate_hook($this->id, $this->downgrade_to, NULL);

		if (!is_null($this->downgrade_to)) {

			$this->activatePlan($this->downgrade_to);
			$this->lastTransactionID =
					TransactionLogger::Log($this->id, is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(), 0, 'Plan downgraded to "' . $this->plan->getName() . '"');
			return TRUE;
		} else {

			// Nothing to downgrade to - mark account as not active
			$this->suspend();
			$this->lastTransactionID =
					TransactionLogger::Log($this->id, is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(), 0, 'Account suspended due to plan "' . $this->plan->getName() . '" deactivation');
			return FALSE;
		}
	}

	/**
	 * Sets payment schedule for the account
	 *
	 * @param string $schedule_slug Payment schedule slug
	 * @param string $engine_slug Payment engine slug
	 *
	 * @return boolean True if successfully set the schedule
	 *
	 * @throws DBException
	 */
	public function setPaymentSchedule($schedule_slug, $engine_slug) {

		if (!($schedule = $this->plan->getPaymentScheduleBySlug($schedule_slug))) {
			return FALSE;
		}

		$this->schedule = $schedule;
		$this->nextCharge = date('Y-m-d H:i:s', time() + $this->schedule->charge_period * 86400);

		// Update db
		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix . 'accounts SET
			schedule_slug = ?, engine_slug = ?, next_charge = ?,
			next_plan_slug = NULL, next_schedule_slug = NULL, next_engine_slug = NULL
			WHERE id = ?'))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('sssi', $schedule_slug, $engine_slug, $this->nextCharge, $this->id)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		// Bill user
		$this->paymentIsDue();
		$this->lastTransactionID =
				TransactionLogger::Log($this->id, $engine_slug, 0, 'Payment schedule "' . $this->schedule->name . '" set.');
		return TRUE;
	}

	/**
	 * Returns payment schedule slug for the account
	 *
	 * @return string Payment schedule slug
	 */
	public function getScheduleSlug() {
		return $this->schedule ? $this->schedule->slug : NULL;
	}

	/**
	 * Return subscription plan slug
	 *
	 * @return string Subscription plan slug
	 */
	public function getPlanSlug() {
		if ($this->plan) {
			return $this->plan->getSlug();
		} else {
			return null;
		}
	}

	/**
	 * Returns payment engine object used by this account
	 *
	 * @return PaymentEngine Payment engine object
	 */
	public function getPaymentEngine() {
		return $this->paymentEngine;
	}

	/**
	 * Returns next payment engine object to be used when current schedule ends
	 *
	 * @return PaymentEngine Payment engine object
	 */
	public function getNextPaymentEngine() {
		return $this->nextPaymentEngine;
	}

	/**
	 * Returns true if account is supposed to have only one user
	 *
	 * @return boolean True if account is individual
	 */
	public function isIndividual() {
		$plan = $this->getPlan(); // can be FALSE
		// in case for whatever reason, we don't have a plan assigned to account
		if (!$plan) {
			$plan = Plan::getPlanBySlug(UserConfig::$default_plan_slug);
		}

		$capabilities = $plan->getCapabilities();
		if ($plan && array_key_exists('individual', $capabilities)) {
			return $capabilities['individual'] ? true : false;
		}

		// if nothing worked, default to multi-user accounts
		return false;
	}

	/**
	 * Sets payment engine for the account
	 *
	 * @param string $engine_slug Payment engine slug
	 *
	 * @return boolean True if successfully updated payment engine
	 *
	 * @throws DBException
	 */
	public function setPaymentEngine($engine_slug) {
		if ($engine_slug == NULL) {
			return FALSE;
		}

		$this->paymentEngine = StartupAPIModule::get($engine_slug);

		// Update db
		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
				'accounts SET engine_slug = ? WHERE id = ?'))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('si', $engine_slug, $this->id)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		$this->lastTransactionID =
				TransactionLogger::Log($this->id, is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(), 0, 'Payment engine "' . $this->paymentEngine->getSlug() . '" set.');
		return TRUE;
	}

	/**
	 * Returns account balance
	 *
	 * @return int Account balance
	 */
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

	/**
	 * Request a change of subscription plan and schedule to be applied
	 * at the end of current charge period
	 *
	 * @param string $plan_slug Slug of new plan
	 * @param string $schedule_slug Slug of new payment schedule
	 * @param string $engine_slug Slug of new payment engine
	 *
	 * @return boolean True if request was successful
	 *
	 * @throws DBException
	 */
	public function planChangeRequest($plan_slug, $schedule_slug, $engine_slug) {
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

		// make sure that charges are up to date
		$this->charges = self::fillCharges($this->getID());

		/* Check, if plan/schedule could be activated immediately
		  It could, if:
		  1. current plan has no schedule
		  2. Account balance is equal or greater than next schedule charge
		  or next plan also has no schedule
		 */

		if (is_null($this->nextCharge) && (is_null($new_schedule) ||
				$this->getBalance() >= $new_schedule->charge_amount)) {

			if (!is_null($this->paymentEngine)) {
				$this->paymentEngine->changeSubscription($this->id, $plan_slug, $schedule_slug);
			}

			return $this->activatePlan($plan_slug, $schedule_slug, $engine_slug);
		}

		// if no schedule specified and no default schedule found
		// and new plan has at least one shcedule, fail
		if (count($new_plan->getPaymentScheduleSlugs()) && is_null($new_schedule)) {
			return FALSE;
		}

		// If requested plan/schedule is same as current plan/schedule, cancel any pending request
		if ($this->plan->getSlug() == $plan_slug && ((!is_null($this->schedule) &&
				$this->schedule->slug == $schedule_slug) || (is_null($this->schedule) && is_null($schedule_slug)))) {
			return $this->cancelChangeRequest();
		}

		// Update db
		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
				'accounts SET next_plan_slug = ?, next_schedule_slug = ?, next_engine_slug = ? WHERE id = ?'))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('sssi', $plan_slug, $schedule_slug, $engine_slug, $this->id)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		$this->lastTransactionID =
				TransactionLogger::Log($this->id, $engine_slug, 0, 'Request to change plan to "' . $new_plan->getName() .
						(is_null($new_schedule) ? '"' : '" and schedule to "' . $new_schedule->name) . '" stored.');
		return TRUE;
	}

	/**
	 * Request a change of payment schedule to be applied at the end of current charge period
	 *
	 * @param string $schedule_slug New payment schedule slug
	 * @param string $engine_slug New payment engine slug
	 *
	 * @return boolean True if request was successful
	 *
	 * @throws DBException
	 */
	public function scheduleChangeRequest($schedule_slug, $engine_slug) {
		if (!($schedule = $this->plan->getPaymentScheduleBySlug($schedule_slug))
				|| !($engine = PaymentEngine::getEngineBySlug($engine_slug))
		) {
			return FALSE;
		}

		// Check, if schedule could be activated immediately
		if (is_null($this->nextCharge) &&
				$this->getBalance() >= $schedule->charge_amount) {
			if (!is_null($this->paymentEngine)) {
				$this->paymentEngine->changeSubscription($this->id, $this->plan, $schedule);
			}

			return $this->setPaymentSchedule($schedule_slug, $engine_slug);
		}

		// If requested schedule is same as current schedule, cancel any pending request
		if (!is_null($this->schedule) && $this->schedule->slug == $schedule_slug) {
			return $this->cancelChangeRequest();
		}

		// Update db
		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
				'accounts SET next_plan_slug = plan_slug, next_schedule_slug = ?,
					next_engine_slug = ? WHERE id = ?'))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('ssi', $schedule_slug, $engine_slug, $this->id)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		$this->lastTransactionID =
				TransactionLogger::Log($this->id, $engine_slug, 0, 'Request to change schedule to "' . $schedule->name . '" stored.');
		return TRUE;
	}

	/**
	 * Suspends the account
	 *
	 * @return boolean True if suspension was successful
	 *
	 * @throws DBException
	 */
	public function suspend() {
		$this->active = 0;

		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
				'accounts SET active = 0 WHERE id = ?'))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('i', $this->id)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		return TRUE;
	}

	/**
	 * Activates the account
	 *
	 * @return boolean True if activation was successful
	 *
	 * @throws DBException
	 */
	public function activate() {
		$this->active = 1;

		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix .
				'accounts SET active = 1 WHERE id = ?'))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('i', $this->id)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		return TRUE;
	}

	/**
	 * Returns last transaction ID
	 *
	 * @return int ID of last transaction
	 */
	public function getLastTransactionID() {
		return $this->lastTransactionID;
	}

	/**
	 * Cancels change request for new subscription plan and/or payment schedule
	 *
	 * @return boolean True if cancellation was successful
	 *
	 * @throws DBException
	 */
	public function cancelChangeRequest() {

		// Cancel any pending request to change plan and/or schedule.

		$db = UserConfig::getDB();

		if (!($stmt = $db->prepare('UPDATE ' . UserConfig::$mysql_prefix . 'accounts SET ' .
				'next_plan_slug = NULL, next_schedule_slug = NULL, next_engine_slug = NULL ' .
				'WHERE id = ?'))) {
			throw new DBPrepareStmtException($db);
		}

		if (!$stmt->bind_param('i', $this->id)) {
			throw new DBBindParamException($db, $stmt);
		}

		if (!$stmt->execute()) {
			throw new DBExecuteStmtException($db, $stmt);
		}

		$this->nextPlan = NULL;
		$this->nextSchedule = NULL;

		$this->lastTransactionID =
				TransactionLogger::Log($this->id, is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(), 0, 'Plan/schedule change request cancelled.');

		return TRUE;
	}

}
