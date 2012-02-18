<?php
require_once(dirname(__FILE__).'/Plan.php');
class Account
{
	private $id;
	private $name;
	private $role;
	private $plan;
	private $schedule;
	private $charges;
	
	private $paymentEngine;

	private $isIndividual;
	private $isActive;

	const ROLE_USER = 0;
	const ROLE_ADMIN = 1;

	/**
	* Gets Account by ID
	*/
	public static function getByID($id)
	{
		$db = UserConfig::getDB();
		$account = null;

		if ($stmt = $db->prepare('SELECT name, plan, schedule, engine, active FROM '.UserConfig::$mysql_prefix.'accounts WHERE id = ?'))
		{
			if (!$stmt->bind_param('i', $id))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}
			
			if (!$stmt->store_result())
			  throw new Exception("Can't store result: ".$stmt->error);
			
			if (!$stmt->bind_result($name, $plan_id, $schedule_id, $engine_id, $active))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}
			
			if ($stmt->fetch() === TRUE)
			{
				$charges = self::fillCharges($id);
				$account = new self($id, $name, $plan_id, Account::ROLE_USER, $engine_id, $schedule_id, $charges, $active);
			}

			$stmt->close();
		}
		else
		{
			throw new Exception("Can't prepare statement: ".$db->error);
		}

		return $account;
	}

	public static function getUserAccounts($user)
	{
		$db = UserConfig::getDB();
		$accounts = array();
		$userid = $user->getID();

		if ($stmt = $db->prepare('SELECT a.id, a.name, a.plan, a.schedule, a.engine, a.active, au.role  FROM '.UserConfig::$mysql_prefix.'accounts a INNER JOIN '.UserConfig::$mysql_prefix.'account_users au ON a.id = au.account_id WHERE au.user_id = ?'))
		{
			if (!$stmt->bind_param('i', $userid))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
			if (!$stmt->execute())
			{
				throw new Exception("Can't execute statement: ".$stmt->error);
			}

			if (!$stmt->store_result())
			  throw new Exception("Can't store result: ".$stmt->error);

			if (!$stmt->bind_result($id, $name, $plan_id, $schedule_id, $engine_id, $active, $role))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}
			  
			while($stmt->fetch() === TRUE)
			{
				$charges = self::fillCharges($id);
				$accounts[] = new self($id, $name, $plan_id, $role,	$schedule_id, $engine_id, $charges, $active);
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


	private function __construct($id, $name, $plan, $role, $schedule = NULL, $engine = NULL, $charges = NULL, $active = TRUE)
	{
		$this->id = $id;
		$this->name = $name;
		$this->plan = Plan::getPlan($plan);
		if(is_null($this->plan))
		  $this->plan = Plan::getPlan(UserConfig::$default_plan);
		$this->schedule = is_null($schedule) ? NULL : $this->plan->getPaymentSchedule($schedule);
		$this->role = $role;
		$this->isActive = $active;
		if($engine !== NULL) {
			UserConfig::loadModule($engine);
			$this->paymentEngine = new $engine;
		}
		
		$this->charges = is_null($charges) ? array() : $charges;
	}

	public function getID()
	{
		return $this->id;
	}
	public function getName()
	{
		if ($this->isIndividual)
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

	public function getSchedule() {
		return $this->schedule;
	}

	public function getUserRole()
	{
		return $this->role;
	}
	
	public function isActive() {
	  return $this->isActive;
  }
	
  public function getCharges() {
    return $this->charges;
  }

	public static function createAccount($name, $plan, $schedule = null, $user = null, $role = Account::ROLE_USER, $engine = null)
	{
		$name = mb_convert_encoding($name, 'UTF-8');

		$db = UserConfig::getDB();

		if ($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'accounts (name, plan, schedule, engine) VALUES (?, ?, ?, ?)'))
		{
			if (!$stmt->bind_param('ssss', $name, $plan, $schedule, $engine))
			{
				 throw new Exception("Can't bind parameter".$stmt->error);
			}
//                                            var_dump($stmt->bind_result());die();

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

		if ($stmt = $db->prepare('SELECT a.id, a.name, a.plan, a.schedule, a.engine, a.active, au.role FROM '.UserConfig::$mysql_prefix.'user_preferences up INNER JOIN '.UserConfig::$mysql_prefix.'accounts a ON a.id = up.current_account_id INNER JOIN '.UserConfig::$mysql_prefix.'account_users au ON a.id = au.account_id WHERE up.user_id = ? AND au.user_id = ?'))
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
			if (!$stmt->bind_result($id, $name, $plan_id, $schedule_id, $engine, $active, $role))
			{
				throw new Exception("Can't bind result: ".$stmt->error);
			}
			$stmt->fetch();
			$stmt->close();

			if ($id)
			{
				$charges = self::fillCharges($id);
				return new self($id, $name, $plan_id, $role, $schedule_id, $engine, $charges, $active);
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
	
	private static function fillCharges($account_id) {

		$db = UserConfig::getDB();

		if(!($stmt = $db->prepare('SELECT date_time, amount FROM '.UserConfig::$mysql_prefix.'account_charge WHERE account_id = ? ORDER BY date_time')))
		throw new Exception("Can't prepare statement: ".$db->error);
		
		if (!$stmt->bind_param('i', $account_id))
			throw new Exception("Can't bind parameter".$stmt->error);
		
		if (!$stmt->execute())
			throw new Exception("Can't execute statement: ".$stmt->error);
		
		if (!$stmt->bind_result($datetime, $amount))
			throw new Exception("Can't bind result: ".$stmt->error);
	
		$charges = array();	
		while($stmt->fetch() === TRUE)
			$charges[] = array('datetime' => $datetime, 'amount' => $amount);
		
		$stmt->close();
		return $charges;
	}
	
	public function paymentIsDue() {
	
		if(is_null($this->schedule)) return;
		$charge = array('datetime' => date('Y-m-d H:i:s'), 'amount' => $this->schedule->charge_amount);
		$this->charges[] = $charge;
		
		$db = UserConfig::getDB();

		if(!($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.'account_charge (account_id, date_time, amount) VALUES (?, ?, ?)')))
			throw new Exception("Can't prepare statement: ".$db->error);
		
		if (!$stmt->bind_param('isd', $this->id, $charge['datetime'], $charge['amount']))
			throw new Exception("Can't bind parameter".$stmt->error);
		
		if (!$stmt->execute())
			throw new Exception("Can't execute statement: ".$stmt->error);

		$stmt->close();
		
		return TRUE;
	}
	
	public function paymentReceived($amount) {
	
		$cleared = array();
		$db = UserConfig::getDB();

		foreach(array_reverse(array_keys($this->charges)) as $n => $k) {

			if($amount <= 0) break;
			if($this->charges[$k]['amount'] <= $amount) {
				$amount -= $this->charges[$k]['amount'];
				$cleared[] = $this->charges[$k];
				unset($this->charges[$k]); 
			} else {
				$this->charges[$k]['amount'] -= $amount;
				
				if(!($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'account_charge SET amount = ? WHERE account_id = ? and date_time = ?')))
					throw new Exception("Can't prepare statement: ".$db->error);
					
				if (!$stmt->bind_param('dis', $this->charges[$k]['amount'], $this->id, $this->charges[$k]['datetime']))
					throw new Exception("Can't bind parameter".$stmt->error);
					
				if (!$stmt->execute())
					throw new Exception("Can't execute statement: ".$stmt->error);   

				$amount = 0;
			}
		}
		
		foreach($cleared as $n => $k) {

			if(!($stmt = $db->prepare('DELETE FROM '.UserConfig::$mysql_prefix.'account_charge WHERE account_id = ? and date_time = ?')))
				throw new Exception("Can't prepare statement: ".$db->error);
				
			if (!$stmt->bind_param('is', $this->id, $k['datetime']))
				throw new Exception("Can't bind parameter".$stmt->error);
				
			if (!$stmt->execute())
				throw new Exception("Can't execute statement: ".$stmt->error);
		}
		
		return TRUE;
	}
	
	public function activatePlan($plan_id, $schedule_id = NULL) {

		$new_plan = Plan::getPlan($plan_id);
		if(is_null($new_plan) || $new_plan === FALSE) return FALSE;	
		$new_schedule = $new_plan->getPaymentSchedule($schedule_id);

		$old_plan = $this->plan->id;
		$old_schedule = $this->schedule ? $this->schedule->id : NULL;
		$this->plan->deactivate_hook($plan_id, $schedule_id);
		$this->plan = $new_plan;
		$this->schedule = $new_schedule;
		if($this->paymentEngine)
			$this->paymentEngine->ChangeSubscription($plan_id, $schedule_id, $old_plan, $old_schedule);
		$this->plan->activate_hook($old_plan,$old_schedule);
		$this->isActive = 1;
		
		# Update db
		# There is a risk that this query fail. If so, object state will differ from db state.
		# Should be addressed in further releases
		
		$db = UserConfig::getDB();
		
		if (!($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'accounts SET plan = ?, schedule = ?, active = 1 WHERE id = ?')))
		  throw new Exception("Can't prepare statement: ".$db->error);
		  
    if (!$stmt->bind_param('ssi', $plan_id, $schedule_id, $this->id))
      throw new Exception("Can't bind parameter".$stmt->error);
      
    if (!$stmt->execute())
      throw new Exception("Can't execute statement: ".$stmt->error);
      
    return TRUE;
	}
	
	public function deactivatePlan() {

		$db = UserConfig::getDB();	

		$this->plan->deactivate_hook($this->downgrade_to);

		if($this->downgrade_to !== NULL) {
			$this->plan = Plan::getPlan($this->downgrade_to);
			$this->schedule = $this->plan->getDefaultPaymentSchedule();
			$this->isActive = TRUE;
			
			# Update db
			if (!($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'accounts SET plan = ?, schedule = ?, active = 1 WHERE id = ?')))
			  throw new Exception("Can't prepare statement: ".$db->error);
			  
      if (!$stmt->bind_param('ssi', $this->plan->id, is_null($this->schedule) ? NULL : $this->schedule->id, $this->id))
        throw new Exception("Can't bind parameters: ".$stmt->error);
        
      if (!$stmt->execute())
        throw new Exception("Can't execute statement: ".$stmt->error);
        
			return TRUE;

		} else {
		
		  # Nothing to downgrade to - mark account as not active
		  $this->isActive = FALSE;
		  
		  if (!($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'accounts SET active = 0 WHERE id = ?')))
		    throw new Exception("Can't prepare statement: ".$db->error);
		    
      if (!$stmt->bind_param('i', $this->id))
        throw new Exception("Can't bind parameters: ".$stmt->error);
        
      if (!$stmt->execute())
        throw new Exception("Can't execute statement: ".$stmt->error);
        
  		return FALSE;
    }
	}
	
	public function setPaymentSchedule($schedule_id) {
	
		if(!($schedule = $this->plan->getPaymentSchedule($schedule_id)))
			return FALSE;
			
		$this->paymentEngine->ChangeSubscription($schedule);
		$this->schedule = $schedule;
		
    # Update db
		$db = UserConfig::getDB();

    if (!($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'accounts SET schedule = ? WHERE id = ?')))
      throw new Exception("Can't prepare statement: ".$db->error);
      
    if (!$stmt->bind_param('si', $schedule_id, $this->id))
      throw new Exception("Can't bind parameters: ".$stmt->error);
      
    if (!$stmt->execute())
      throw new Exception("Can't execute statement: ".$stmt->error);

    return TRUE;
	}
	
	public function getScheduleID() {
	
		return $this->schedule ? $this->schedule->id : NULL;
	}
	
	public function getPlanID() {
	
		return $this->plan->id;
	}
	
	public function getPaymentEngine() {
	
		return $this->paymentEngine; # do we need class or just id?
	}
	
	public function setPaymentEngine($engine) {
	
    if($engine == NULL)
      return FALSE;

    UserConfig::loadModule($engine);
    $this->paymentEngine = new $engine;
    
    # Update db
		$db = UserConfig::getDB();

    if (!($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'accounts SET engine = ? WHERE id = ?')))
      throw new Exception("Can't prepare statement: ".$db->error);
      
    if (!$stmt->bind_param('si', $engine, $this->id))
      throw new Exception("Can't bind parameters: ".$stmt->error);
      
    if (!$stmt->execute())
      throw new Exception("Can't execute statement: ".$stmt->error);
      
    return TRUE;
	}

}
