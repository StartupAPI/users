<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(dirname(dirname(__FILE__))).'/User.php');

$user = User::require_login();
$account = Account::getCurrentAccount($user);

$engine = htmlspecialchars($_REQUEST['engine']);
session_start();

// Check for no-op
if(!is_null($account->getPaymentEngine()) && $account->getPaymentEngine()->getSlug() == $engine) {
  $_SESSION['message'][] = "You are already using '".$account->getPaymentEngine()->getTitle()."'";
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
  $_SESSION['message'][] = "Error changing payment method. Please contact site administrator.";
}

header('Location: '.$_SERVER['HTTP_REFERER']);
