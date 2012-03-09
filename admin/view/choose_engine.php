<?php

require_once(dirname(dirname(__FILE__)).'/admin.php');
require_once(dirname(dirname(dirname(__FILE__))).'/smarty/libs/Smarty.class.php');

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

$account_id = htmlspecialchars($_REQUEST['account_id']);
if(is_null($account = Account::getByID($account_id))) {
  $smarty->assign('message',array("Can't find account with id $account_id"));
  $smarty->assign('fatal',1);
  return;
}

$smarty->assign('account_name',$account->getName());
$smarty->assign('account_id',$account->getID());

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
ob_start();
UserTools::renderCSRFNonce();
$CSRFNonce = ob_get_contents();
ob_end_clean();
$smarty->assign('CSRFNonce',$CSRFNonce); // Required for POST
