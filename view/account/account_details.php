<?php

require_once(dirname(dirname(dirname(__FILE__))).'/users.php');
require_once(UserConfig::$SMARTY_DIR.'/Smarty.class.php');

$user = User::require_login();
$account = Account::getCurrentAccount($user);

$account_data = array(
  'name', 'role', 'isActive', 'paymentEngine' );

$plan_data = array(
  'name', 'description', 'base_price', 'base_period', 'details_url', 'downgrade_to', 'grace_period');
  
$schedule_data = array(
  'name', 'description', 'charge_amount', 'charge_period');
  
$smarty = new Smarty();

$smarty->assign('account_name',$account->getName());
$smarty->assign('account_role',$account->getUserRole());
$smarty->assign('account_isActive',$account->isActive());
$smarty->assign('account_engine', $account->getPaymentEngine());
  
$plan = Plan::getPlan($account->getPlanID());
foreach($plan_data as $d)
  $smarty->assign('plan_'.$d, $plan->$d);

$schedule_id = $account->getScheduleID();
if($schedule_id)
  $schedule = $plan->getPaymentSchedule($schedule_id);
if($schedule)
  foreach($schedule_data as $d)
    $smarty->assign('schedule_'.$d, $schedule->$d);
    
$smarty->assign('charges',$account->getCharges());
