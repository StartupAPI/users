<?php
require_once(dirname(dirname(__DIR__)).'/global.php');

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
$current_engine_slug = empty($current_engine) ? NULL : $current_engine->getSlug();

$engines = array();
foreach (UserConfig::$payment_modules as $mod) {
  $engine = array();
  $engine['slug'] = $mod->getSlug();
  $engine['title'] = $mod->getTitle();
  $engine['current'] = ($engine['slug'] == $current_engine_slug);

  $engines[] = $engine;
}

$template_data['engines'] = $engines;
$template_data['USERSROOTURL'] = UserConfig::$USERSROOTURL;
