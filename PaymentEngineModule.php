<?php

  require_once(dirname(__FILE__).'/Account.php');

  interface IPaymentEngine extends IUserBaseModule 
  {
    public function changeSubscription($account_id, $plan_slug, $schedule_slug);
    public function paymentReceived($data);
    public function refund($data);
    public function unsubscribe($account_id);
    public function cronHandler();
    public function getSlug();
    public function storeTransactionDetails($transaction_id,$details);
    public function expandTransactionDetails($transaction_id);
  }
    
  abstract class PaymentEngine extends UserBaseModule implements IPaymentEngine 
  {
    protected $engineSlug;
    // ???
    
    public function __construct() 
    {
      parent::__construct();
      UserConfig::$payment_modules[] = $this;
    }

    public function paymentReceived($data) 
    {
      // data is array with following required keys:
      // account_id
      // amount
      // This method should be called by successor class which actually receives information about payment

      if (!(isset($data['account_id']) && isset($data['amount']))) {
        throw new Exception("No account_id in element ".print_r($data,1));
      }

      $account = Account::getByID($data['account_id']);
      if (is_null($account)) {
        return FALSE;
      }
      $account->paymentReceived($data['amount']);
      
      return $account->getLastTransactionID();
    }

    public function refund($data) 
    {
      // refund is generally the same, as payment

      if (!(isset($data['account_id']) && isset($data['amount']))) {
        throw new Exception("No account_id in element ".print_r($data,1));
      }
      
      $account = Account::getByID($data['account_id']);
      if (is_null($account)) {
        return FALSE;
      }
      $account->paymentIsDue($data['amount']);
      
      return $account->getLastTransactionID();
    
    }
    
    public function unsubscribe($account_id) 
    {
      $account = Account::getByID($account_id);  
      if (is_null($account)) {
        return FALSE;
      }
      while($account->deactivatePlan()) {}
      
    }
    
    public function getID()
    {
      return $this->getSlug();
    }
    
    public function cronHandler() 
    {
      $db = UserConfig::getDB();

      // Deactivate all accounts where grace period is expired
      // Those accounts should have negative balance, so we can restrict 'where' condition
      
      if (!($stmt = $db->prepare('SELECT a.id, UNIX_TIMESTAMP(MIN(ac.date_time)) FROM '.UserConfig::$mysql_prefix.'accounts a INNER JOIN '.
        UserConfig::$mysql_prefix.'account_charge ac ON a.id = ac.account_id AND ac.amount > 0 AND engine_slug = ? GROUP BY a.id')))
      {
          throw new Exception("Can't prepare statement: ".$db->error);
      }
          
      if (!$stmt->bind_param('s', $this->engineSlug)) {
        throw new Exception("Can't bind parameter".$stmt->error);
      }
        
      if (!$stmt->execute()) {
        throw new Exception("Can't execute statement: ".$stmt->error);
      }
        
      if (!$stmt->store_result()) {
        throw new Exception("Can't store result: ".$stmt->error);
      }
        
      if (!$stmt->bind_result($account_id, $date_time)) {
        throw new Exception("Can't bind result: ".$stmt->error);
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

      if (!($stmt = $db->prepare('SELECT id, plan_slug, next_plan_slug, schedule_slug, next_schedule_slug FROM '.
        UserConfig::$mysql_prefix.'accounts WHERE engine_slug = ? AND active = 1 AND next_charge < ?')))
      {
          throw new Exception("Can't prepare statement: ".$db->error);
      }
          
      if (!$stmt->bind_param('ss', $this->engineSlug, date('Y-m-d H:i:s'))) {
        throw new Exception("Can't bind parameter".$stmt->error);
      }
        
      if (!$stmt->execute()) {
        throw new Exception("Can't execute statement: ".$stmt->error);
      }
        
      if (!$stmt->store_result()) {
        throw new Exception("Can't store result: ".$stmt->error);
      }

      if (!$stmt->bind_result($account_id, $plan_slug, $next_plan_slug, $schedule_slug, $next_schedule_slug)) {
        throw new Exception("Can't bind result: ".$stmt->error);
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

            } 
            else { // change plan and schedule
            
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
            if (!($stmt2 = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.
              'accounts SET next_charge = next_charge + INTERVAL ? DAY WHERE id = ?')))
            {
                throw new Exception("Can't prepare statement: ".$db->error);
            }
          
            if (!$stmt2->bind_param('ii', $schedule->charge_period, $account->getID())) {
              throw new Exception("Can't bind parameter".$db->error);
            }
            
            if (!$stmt2->execute()) {
              throw new Exception("Can't execute statement: ".$stmt->error);
            }
            
            $stmt2->close();
          }
        }
      }	// end of while
    }
  }
