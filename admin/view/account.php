<?php

require_once(dirname(dirname(__FILE__)).'/admin.php');
require_once(dirname(dirname(dirname(__FILE__))).'/smarty/libs/Smarty.class.php');

$smarty = new Smarty();

$account_id = htmlspecialchars($_REQUEST['account_id']);
if(is_null($account = Account::getByID($account_id))) {
  $smarty->assign('message',array("Can't find account with id $account_id"));
  $smarty->assign('fatal',1);
  return;
}

$plan_data = array(
  'name', 'description', 'base_price', 'base_period', 'details_url', 'grace_period');
  
$schedule_data = array(
  'name', 'description', 'charge_amount', 'charge_period');
  
$smarty->assign('account_id',$account_id);
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

$smarty->assign('charges',$account->getCharges());

$acct_users = $account->getUsers();
$users = array();
foreach($acct_users as $user)
  $users[] = array('id' => $user->getID(), 'name' => $user->getName());
$smarty->assign('users',$users);
$smarty->assign('USERSROOTURL',UserConfig::$USERSROOTURL);
