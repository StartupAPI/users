<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(dirname(dirname(__FILE__))).'/User.php');

$user = User::require_login();
$account = Account::getCurrentAccount($user);

$data = explode('.',$_REQUEST['plan']);
if(!isset($data[1]))
  $data[1] = NULL;
session_start();

try {
  # Check if plan and schedule exists
  if(!($plan = Plan::getPlan($data[0])))
    throw new Exception("Unknown plan '".$data[0].'"');
    
  if(!is_null($data[1]) && !($schedule = $plan->getPaymentSchedule($data[1])))
    throw new Exception("Unknown schedule '".$data[1]."' for plan '".$data[0]."'");
} catch (Exception $e) {
  $_SESSION['message'][] = $e->getMessage();
  header('Location: '.$_SERVER['HTTP_REFERER']);
  exit;
}

# Check balance
if($schedule && $schedule->charge_amount > $account->getBalance()) {

  $_SESSION['message'][] = "Not enough funds to activate plan/schedule";

} elseif($account->getPlanSlug() != $data[0]) {

  if($account->activatePlan($data[0],$data[1]))
    $_SESSION['message'][] = "Plan activated";
  else
    $_SESSION['message'][] = "Error activating plan";

} elseif(!is_null($data[1]) && $account->getScheduleSlug() != $data[1]) {

  if($account->setPaymentSchedule($data[1]))
    $_SESSION['message'][] = "Payment schedule changed";
  else
    $_SESSION['message'][] = "Error changing schedule";
}

header('Location: '.$_SERVER['HTTP_REFERER']);
