<?php
/**
 * @package StartupAPI
 */
require_once(dirname(__FILE__).'/Plan.php');

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
 */
class Account
{
	/**
	 * @var int Account ID
	 */
	private $id;

	/**
	 * @var string Account name
	 */
	private $name;

	/**
	 * @var int Current user's role
	 */
	private $role;

	/**
	 * @var Plan Subscription plan
	 */
	private $plan;

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
	public static function getByID($id)
	{
		$db = UserConfig::getDB();
		$account = null;

		if ($stmt = $db->prepare('SELECT name, plan FROM '.UserConfig::$mysql_prefix.'accounts WHERE id = ?'))
		{
			if (!$stmt->bind_param('i', $id))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($name, $plan_id))
			{
				throw new DBBindResultException($db, $stmt);
			}

			if ($stmt->fetch() === TRUE)
			{
				$account = new self($id, $name, Plan::getByID($plan_id), Account::ROLE_USER);
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

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
	public static function getUserAccounts(User $user)
	{
		$db = UserConfig::getDB();
		$accounts = array();
		$userid = $user->getID();

		if ($stmt = $db->prepare('SELECT a.id, a.name, a.plan, au.role FROM '.UserConfig::$mysql_prefix.'accounts a INNER JOIN '.UserConfig::$mysql_prefix.'account_users au ON a.id = au.account_id WHERE au.user_id = ?'))
		{
			if (!$stmt->bind_param('i', $userid))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($id, $name, $plan_id, $role))
			{
				throw new DBBindResultException($db, $stmt);
			}

			while($stmt->fetch() === TRUE)
			{
				$accounts[] = new self($id, $name, Plan::getByID($plan_id), $role);
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		if (count($accounts) == 0)
		{
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
	public static function getTotalAccounts()
	{
		$db = UserConfig::getDB();

		$total = 0;

		if ($stmt = $db->prepare('SELECT COUNT(*) FROM '.UserConfig::$mysql_prefix.'accounts'))
		{
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($total))
			{
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
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
	private function __construct($id, $name, Plan $plan, $role)
	{
		$this->id = $id;
		$this->name = $name;
		$this->plan = $plan;
		$this->role = $role;
	}

	/**
	 * Returns account ID
	 *
	 * @return int Account ID
	 */
	public function getID()
	{
		return $this->id;
	}

	/**
	 * Returns Accoutn name
	 *
	 * @return string Account Name
	 */
	public function getName()
	{
		if ($this->plan->isIndividual())
		{
			$users = $this->getUsers();
			return $users[0]->getName();
		}
		else
		{
			return $this->name;
		}
	}

	/**
	 * Returns a list of account users
	 *
	 * @return array Array of User objects
	 *
	 * @throws DBException
	 */
	public function getUsers()
	{
		$db = UserConfig::getDB();
		$userids = array();

		if ($stmt = $db->prepare('SELECT user_id FROM '.UserConfig::$mysql_prefix.'account_users WHERE account_id = ?'))
		{
			if (!$stmt->bind_param('i', $this->id))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($userid))
			{
				throw new DBBindResultException($db, $stmt);
			}

			while($stmt->fetch() === TRUE)
			{
				$userids[] = $userid;
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		$users = User::getUsersByIDs($userids);

		return $users;
	}

	/**
	 * Returns account's subscription plan
	 *
	 * @return Plan Subscription Plan
	 */
	public function getPlan()
	{
		return $this->plan;
	}

	/**
	 * Return current user's role in the account
	 *
	 * @return int User's role (either Account::ROLE_USER or Account::ROLE_ADMIN)
	 */
	public function getUserRole()
	{
		return $this->role;
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
	public static function createAccount($name, $plan, $user = NULL, $role = Account::ROLE_USER)
	{
		$name = mb_convert_encoding($name, 'UTF-8');

		$db = UserConfig::getDB();
		$plan_id = $plan->getID();

		if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'accounts (name, plan) VALUES (?, ?)'))
		{
			if (!$stmt->bind_param('si', $name, $plan_id))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			$id = $stmt->insert_id;

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		if ($user !== null)
		{
			$userid = $user->getID();

			if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'account_users (account_id, user_id, role) VALUES (?, ?, ?)'))
			{
				if (!$stmt->bind_param('iii', $id, $userid, $role))
				{
					throw new DBBindParamException($db, $stmt);
				}
				if (!$stmt->execute())
				{
					throw new DBExecuteStmtException($db, $stmt);
				}

				$stmt->close();
			}
			else
			{
				throw new DBPrepareStmtException($db);
			}
		}

		return new self($id, $name, $plan, $role);
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
	public static function getCurrentAccount($user)
	{
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('SELECT a.id, a.name, a.plan, au.role FROM '.UserConfig::$mysql_prefix.'user_preferences up INNER JOIN '.UserConfig::$mysql_prefix.'accounts a ON a.id = up.current_account_id INNER JOIN '.UserConfig::$mysql_prefix.'account_users au ON a.id = au.account_id WHERE up.user_id = ? AND au.user_id = ?'))
		{
			$id = null;

			if (!$stmt->bind_param('ii', $userid, $userid))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($id, $name, $plan_id, $role))
			{
				throw new DBBindResultException($db, $stmt);
			}
			$stmt->fetch();
			$stmt->close();

			if ($id)
			{
				return new self($id, $name, Plan::getByID($plan_id), $role);
			}
			else
			{
				$user_accounts = self::getUserAccounts($user);

				if (count($user_accounts) > 0)
				{
					$user_accounts[0]->setAsCurrent($user);
					return $user_accounts[0];
				}
			}

			throw new StartupAPIException("No accounts are set for the user");
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return $current_account;
	}

	/**
	 * Setts this account as current for the user
	 *
	 * @param User $user
	 *
	 * @throws DBException
	 */

	public function setAsCurrent($user)
	{
		$db = UserConfig::getDB();

		$accounts = self::getUserAccounts($user);

		$valid_account = false;
		foreach ($accounts as $account)
		{
			if ($this->isTheSameAs($account))
			{
				$valid_account = true;
				break;
			}
		}

		if (!$valid_account)
		{
			return; // silently ignore if user is not connected to this account
		}

		if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'user_preferences SET current_account_id = ? WHERE user_id = ?'))
		{
			$userid = $user->getID();

			if (!$stmt->bind_param('ii', $this->id, $userid))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt, "Can't update user preferences (set current account)");
			}
			$stmt->close();
		}
		else
		{
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
	public function isTheSameAs($account)
	{
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
}
