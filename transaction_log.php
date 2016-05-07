<?php
require_once(__DIR__.'/global.php');

if (!UserConfig::$useSubscriptions) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

$user = User::require_login();
$account = Account::getCurrentAccount($user);

$template_info = StartupAPI::getTemplateInfo();
$template_info['account_name'] = $account->getName();

if ($account->getUserRole($user) !== Account::ROLE_ADMIN) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

# This view depends on request parameters
$date = array();
$date['from'] = isset($_REQUEST['from']) && $_REQUEST['from'] != '' ? $_REQUEST['from'] : NULL;
$date['to'] = isset($_REQUEST['to']) && $_REQUEST['to'] != '' ? $_REQUEST['to'] : NULL;

$message = array();
$tms = array();
foreach($date as $k => $v) {

  if(is_null($v)) continue;
  if(preg_match("/^(?:(\d{1,2})\/(\d{1,2})\/(\d{4})|(\d{4})-(\d{1,2})-(\d{1,2}))$/",$date[$k],$m)) {

    foreach(array(1,2,5,6) as $i)
      if(strlen($m[$i]) == 1 && $m[$i] < 10) $m[$i] = '0'.$m[$i];

    $date[$k] = $m[1] != '' ? $m[3].'-'.$m[1].'-'.$m[2] : $m[4].'-'.$m[5].'-'.$m[6];
    $tms[$k] = $m[1] != '' ? $m[3].$m[1].$m[2] : $m[4].$m[5].$m[6];
  } else {
    $message[] = "Can't parse '".$k."' date '".$date[$k]."'";
  }
}

if(count($message)) {

  $template_info['fatal'] = 1;
  $template_info['message'] = $message;
  return;
}

if(isset($tms['from'],$tms['to']) && $tms['from'] > $tms['to']) {
  $d = $date['from'];
  $date['from'] = $date['to'];
  $date['to'] = $d;
}

$template_info['from'] = $date['from'];
$template_info['to'] = $date['to'];

$from_to = (is_null($date['from']) ? '' : '&from='.$date['from']).(is_null($date['to']) ? '' : '&to='.$date['to']);
$template_info['from_to'] = $from_to;

# Pagination
$perpage = 5;
$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 0;
$offset = $page * $perpage;
$template_info['perpage'] = $perpage;
$template_info['page'] = $page;

$log = TransactionLogger::getAccountTransactions($account->getID(),$date['from'],$date['to'], false, $perpage,$offset);

# Cheating on payment modules :)

$mods = array();
foreach(UserConfig::$payment_modules as $pm)
  $mods[$pm->getID()] = $pm->getTitle();

foreach($log as $k => $l) {
  if(array_key_exists($l['engine_slug'],$mods))
    $log[$k]['engine_slug'] = $mods[$l['engine_slug']];
  else
    $log[$k]['engine_slug'] = '';
}

$template_info['log'] = $log;


// setting section value
$template_info['PAGE']['SECTION'] = 'transaction_log';

StartupAPI::$template->display('@startupapi/account/transaction_log.html.twig', $template_info);
