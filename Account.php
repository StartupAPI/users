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
	private $id;
	private $name;
	private $role;
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
	 * @trows Exception
	 */
	public static function getByID($id)
	{
		$db = UserConfig::getDB();
		$account = null;

		if ($stmt = $db->prepare('SELECT name, plan FROM '.UserConfig::$mysql_prefix.'accounts WHERE id = ?'))
		{
			if (!$stmt->bind_param('i', $id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($name, $plan_id))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			if ($stmt->fetch() === TRUE)
			{
				$account = new self($id, $name, Plan::getByID($plan_id), Account::ROLE_USER);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $account;
	}

	/**
	 * Gets all accounts associated with the user
	 *
	 * @param User $user User we need accounts for
	 * @return array Array of user accounts
	 * @throws Exception
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
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($id, $name, $plan_id, $role))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$accounts[] = new self($id, $name, Plan::getByID($plan_id), $role);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		if (count($accounts) == 0)
		{
			// there must be at least one personal account for each user
			throw new Exception("No accounts are set for the user");
		}

		return $accounts;
	}

	/*
	 * Returns total number of accounts in the system
	 */
	public static function getTotalAccounts()
	{
		$db = UserConfig::getDB();

		$total = 0;

		if ($stmt = $db->prepare('SELECT COUNT(*) FROM '.UserConfig::$mysql_prefix.'accounts'))
		{
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($total))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $total;
	}



	private function __construct($id, $name, $plan, $role)
	{
		$this->id = $id;
		$this->name = $name;
		$this->plan = $plan;
		$this->role = $role;
	}

	public function getID()
	{
		return $this->id;
	}
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
	public function getUsers()
	{
		$db = UserConfig::getDB();
		$userids = array();

		if ($stmt = $db->prepare('SELECT user_id FROM '.UserConfig::$mysql_prefix.'account_users WHERE account_id = ?'))
		{
			if (!$stmt->bind_param('i', $this->id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($userid))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}

			while($stmt->fetch() === TRUE)
			{
				$userids[] = $userid;
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		$users = User::getUsersByIDs($userids);

		return $users;
	}
	public function getPlan()
	{
		return $this->plan;
	}
	public function getUserRole()
	{
		return $this->role;
	}

	public static function createAccount($name, $plan, $user = null, $role = Account::ROLE_USER)
	{
		$name = mb_convert_encoding($name, 'UTF-8');

		$db = UserConfig::getDB();
		$plan_id = $plan->getID();

		if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'accounts (name, plan) VALUES (?, ?)'))
		{
			if (!$stmt->bind_param('si', $name, $plan_id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			$id = $stmt->insert_id;

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		if ($user !== null)
		{
			$userid = $user->getID();

			if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'account_users (account_id, user_id, role) VALUES (?, ?, ?)'))
			{
				if (!$stmt->bind_param('iii', $id, $userid, $role))
				{
					 throw new Exception("Can't bind parameter".$stmt->error);
				}
				if (!$stmt->execute())
				{
					throw new Exception("Can't execute statement: ".$stmt->error);
				}

				$stmt->close();
			}
			else
			{
				throw new Exception("Can't prepare statement: ".$db->error);
			}
		}

		return new self($id, $name, $plan, $role);
	}

	public static function getCurrentAccount($user)
	{
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('SELECT a.id, a.name, a.plan, au.role FROM '.UserConfig::$mysql_prefix.'user_preferences up INNER JOIN '.UserConfig::$mysql_prefix.'accounts a ON a.id = up.current_account_id INNER JOIN '.UserConfig::$mysql_prefix.'account_users au ON a.id = au.account_id WHERE up.user_id = ? AND au.user_id = ?'))
		{
			$id = null;

			if (!$stmt->bind_param('ii', $userid, $userid))
			{
				throw new Exception("Can't bind parameter: ".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			if (!$stmt->bind_result($id, $name, $plan_id, $role))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
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

			throw new Exception("No accounts are set for the user");
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $current_account;
	}

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
				throw new Exception("Can't bind parameter");
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't update user preferences (set current account)");
			}
			$stmt->close();
		}
		else
		{
			throw new Exception("Can't update user preferences (set current account)");
		}
	}

	public function isTheSameAs($account)
	{
		if (is_null($account)) {
			return false;
		}

		return $this->getID() == $account->getID();
	}

	/*
	 * Returns true if account has requested feature enabled
	 */
	public function hasFeature($feature) {
		// checking if we got feature ID instead of object for backwards compatibility
		if (is_int($feature)) {
			$feature = Feature::getByID($feature);
		}

		return $feature->isEnabledForAccount($this);
	}
}
