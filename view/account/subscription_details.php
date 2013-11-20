<?php
$user = User::require_login();
$account = Account::getCurrentAccount($user);

$plan_data = array(
  'name', 'description', 'base_price', 'base_period', 'details_url', 'grace_period');

$schedule_data = array(
  'name', 'description', 'charge_amount', 'charge_period');

session_start();
if(isset($_SESSION['message'])) {
  $template_data['message'] = $_SESSION['message'];
  unset($_SESSION['message']);
  $fatal = isset($_SESSION['fatal']) ? $_SESSION['fatal'] : 0;
  unset($_SESSION['fatal']);
  if($fatal) {
    $template_data['fatal'] = 1;
    return;
  }
}

$template_data['account_name'] = $account->getName();
$template_data['account_isActive'] = $account->isActive();
$template_data['account_engine'] = is_null($account->getPaymentEngine()) ? 'None' : $account->getPaymentEngine()->getTitle();
$template_data['account_next_charge'] = preg_replace("/ .*/","",$account->getNextCharge());

$plan = $account->getPlan(); // can be FALSE

// If plan is not set, this page just does not make sense
Plan::enforcePlan($plan);

foreach ($plan_data as $d) {
  $template_data['plan_'.$d] = $plan->$d;
}

$downgrade = Plan::getPlanBySlug($plan->downgrade_to);
if ($downgrade) {
	$template_data['plan_downgrade_to'] = $downgrade->name;
}

$plan = $account->getNextPlan();

if (!is_null($plan)) {
  foreach($plan_data as $d) {
    $template_data['next_plan_'.$d] = $plan->$d;
  }
}

$schedule = $account->getSchedule();
if (!is_null($schedule)) {
  foreach($schedule_data as $d) {
    $template_data['schedule_'.$d] = $schedule->$d;
  }
}

$schedule = $account->getNextSchedule();
if (!is_null($schedule)) {
  foreach($schedule_data as $d) {
    $template_data['next_schedule_'.$d] = $schedule->$d;
  }
}

$engine = $account->getPaymentEngine();
$template_data['payment_engine'] = empty($engine) ? NULL : $engine->getTitle();
$template_data['charges'] = $account->getCharges();
$template_data['balance'] = $account->getBalance();
$template_data['USERSROOTURL'] = UserConfig::$USERSROOTURL;
