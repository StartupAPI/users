<?php

class Account
{
	private $slug;
	private $name;
	private $role;
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

	const ROLE_USER = 0;
	const ROLE_ADMIN = 1;

	/**
	* Gets Account by ID
	*/
	public static function getByID($id)
	{
		$db = UserConfig::getDB();
		$account = null;

    if (!($stmt = $db->prepare('SELECT name, plan_slug, schedule_slug, engine_slug, '.
        'active, next_charge, next_plan_slug, next_schedule_slug FROM '.
	  	  UserConfig::$mysql_prefix.'accounts WHERE id = ?')))
    {
      throw new Exception("Can't prepare statement: ".$db->error);
    }

    if (!$stmt->bind_param('i', $id)) {
       throw new Exception("Can't bind parameter".$stmt->error);
    }

    if (!$stmt->execute()) {
      throw new Exception("Can't execute statement: ".$stmt->error);
    }
    
    if (!$stmt->store_result()) {
      throw new Exception("Can't store result: ".$stmt->error);
    }
    
    if (!$stmt->bind_result($name, $plan_slug, $schedule_slug, $engine_slug, 
        $active, $next_charge, $next_plan_slug, $next_schedule_slug))
    {
      throw new Exception("Can't bind result: ".$stmt->error);
    }
    
    if ($stmt->fetch() === TRUE) {
      $charges = self::fillCharges($id);
      $account = new 
        self($id, $name, $plan_slug, Account::ROLE_USER, $schedule_slug, $engine_slug,
             $charges, $active, $next_charge, $next_plan_slug, $next_schedule_slug);
    }

    $stmt->close();

		return $account;
	}

	public static function getUserAccounts($user)
	{
		$db = UserConfig::getDB();
		$accounts = array();
		$userid = $user->getID();

		if (!($stmt = $db->prepare(
        'SELECT a.id, a.name, a.plan_slug, a.schedule_slug, a.engine_slug, a.active, '.
        'a.next_charge, a.next_plan_slug, a.next_schedule_slug, au.role  FROM '.
        UserConfig::$mysql_prefix.'accounts a INNER JOIN '.
        UserConfig::$mysql_prefix.'account_users au ON a.id = au.account_id '.
        'WHERE au.user_id = ?')))
		{
			throw new Exception("Can't prepare statement: ".$db->error);
    }

    if (!$stmt->bind_param('i', $userid))	{
       throw new Exception("Can't bind parameter".$stmt->error);
    }

    if (!$stmt->execute()) {
      throw new Exception("Can't execute statement: ".$stmt->error);
    }

    if (!$stmt->store_result()) {
      throw new Exception("Can't store result: ".$stmt->error);
    }

    if (!$stmt->bind_result($id, $name, $plan_slug, $schedule_slug, $engine_slug,
        $active, $next_charge, $role, $next_plan_slug, $next_schedule_slug))
    {
      throw new Exception("Can't bind result: ".$stmt->error);
    }
      
    while($stmt->fetch() === TRUE) {
      $charges = self::fillCharges($id);
      $accounts[] = new self($id, $name, $plan_slug, $role,	$schedule_slug, $engine_slug,
                  $charges, $active, $next_charge, $next_plan_slug, $next_schedule_slug);
    }

    $stmt->close();

		if (count($accounts) == 0) {
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



  private function __construct($id, $name, $plan_slug, $role, $schedule_slug = NULL,
    $engine_slug = NULL, $charges = NULL, $active = TRUE, $next_charge = NULL, 
    $next_plan_slug = NULL, $next_schedule_slug = NULL)
	{
		$this->id = $id;
		$this->name = $name;
		$this->plan = is_null($plan_slug) ? NULL : Plan::getPlanBySlug($plan_slug);
		if (is_null($this->plan)) {
		  $this->plan = Plan::getPlanBySlug(UserConfig::$default_plan_slug);
    }
    $this->schedule = is_null($schedule_slug) || is_null($this->plan) ? 
      NULL : $this->plan->getPaymentScheduleBySlug($schedule_slug);
		$this->nextCharge = is_null($this->schedule) ? NULL : $next_charge;
		$this->role = $role;
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

	public function getID()
	{
		return $this->id;
	}

	public function getName()
	{
		if ($this->isIndividual) {
			$users = $this->getUsers();
			return $users[0]->getName();
		}
		else {
			return $this->name;
		}
	}

	public function getUsers()
	{
		$db = UserConfig::getDB();
		$userids = array();

    if (!($stmt = $db->prepare('SELECT user_id FROM '.UserConfig::$mysql_prefix.
      'account_users WHERE account_id = ?')))
		{
			throw new Exception("Can't prepare statement: ".$db->error);
    }

    if (!$stmt->bind_param('i', $this->id))	{
       throw new Exception("Can't bind parameter".$stmt->error);
    }

    if (!$stmt->execute()) {
      throw new Exception("Can't execute statement: ".$stmt->error);
    }
    
    if (!$stmt->bind_result($userid))	{
      throw new Exception("Can't bind result: ".$stmt->error);
    }

    while($stmt->fetch() === TRUE) {
      $userids[] = $userid;
    }

    $stmt->close();

		$users = User::getUsersByIDs($userids);

		return $users;
	}

	public function getPlan()
	{
		return $this->plan;
	}

	public function getSchedule() 
	{
		return $this->schedule;
	}

	public function getUserRole()
	{
		return $this->role;
	}
	
	public function isActive() 
	{
	  return $this->active;
  }
	
  public function getCharges() 
  {
    return $this->charges;
  }
  
  public function getNextCharge() 
  {
    return $this->nextCharge;
  }
  
  public function getNextPlan() 
  {
    return $this->nextPlan;
  }
  
  public function getNextSchedule() 
  {
    return $this->nextSchedule;
  }

  public static function createAccount($name, $plan_slug, $schedule_slug = null, 
    $user = null, $role = Account::ROLE_USER, $engine_slug = null)
	{
		$name = mb_convert_encoding($name, 'UTF-8');

		$db = UserConfig::getDB();

    if (!($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.
      'accounts (name, plan_slug, schedule_slug, engine_slug) VALUES (?, ?, ?, ?)')))
		{
			throw new Exception("Can't prepare statement: ".$db->error);
    }


    if (!$stmt->bind_param('ssss', $name, $plan_slug, $schedule_slug, $engine_slug)) {
       throw new Exception("Can't bind parameter".$stmt->error);
    }

    if (!$stmt->execute()) {
      throw new Exception("Can't execute statement: ".$stmt->error);
    }

    $id = $stmt->insert_id;

    $stmt->close();

		if ($user !== null)	{
			$userid = $user->getID();

      if (!($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.
        'account_users (account_id, user_id, role) VALUES (?, ?, ?)')))
			{
				throw new Exception("Can't prepare statement: ".$db->error);
      }

      if (!$stmt->bind_param('iii', $id, $userid, $role)) {
         throw new Exception("Can't bind parameter".$stmt->error);
      }

      if (!$stmt->execute()) {
        throw new Exception("Can't execute statement: ".$stmt->error);
      }

      $stmt->close();
		}
		
		$account = new self($id, $name, $plan_slug, $role, NULL, $engine_slug);
		$account->activatePlan($plan_slug, $schedule_slug);
		$this->lastTransactionID = 
		  TransactionLogger::Log($id,is_null($engine_slug) ? NULL : $account->paymentEngine->getSlug(),0,'Account created');
		return $account;
	}

	public static function getCurrentAccount($user)
	{
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if (!($stmt = $db->prepare(
      'SELECT a.id, a.name, a.plan_slug, a.schedule_slug, a.engine_slug, a.active, '.
      'a.next_charge, a.next_plan_slug, a.next_schedule_slug, au.role FROM '.
		  UserConfig::$mysql_prefix.'user_preferences up INNER JOIN '.
		  UserConfig::$mysql_prefix.'accounts a ON a.id = up.current_account_id INNER JOIN '.
      UserConfig::$mysql_prefix.'account_users au ON a.id = au.account_id '.
      'WHERE up.user_id = ? AND au.user_id = ?')))
		{
			throw new Exception("Can't prepare statement: ".$db->error);
    }

    $id = null;

    if (!$stmt->bind_param('ii', $userid, $userid)) {
      throw new Exception("Can't bind parameter: ".$stmt->error);
    }

    if (!$stmt->execute()) {
      throw new Exception("Can't execute statement: ".$stmt->error);
    }

    if (!$stmt->bind_result($id, $name, $plan_slug, $schedule_slug, $engine_slug, 
      $active, $next_charge, $next_plan_slug, $next_schedule_slug, $role))
    {
      throw new Exception("Can't bind result: ".$stmt->error);
    }

    $stmt->fetch();
    $stmt->close();

    if ($id) {
      $charges = self::fillCharges($id);
      return new self($id, $name, $plan_slug, $role, $schedule_slug, $engine_slug, 
                      $charges, $active, $next_charge, 
                      $next_plan_slug, $next_schedule_slug);
    }
    else {
      $user_accounts = self::getUserAccounts($user);

      if (count($user_accounts) > 0) {
        $user_accounts[0]->setAsCurrent($user);
        return $user_accounts[0];
      }
    }

    throw new Exception("No accounts are set for the user");
	}

	public function setAsCurrent($user)
	{
		$db = UserConfig::getDB();

		$accounts = self::getUserAccounts($user);

		$valid_account = false;
		foreach ($accounts as $account) {
			if ($this->isTheSameAs($account))	{
				$valid_account = true;
				break;
			}
		}

		if (!$valid_account) {
			return; // silently ignore if user is not connected to this account
		}

    if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.
      'user_preferences SET current_account_id = ? WHERE user_id = ?'))
		{
			$userid = $user->getID();

			if (!$stmt->bind_param('ii', $this->id, $userid)) {
				throw new Exception("Can't bind parameter");
			}

			if (!$stmt->execute()) {
				throw new Exception("Can't update user preferences (set current account)");
			}
			$stmt->close();
		}
		else {
			throw new Exception("Can't update user preferences (set current account)");
		}
		$this->lastTransactionID = 
		  TransactionLogger::Log($this->id,is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(),0,'Account set as current');
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
	public function hasFeature($feature) 
	{
		// checking if we got feature ID instead of object for backwards compatibility
		if (is_int($feature)) {
			$feature = Feature::getByID($feature);
		}

		return $feature->isEnabledForAccount($this);
	}
	
	private static function fillCharges($account_id) 
	{

		$db = UserConfig::getDB();

    if (!($stmt = $db->prepare('SELECT date_time, amount FROM '.
      UserConfig::$mysql_prefix.'account_charge WHERE account_id = ? '.
      'ORDER BY date_time')))
    {
  		throw new Exception("Can't prepare statement: ".$db->error);
    }
		
		if (!$stmt->bind_param('i', $account_id)) {
			throw new Exception("Can't bind parameter".$stmt->error);
    }
		
		if (!$stmt->execute()) {
			throw new Exception("Can't execute statement: ".$stmt->error);
    }
		
		if (!$stmt->bind_result($datetime, $amount)) {
			throw new Exception("Can't bind result: ".$stmt->error);
    }
	
		$charges = array();	
		while($stmt->fetch() === TRUE) {
			$charges[] = array('datetime' => $datetime, 'amount' => sprintf("%.2f",$amount));
    }
		
		$stmt->close();
		return $charges;
	}
	
	public function paymentIsDue($refund = NULL) // refund is almost the same, as general payment
	{
	
		if (is_null($this->schedule)) {
		  return;
    }

		$db = UserConfig::getDB();		

		if(is_null($this->schedule) && is_null($refund)) {
		  return;
    }
		$charge_amount = is_null($refund) ? $this->schedule->charge_amount : $refund;
		// Look if there is a positive charge (actually, account surplus), it should be a single element
		$c = reset(array_keys($this->charges));
		
		// Lock tables
    $db->query("LOCK TABLES ".UserConfig::$mysql_prefix."account_charge WRITE");

		if ($c !== FALSE && $this->charges[$c]['amount'] > 0) {
      if ($this->charges[$c]['amount'] - $charge_amount < 0) { 
        // This charge is greater than we owe to user

		    $charge_amount -= $this->charges[$c]['amount'];
		    
        if (!($stmt = $db->prepare('DELETE FROM '.UserConfig::$mysql_prefix.
          'account_charge WHERE account_id = ?')))
        {
		      throw new Exception("Can't prepare statement: ".$db->error);
        }
        
        if (!$stmt->bind_param('i', $this->id)) {
          throw new Exception("Can't bind parameter".$stmt->error);
        }
          
        if (!$stmt->execute()) {
          throw new Exception("Can't execute statement: ".$stmt->error);
        }
          
        $this->charges = array();
        $stmt->close();
      } 
      else { // We still owe to user
      
        if (!($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.
          'account_charge SET amount = ? WHERE account_id = ?')))
        {
          throw new Exception("Can't prepare statement: ".$db->error);
        }
          
        $amt = $this->charges[$c]['amount'] - $charge_amount;
        if (!$stmt->bind_param('di', $amt, $this->id)) {
          throw new Exception("Can't bind parameter".$stmt->error);
        }
          
        if (!$stmt->execute()) {
          throw new Exception("Can't execute statement: ".$stmt->error);
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
		
		if($charge_amount > 0) {

      $charge = array('datetime' => date('Y-m-d H:i:s'), 
        'amount' => -$charge_amount);
      $this->charges[] = $charge;

      if (!($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.
        'account_charge (account_id, date_time, amount) VALUES (?, ?, ?)')))
      {
        throw new Exception("Can't prepare statement: ".$db->error);
      }
      
      if (!$stmt->bind_param('isd', $this->id, $charge['datetime'], 
        $charge['amount']))
      {
        throw new Exception("Can't bind parameter".$stmt->error);
      }
      
      if (!$stmt->execute()) {
        throw new Exception("Can't execute statement: ".$stmt->error);
      }

      $stmt->close();
    }

		$db->query("UNLOCK TABLES");

		if(is_null($refund)) {
  		$this->lastTransactionID =
  		  TransactionLogger::Log($this->id,is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(),
	  	    -$this->schedule->charge_amount,'Payment Schedule charge');
    }
    else {
      $this->lastTransactionID =
        TransactionLogger::Log($this->id,is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(),
          -$refund,'Refund recorded');
    }

		return TRUE;
	}
	
	public function paymentReceived($amount) 
	{

		$cleared = array();
		$db = UserConfig::getDB();
		$amount_to_log = $amount;

		// Lock tables
    $db->query("LOCK TABLES ".UserConfig::$mysql_prefix.
      "account_charge WRITE");
		foreach(array_reverse(array_keys($this->charges)) as $n => $k) {

			if ($amount <= 0) {
			  break;
      }
			if (-$this->charges[$k]['amount'] <= $amount) {
				$amount += $this->charges[$k]['amount'];
				$cleared[] = $this->charges[$k];
				unset($this->charges[$k]); 
			} 
			else {
				$this->charges[$k]['amount'] += $amount;
				
        if (!($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.
          'account_charge SET amount = ? '.
          'WHERE account_id = ? and date_time = ?')))
        {
					throw new Exception("Can't prepare statement: ".$db->error);
        }
					
        if (!$stmt->bind_param('dis', $this->charges[$k]['amount'], 
                               $this->id, $this->charges[$k]['datetime']))
        {
					throw new Exception("Can't bind parameter".$stmt->error);
        }
					
				if (!$stmt->execute()) {
					throw new Exception("Can't execute statement: ".$stmt->error);
        }

				$amount = 0;
				$stmt->close();
			}
		}
		
		foreach($cleared as $n => $k) {

      if (!($stmt = $db->prepare('DELETE FROM '.UserConfig::$mysql_prefix.
        'account_charge WHERE account_id = ? and date_time = ?')))
      {
				throw new Exception("Can't prepare statement: ".$db->error);
      }
				
			if (!$stmt->bind_param('is', $this->id, $k['datetime'])) {
				throw new Exception("Can't bind parameter".$stmt->error);
      }
				
			if (!$stmt->execute()) {
				throw new Exception("Can't execute statement: ".$stmt->error);
      }

      $stmt->close();
		}
		
		// Store excessive payment as positive charge (account surplus)
		if ($amount > 0) {
      $charge = array('datetime' => date('Y-m-d H:i:s'), 
      'amount' => $amount);
		  $this->charges[] = $charge;

      if (!($stmt = $db->prepare('INSERT INTO '.UserConfig::$mysql_prefix.
        'account_charge (account_id, date_time, amount) VALUES (?, ?, ?)')))
      {
        throw new Exception("Can't prepare statement: ".$db->error);
      }
      
      if (!$stmt->bind_param('isd', $this->id, $charge['datetime'], 
        $charge['amount']))
      {
        throw new Exception("Can't bind parameter".$stmt->error);
      }
      
      if (!$stmt->execute()) {
        throw new Exception("Can't execute statement: ".$stmt->error);
      }
        
      $stmt->close();
    }
		
    $db->query("UNLOCK TABLES");

		if ($this->getBalance() >= 0 && !$this->active) {
      TransactionLogger::Log($this->id,is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(),
        0,'Account activated due to positive balance');
		  $this->activate();
    }

 		$this->lastTransactionID =
 		  TransactionLogger::Log($this->id,is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(),
  	    $amount_to_log,'Payment received');
		return TRUE;
	}
	
	public function activatePlan($plan_slug, $schedule_slug = NULL) 
	{

		$new_plan = Plan::getPlanBySlug($plan_slug);
		if (is_null($new_plan) || $new_plan === FALSE) {
		  return FALSE;
    }

		if (!is_null($schedule_slug)) {
  		$new_schedule = $new_plan->getPaymentScheduleBySlug($schedule_slug);
	  	if (is_null($new_schedule)) {
		    $new_schedule = $new_plan->getDefaultPaymentSchedule();
      }
    } 
    else {
      $new_schedule = NULL;
    }

    /* if no schedule specified and no default schedule found 
       and new plan has at least one schedule, fail */
    if (count($new_plan->getPaymentScheduleSlugs()) && is_null($new_schedule)) {
      return FALSE;
    }

		$old_plan_slug = $this->plan->slug;
		$old_schedule_slug = is_null($this->schedule) ? NULL : $this->schedule->slug;
		$this->plan->deactivate_hook($this->id,$plan_slug, $schedule_slug);
		$this->plan = $new_plan;
		$this->schedule = $new_schedule;
		$this->plan->activate_hook($this->id,$old_plan_slug,$old_schedule_slug);
		$this->active = 1;
    $this->nextCharge = is_null($this->schedule) ? 
      NULL : date('Y-m-d H:i:s',time() + $this->schedule->charge_period * 86400);
		
		/* Update db
      There is a risk that this query fail. If so, 
      object state will differ from db state.
		  Should be addressed in further releases. */
		
		$db = UserConfig::getDB();
		
		if (!($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.
      'accounts SET plan_slug = ?, schedule_slug = ?, active = 1, next_charge = ?, '.
      'next_plan_slug = NULL, next_schedule_slug = NULL WHERE id = ?')))
    {
		    throw new Exception("Can't prepare statement: ".$db->error);
    }
		  
    if (!$stmt->bind_param('sssi', $plan_slug, $schedule_slug, 
      $this->nextCharge, $this->id))
    {
      throw new Exception("Can't bind parameter".$stmt->error);
    }
      
    if (!$stmt->execute()) {
      throw new Exception("Can't execute statement: ".$stmt->error);
    }
      
    $this->paymentIsDue();
		$this->lastTransactionID =
		  TransactionLogger::Log($this->id,is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(),
		    0,'Plan "'.$this->plan->name.'" activated');
    return TRUE;
	}
	
	public function deactivatePlan() 
	{
		$db = UserConfig::getDB();	

		$this->plan->deactivate_hook($this->id,$this->downgrade_to,NULL);

		if (!is_null($this->downgrade_to)) {

		  $this->activatePlan($this->downgrade_to);
		  $this->lastTransactionID =
		    TransactionLogger::Log($this->id,is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(),
		      0,'Plan downgraded to "'.$this->plan->name.'"');
			return TRUE;
		} 
		else {
		
		  // Nothing to downgrade to - mark account as not active
		  $this->suspend();
  		$this->lastTransactionID =
  		  TransactionLogger::Log($this->id,is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(),
  		    0,'Account suspended due to plan "'.$this->plan->name.'" deactivation');
  		return FALSE;
    }
	}
	
	public function setPaymentSchedule($schedule_slug) {
	
		if (!($schedule = $this->plan->getPaymentScheduleBySlug($schedule_slug))) {
			return FALSE;
    }
			
		$this->schedule = $schedule;
    $this->nextCharge = date('Y-m-d H:i:s',
      time() + $this->schedule->charge_period * 86400);
		
    // Update db
		$db = UserConfig::getDB();

    if (!($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.
      'accounts SET schedule_slug = ?, next_charge = ?, next_plan_slug = NULL, '.
      'next_schedule_slug = NULL WHERE id = ?')))
    {
        throw new Exception("Can't prepare statement: ".$db->error);
    }
      
    if (!$stmt->bind_param('ssi', $schedule_slug,
      $this->nextCharge, $this->id))
    {
      throw new Exception("Can't bind parameters: ".$stmt->error);
    }
      
    if (!$stmt->execute()) {
      throw new Exception("Can't execute statement: ".$stmt->error);
    }

    // Bill user
    $this->paymentIsDue();
    $this->lastTransactionID =
      TransactionLogger::Log($this->id,is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(),
        0,'Payment schedule "'.$this->schedule->name.'" set.');
    return TRUE;
	}
	
	public function getScheduleSlug() 
	{
		return $this->schedule ? $this->schedule->slug : NULL;
	}
	
	public function getPlanSlug() 
	{
		return $this->plan->slug;
	}
	
	public function getPaymentEngine() 
	{
		return $this->paymentEngine;
	}
	
	public function isIndividual() 
	{
	  return $this->isIndividual;
	}
	
	public function setPaymentEngine($engine_slug) 
	{
    if ($engine_slug == NULL) {
      return FALSE;
    }

    UserConfig::loadModule($engine_slug);
    $this->paymentEngine = new $engine_slug;
    
    // Update db
		$db = UserConfig::getDB();

    if (!($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.
      'accounts SET engine_slug = ? WHERE id = ?')))
    {
      throw new Exception("Can't prepare statement: ".$db->error);
    }
      
    if (!$stmt->bind_param('si', $engine_slug, $this->id)) {
      throw new Exception("Can't bind parameters: ".$stmt->error);
    }
      
    if (!$stmt->execute()) {
      throw new Exception("Can't execute statement: ".$stmt->error);
    }
      
    $this->lastTransactionID =
      TransactionLogger::Log($this->id,is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(),
        0,'Payment engine "'.$this->paymentEngine->getSlug().'" set.');
    return TRUE;
	}
	
	public function getBalance() 
	{
	
	  if (is_null($this->charges)) {
	    return 0;
    }
	  
	  $balance = 0;
	  foreach($this->charges as $c) {
	    $balance += $c['amount'];
    }
    
    return $balance;
  }
  
  public function planChangeRequest($plan_slug, $schedule_slug) 
  {
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
    } 
    else {
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

    // Update db
		$db = UserConfig::getDB();

    if (!($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.
      'accounts SET next_plan_slug = ?, next_schedule_slug = ? WHERE id = ?')))
    {
      throw new Exception("Can't prepare statement: ".$db->error);
    }
      
    if (!$stmt->bind_param('ssi', $plan_slug, $schedule_slug, $this->id)) {
      throw new Exception("Can't bind parameters: ".$stmt->error);
    }
      
    if (!$stmt->execute()) {
      throw new Exception("Can't execute statement: ".$stmt->error);
    }

    $this->lastTransactionID =
      TransactionLogger::Log($this->id,is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(),
        0,'Request to change plan to "'.$new_plan->name.
        (is_null($new_schedule) ? '"' : '" and schedule to "'.$new_schedule->name).'" stored.');
    return TRUE;
	}
	
	public function scheduleChangeRequest($schedule_slug) 
	{
		if (!($schedule = $this->plan->getPaymentScheduleBySlug($schedule_slug))) {
			return FALSE;
    }

    // Check, if schedule could be activated immediately
    if (is_null($this->nextCharge) && 
      $this->getBalance() >= $schedule->charge_amount) 
    {
      if (!is_null($this->paymentEngine)) {
        $this->paymentEngine->changeSubscription($this->plan,$schedule);
      }

      return $this->setPaymentSchedule($schedule_slug);
    }
	  
    // Update db
		$db = UserConfig::getDB();

    if (!($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.
      'accounts SET next_plan_slug = plan_slug, next_schedule_slug = ? WHERE id = ?')))
    {
      throw new Exception("Can't prepare statement: ".$db->error);
    }
      
    if (!$stmt->bind_param('si', $schedule_slug, $this->id)) {
      throw new Exception("Can't bind parameters: ".$stmt->error);
    }
      
    if (!$stmt->execute()) {
      throw new Exception("Can't execute statement: ".$stmt->error);
    }

    $this->lastTransactionID =
      TransactionLogger::Log($this->id,is_null($this->paymentEngine) ? NULL : $this->paymentEngine->getSlug(),
        0,'Request to change schedule to "'.$schedule->name.'" stored.');      
    return TRUE;
	}

	public function suspend() 
	{
	  $this->active = 0;
	  
		$db = UserConfig::getDB();

    if (!($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.
      'accounts SET active = 0 WHERE id = ?'))) 
    {
      throw new Exception("Can't prepare statement: ".$db->error);
    }
      
    if (!$stmt->bind_param('i', $this->id)) {
      throw new Exception("Can't bind parameters: ".$stmt->error);
    }
      
    if (!$stmt->execute()) {
      throw new Exception("Can't execute statement: ".$stmt->error);
    }
      
    return TRUE;
	}

	public function activate() 
	{
	  $this->active = 1;
	  
		$db = UserConfig::getDB();

    if (!($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.
      'accounts SET active = 1 WHERE id = ?')))
    {
      throw new Exception("Can't prepare statement: ".$db->error);
    }
      
    if (!$stmt->bind_param('i', $this->id)) {
      throw new Exception("Can't bind parameters: ".$stmt->error);
    }
      
    if (!$stmt->execute()) {
      throw new Exception("Can't execute statement: ".$stmt->error);
    }
      
    return TRUE;
	}
	
	public function getLastTransactionID() 
	{
	  return $this->lastTransactionID;
  }
}
