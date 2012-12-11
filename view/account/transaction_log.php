<?php
require_once(dirname(dirname(dirname(__FILE__))).'/users.php');

$user = User::require_login();
$account = Account::getCurrentAccount($user);

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

  $template_data['fatal'] = 1;
  $template_data['message'] = $message;
  return;
}

if(isset($tms['from'],$tms['to']) && $tms['from'] > $tms['to']) {
  $d = $date['from'];
  $date['from'] = $date['to'];
  $date['to'] = $d;
}

$template_data['from'] = $date['from'];
$template_data['to'] = $date['to'];

$from_to = (is_null($date['from']) ? '' : '&from='.$date['from']).(is_null($date['to']) ? '' : '&to='.$date['to']);
$template_data['from_to'] = $from_to;

# Pagination
$perpage = 20;
$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 0;
$offset = $page * $perpage;
$template_data['perpage'] = $perpage;
$template_data['page'] = $page;

$log = TransactionLogger::getAccountTransactions($account->getID(),$date['from'],$date['to'], false, $perpage,$offset);

# Cheating on payment modules :)

$mods = array();
foreach(UserConfig::$payment_modules as $pm)
  $mods[$pm->getID()] = $pm->getTitle();

foreach($log as $k => $l) {
  if(array_key_exists($l['engine_slug'],$mods))
    $log[$k]['engine_slug'] = $mods[$l['engine_slug']];
  else
    $log[$k]['engine_slug'] = 'Unknown';
}

$template_data['log'] = $log;
$template_data['USERSROOTURL'] = UserConfig::$USERSROOTURL;
