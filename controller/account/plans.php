<?php
require_once(dirname(dirname(__DIR__)).'/classes/User.php');

UserTools::preventCSRF();

$user = User::require_login();
$account = Account::getCurrentAccount($user);

$data = explode('.',$_REQUEST['plan']);
if(!isset($data[1]))
  $data[1] = NULL;
session_start();

try {
  // Check if plan and schedule exists
  if(!($plan = Plan::getPlanBySlug($data[0])))
    throw new Exception("Unknown plan '".$data[0].'"');

  if(!is_null($data[1]) && !($schedule = $plan->getPaymentScheduleBySlug($data[1])))
    throw new Exception("Unknown schedule '".$data[1]."' for plan '".$data[0]."'");
} catch (Exception $e) {
  $_SESSION['message'][] = $e->getMessage();
  header('Location: '.$_SERVER['HTTP_REFERER']);
  exit;
}

// Check balance
if (!is_null($schedule) && $schedule->charge_amount > $account->getBalance()) {

  $_SESSION['message'][] = "Not enough funds to activate plan/schedule";

}
elseif ($account->getPlanSlug() != $data[0] ||
  (!is_null($account->getNextPlan()) && $account->getNextPlan()->slug != $data[0]))
{
 // Not changing plan if requested plan is same as current or next

  if ($account->planChangeRequest($data[0],$data[1])) {
    if ($account->getPlanSlug() != $data[0]) {
      // Plan activation postponed
      $_SESSION['message'][] = "Your request to activate plan '".$data[0].'/'.$data[1].
        "' accepted. Plan will be activated on the next charge according to your current schedule.";
    }
    else {
      // Plan activated immediately
      $_SESSION['message'][] = "Plan ".$data[0].'/'.$data[1]." activated.";
    }
  }
  else {
    $_SESSION['message'][] = "Error activating plan";
  }

}
elseif (!is_null($data[1]) && ($account->getScheduleSlug() != $data[1] ||
  (!is_null($account->getNextSchedule()) && $account->getNextSchedule()->slug!= $data[1])))
{
  // Not changing schedule if requested schedule is same as current or next

  if ($account->scheduleChangeRequest($data[1])) {
    if ($account->getScheduleSlug() != $data[1]) {
      // Schedule change postponed
      $_SESSION['message'][] = "Your request to change payment schedule to '".$data[1].
        "' accepted. Schedule will be activated on the next charge according to your current schedule.";
    }
    else {
      // Schedule changed immediately
      $_SESSION['message'][] = "Payment schedule changed to ".$data[1];
    }
  }
  else {
    $_SESSION['message'][] = "Error changing schedule";
  }
}

header('Location: '.$_SERVER['HTTP_REFERER']);
