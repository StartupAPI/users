<?php
require_once(dirname(__DIR__).'/admin.php');

$engine = htmlspecialchars($_REQUEST['engine']);
$account_id = htmlspecialchars($_REQUEST['account_id']);

session_start();
if(is_null($account = Account::getByID($account_id))) {
  $_SESSION['message'][] = "Can't find account with id ".$account_id;
  header('Location: '.$_SERVER['HTTP_REFERER']);
  exit;
}

// Check for no-op
if(!is_null($account->getPaymentEngine()) && $account->getPaymentEngine()->getSlug() == $engine) {
  $_SESSION['message'][] = "This account already uses '".$account->getPaymentEngine()->getTitle()."'";
  header('Location: '.$_SERVER['HTTP_REFERER']);
  exit;
}

// Check if engine exists
$engine_found = NULL;
foreach(UserConfig::$payment_modules as $mod) {

  if ($mod->getID() == $engine) {
    $engine_found = $mod;
    break;
  }
}

if (is_null($engine_found)) {
  $_SESSION['message'][] = "Can't find engine with slug '".$engine."'";
  header('Location: '.$_SERVER['HTTP_REFERER']);
  exit;
}

if ($account->setPaymentEngine($engine_found->getSlug())) {
  $_SESSION['message'][] = "Payment method changed to '".$engine_found->getTitle()."'";
}
else {
  $_SESSION['message'][] = "Error changing payment method. Please check server logs.";
}

header('Location: '.$_SERVER['HTTP_REFERER']);
