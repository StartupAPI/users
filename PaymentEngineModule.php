<?php

  require_once(dirname(__FILE__).'/Account.php');

  interface IPaymentEngine extends IUserBaseModule {
  
    public function changeSubscription($plan_id, $schedule_id);
    public function paymentReceived($data);
    public function unsubscribe($account_id);
    public function cronHandler();
  }
    
  abstract class PaymentEngine extends UserBaseModule implements IPaymentEngine {
  
    protected $engineID;
    # ???
    
    public function paymentReceived($data) {
    
      # data is array with following required keys:
      # account_id
      # amount
      # This method should be called by successor class which actually receives information about payment

      if (!(isset($d['account_id']) && isset($d['amount'])))
        throw new Exception("No account_id in element ".print_r($d,1));
      $account = Account::getByID($d['account_id']);
      if (is_null($account))
        return FALSE;
      $account->paymentReceived($d['amount']);
      
      return TRUE;
    
    }
    
    public function unsubscribe($account_id) {
    
      $account = Account::getByID($d['account_id']);  
      if (is_null($account))
        return FALSE;
      while($account->deactivatePlan()) {}
      
    }
    
    public function cronHandler() {
    
      $db = UserConfig::getDB();

      # Deactivate all accounts where grace period is expired
      # Those accounts should have negative balance, so we can restrict 'where' condition
      
      if (!($stmt = $db->prepare('SELECT a.id, UNIX_TIMESTAMP(MIN(ac.date_time)) FROM '.UserConfig::$mysql_prefix.'accounts a INNER JOIN '.
        UserConfig::$mysql_prefix.'account_charge ac ON a.id = ac.account_id AND ac.amount > 0 AND engine = ? GROUP BY a.id')))
          throw new Exception("Can't prepare statement: ".$db->error);
          
      if (!$stmt->bind_param('s', $this->engineID))
        throw new Exception("Can't bind parameter".$stmt->error);
        
      if (!$stmt->execute())
        throw new Exception("Can't execute statement: ".$stmt->error);
        
      if (!$stmt->store_result())
        throw new Exception("Can't store result: ".$stmt->error);
        
      if (!$stmt->bind_result($account_id, $date_time))
        throw new Exception("Can't bind result: ".$stmt->error);
        
      while ($stmt->fetch() === TRUE) {
      
        if (is_null($account = Account::getByID($account_id)))
          continue;
          
        if (is_null($plan = $account->getPlan()))
          continue;
          
        if ($plan->grace_period && $plan->grace_period * 86400 + $date_time > time())
          $account->deactivatePlan();
      }
        
      # Find all accounts which are served by this Payment Engine and which has next_charge date earlier than now      

      if (!($stmt = $db->prepare('SELECT id, plan, next_plan, schedule, next_schedule FROM '.
        UserConfig::$mysql_prefix.'accounts WHERE engine = ? AND active = 1 AND next_charge < ?')))
          throw new Exception("Can't prepare statement: ".$db->error);
          
      if (!$stmt->bind_param('ss', $this->engineID, date('Y-m-d H:i:s')))
        throw new Exception("Can't bind parameter".$stmt->error);
        
      if (!$stmt->execute())
        throw new Exception("Can't execute statement: ".$stmt->error);
        
      if (!$stmt->store_result())
        throw new Exception("Can't store result: ".$stmt->error);

      if (!$stmt->bind_result($account_id, $plan_id, $next_plan_id, $schedule_id, $next_schedule_id))
        throw new Exception("Can't bind result: ".$stmt->error);
        
      while ($stmt->fetch() === TRUE) {
        
        # If plan/schedule change requested, perform
        # Again, check if user balance is sufficient to do this change
        
        if (is_null($account = Account::getByID($account_id)))
          continue;
          
        $billed = 0;
        if (!is_null($next_plan_id) || !is_null($next_schedule_id)) {

          $next_plan = Plan::getPlan($next_plan_id);
          $next_schedule = is_null($next_plan) ? NULL : $next_plan->getPaymentSchedule($next_schedule_id);
          
          # ok if either no next_schedule, or balance is sufficient
          $ok = is_null($next_schedule) || $account->getBalance() >= $next_schedule->charge_amount;
            
          if (!is_null($next_plan) && $ok) { # next_plan found, change could be performed
            
            if ($next_plan_id == $plan_id) { # only change schedule
            
              $account->setPaymentSchedule($next_schedule_id); # schedule changed, user billed automatically
              $billed = 1;

            } else { # change plan and schedule
            
              $account->activatePlan($next_plan_id, $next_schedule_id);
              $billed = 1;
            }
          } # othrewise plan and schedule left as it was before
        }

        # Bill user if not yet billed
        if (!$billed) {
          
          $account->paymentIsDue();
            
          $plan = $account->getPlan();
          if (!is_null($schedule = $plan->getPaymentSchedule())) {
            
            # Set new next_charge
            if (!($stmt2 = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.
              'accounts SET next_charge = next_charge + INTERVAL ? DAY WHERE id = ?')))
                throw new Exception("Can't prepare statement: ".$db->error);
          
            if (!$stmt2->bind_param('ii', $schedule->charge_period, $account->getID()))
              throw new Exception("Can't bind parameter".$db->error);
            
            if (!$stmt2->execute())
              throw new Exception("Can't execute statement: ".$stmt->error);
            
            $stmt2->close();
          }
        }
      }	# end of while
    }
  }
              
          
            
      
      
      
      
      
      
      
      
      
      
      