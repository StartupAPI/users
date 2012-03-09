<?php

require_once(dirname(dirname(dirname(__FILE__))).'/users.php');
require_once(dirname(dirname(dirname(__FILE__))).'/smarty/libs/Smarty.class.php');

$user = User::require_login();
$account = Account::getCurrentAccount($user);

$plan_data = array(
  'name', 'description', 'base_price', 'base_period', 'details_url', 'grace_period');
  
$schedule_data = array(
  'name', 'description', 'charge_amount', 'charge_period');
  
$smarty = new Smarty();

$smarty->assign('account_name',$account->getName());
$smarty->assign('account_role',$account->getUserRole());
$smarty->assign('account_isActive',$account->isActive());
$smarty->assign('account_engine', is_null($account->getPaymentEngine()) ? 'None' : $account->getPaymentEngine()->getTitle());
$smarty->assign('account_next_charge', preg_replace("/ .*/","",$account->getNextCharge()));
  
$plan = $account->getPlan();
foreach($plan_data as $d)
  $smarty->assign('plan_'.$d, $plan->$d);
  
$downgrade = Plan::getPlanBySlug($plan->downgrade_to);
if($downgrade) $smarty->assign('plan_downgrade_to', $downgrade->name);

$plan = $account->getNextPlan();
if($plan)
  foreach($plan_data as $d)
    $smarty->assign('next_plan_'.$d, $plan->$d);

$schedule = $account->getSchedule();
if($schedule)
  foreach($schedule_data as $d)
    $smarty->assign('schedule_'.$d, $schedule->$d);

$schedule = $account->getNextSchedule();
if($schedule)
  foreach($schedule_data as $d)
    $smarty->assign('next_schedule_'.$d, $schedule->$d);

$engine = $account->getPaymentEngine();
$smarty->assign('payment_engine',empty($engine) ? NULL : $engine->getTitle());
$smarty->assign('charges',$account->getCharges());
$smarty->assign('balance',$account->getBalance());
$smarty->assign('USERSROOTURL',UserConfig::$USERSROOTURL);
