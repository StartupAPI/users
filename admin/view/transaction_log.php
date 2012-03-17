<?php

require_once(dirname(dirname(__FILE__)).'/admin.php');
require_once(dirname(dirname(dirname(__FILE__))).'/smarty/libs/Smarty.class.php');

$smarty = new Smarty();
$account_id = htmlspecialchars($_REQUEST['account_id']);
if(is_null($account = Account::getByID($account_id))) {
  $smarty->assign('message',array("Can't find account with id $account_id"));
  $smarty->assign('fatal',1);
  return;
}
$smarty->assign('account_id',$account_id);
$smarty->assign('account_name',$account->getName());

# This view depends on request parameters
$date = array();
$date['from'] = isset($_REQUEST['from']) && $_REQUEST['from'] != '' ? $_REQUEST['from'] : NULL;
$date['to'] = isset($_REQUEST['to']) && $_REQUEST['to'] != '' ? $_REQUEST['to'] : NULL;

$message = array();
$tms = array();
foreach($date as $k => $v) {

  if(is_null($v)) {
    continue;
  }

  if(preg_match("/^(?:(\d{1,2})\/(\d{1,2})\/(\d{4})|(\d{4})-(\d{1,2})-(\d{1,2}))$/",$date[$k],$m)) {

    foreach(array(1,2,5,6) as $i) {
      if(strlen($m[$i]) == 1 && $m[$i] < 10) {
        $m[$i] = '0'.$m[$i];
      }
    }

    $date[$k] = $m[1] != '' ? $m[3].'-'.$m[1].'-'.$m[2] : $m[4].'-'.$m[5].'-'.$m[6];
    $tms[$k] = $m[1] != '' ? $m[3].$m[1].$m[2] : $m[4].$m[5].$m[6];
  }
  else {
    $message[] = "Can't parse '".$k."' date '".$date[$k]."'";
  }
}

if(count($message)) {

  $smarty->assign('fatal',1);
  $smarty->assign('message',$message);
  return;
}

if(isset($tms['from'],$tms['to']) && $tms['from'] > $tms['to']) {
  $d = $date['from'];
  $date['from'] = $date['to'];
  $date['to'] = $d;
}

$smarty->assign('from',$date['from']);
$smarty->assign('to',$date['to']);

$from_to = (is_null($date['from']) ? '' : '&from='.$date['from']).(is_null($date['to']) ? '' : '&to='.$date['to']);
$smarty->assign('from_to',$from_to);


# Pagination
$perpage = 20;
$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 0;
$offset = $page * $perpage;
$smarty->assign('perpage',$perpage);
$smarty->assign('page',$page);

$log = TransactionLogger::getAccountTransactions($account->getID(),$date['from'],$date['to'],$perpage,$offset);

# Cheating on payment modules :)

$mods = array();
$pms = array();
foreach(UserConfig::$payment_modules as $pm) {
  $mods[$pm->getID()] = $pm->getTitle();
  $pms[$pm->getID()] = $pm;
}

foreach($log as $k => $l) {
  if(array_key_exists($l['engine_slug'],$mods)) {
    $log[$k]['engine_slug'] = $mods[$l['engine_slug']];
    $log[$k]['details'] = $pms[$l['engine_slug']]->renderTransactionLogDetails($l['transaction_id']);
  }
  else {
    $log[$k]['engine_slug'] = 'Unknown';
  }
}

$smarty->assign('log',$log);
$smarty->assign('USERSROOTURL',UserConfig::$USERSROOTURL);
