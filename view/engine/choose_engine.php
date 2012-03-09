<?php

require_once(dirname(dirname(dirname(__FILE__))).'/users.php');
require_once(dirname(dirname(dirname(__FILE__))).'/smarty/libs/Smarty.class.php');

$user = User::require_login();

$smarty = new Smarty();
session_start();

if(isset($_SESSION['message'])) {
  $smarty->assign('message',$_SESSION['message']);
  unset($_SESSION['message']);
  $fatal = isset($_SESSION['fatal']) ? $_SESSION['fatal'] : 0;
  unset($_SESSION['fatal']);
  if($fatal) {
    $smarty->assign('fatal',1);
    return;
  }
}

$account = Account::getCurrentAccount($user);
$current_engine = $account->getPaymentEngine();
$current_engine = empty($current_engine) ? NULL : $current_engine->getID();

$engines = array();
foreach (UserConfig::$payment_modules as $mod) {
  $engine = array();
  $engine['id'] = $mod->getID();
  $engine['title'] = $mod->getTitle();
  $engine['current'] = $engine['id'] == $current_engine;
  
  $engines[] = $engine;
}

$smarty->assign('engines',$engines);
$smarty->assign('USERSROOTURL',UserConfig::$USERSROOTURL);
