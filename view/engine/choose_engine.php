<?php
require_once(dirname(dirname(dirname(__FILE__))).'/global.php');

$user = User::require_login();

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

$template_data['engines'] = $engines;
$template_data['USERSROOTURL'] = UserConfig::$USERSROOTURL;
