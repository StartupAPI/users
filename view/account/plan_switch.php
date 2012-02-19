<?php

require_once(dirname(dirname(dirname(__FILE__))).'/users.php');
require_once(UserConfig::$SMARTY_DIR.'/Smarty.class.php');

$user = User::require_login();
$account = Account::getCurrentAccount($user);

$smarty = new Smarty();
session_start();

if(isset($_SESSION['message'])) {
  $smarty->assign('message',$_SESSION['message']);
  unset($_SESSION['message']);
  $fatal = isset($_SESSION['fatal']) ? $_SESSION['fatal'] : 0;
  unset($_SESSION['fatal']);
  if($fatal) {
    $smarty->assign('fatal',1);
    exit;
  }
}

if(!$account->isActive()) {
  $smarty->assign('message',array('This account is not active. Please activate it first.'));
  $smarty->assign('fatal',1);
  exit;
}

$plan_data = array(
  'id', 'name', 'description', 'base_price', 'base_period', 'details_url', 'downgrade_to', 'grace_period');
$schedule_data = array(
  'id', 'name', 'description', 'charge_amount', 'charge_period');
  
$plans = array();

$plan_ids = Plan::getPlanIDs();  
foreach($plan_ids as $p) { # Iterate over all configured plans

  $this_plan = Plan::getPlan($p);
  $plan = array();
  foreach($plan_data as $d) # Put all plan properties
    $plan[$d] = $this_plan->$d;
    
  if($account->getPlan()->id == $this_plan->id) # Mark plan as current if so
    $plan['current'] = TRUE;
  else
    $plan['current'] = FALSE;
    
  $schedule = array();
  $schedule_ids = $this_plan->getPaymentScheduleIDs(); # Iterate over all schedules of this plan
  foreach($schedule_ids as $s) {
    
    $this_schedule = $this_plan->getPaymentSchedule($s);
    foreach($schedule_data as $sd)                       # Put all schedule properties
      $schedule[$sd] = $this_schedule->$sd;
      
    if($plan['current'] && $account->getSchedule()->id == $this_schedule->id)
      $schedule['current'] = TRUE;
    else
      $schedule['current'] = FALSE;
  
    $plan['schedules'][] = $schedule;
  }
  $plans[] = $plan;
}
  
$smarty->assign('plans',$plans);
