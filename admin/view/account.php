<?php

require_once(dirname(dirname(__FILE__)) . '/admin.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/smarty/libs/Smarty.class.php');

$smarty = new Smarty();

$account_id = htmlspecialchars($_REQUEST['id']);
if (is_null($account = Account::getByID($account_id))) {
	$smarty->assign('message', array("Can't find account with id $account_id"));
	$smarty->assign('fatal', 1);
	return;
}

// UserTools::debug("Account: " . var_export($account, true));

$plan_data = array('name', 'description', 'base_price', 'base_period', 'details_url', 'grace_period');

$schedule_data = array('name', 'description', 'charge_amount', 'charge_period');

$BREADCRUMB_EXTRA = $account->getName();

$smarty->assign('useSubscriptions', UserConfig::$useSubscriptions);

$smarty->assign('account_id', $account_id);

$smarty->assign('account_name', $account->getName());
$smarty->assign('account_isActive', $account->isActive());
$smarty->assign('account_engine', is_null($account->getPaymentEngine()) ? 'None' : $account->getPaymentEngine()->getTitle());
$smarty->assign('account_next_charge', preg_replace("/ .*/", "", $account->getNextCharge()));

if (UserConfig::$useSubscriptions) {
	$plan = $account->getPlan();

	foreach ($plan_data as $d) {
		$smarty->assign('plan_' . $d, $plan->$d);
	}

	$downgrade = Plan::getPlanBySlug($plan->downgrade_to);
	if ($downgrade) {
		$smarty->assign('plan_downgrade_to', $downgrade->name);
	}

	$next_plan = $account->getNextPlan();
	if ($next_plan) {
		foreach ($plan_data as $d) {
			$smarty->assign('next_plan_' . $d, $next_plan->$d);
		}
	}

	$schedule = $account->getSchedule();
	if ($schedule) {
		foreach ($schedule_data as $d) {
			$smarty->assign('schedule_' . $d, $schedule->$d);
		}
	}

	$schedule = $account->getNextSchedule();
	if ($schedule) {
		foreach ($schedule_data as $d) {
			$smarty->assign('next_schedule_' . $d, $schedule->$d);
		}
	}

	$smarty->assign('charges', $account->getCharges());
	$smarty->assign('balance', $account->getBalance());
}

$acctount_users = $account->getUsers();
$users = array();

uasort($acctount_users, function($a, $b) {
			// sort by role first
			if ($a[1] !== $b[1]) {
				return $b[1] - $a[1];
			}

			// then sort by user name
			return strcmp($a[0]->getName(), $b[0]->getName());
		});

foreach ($acctount_users as $user_and_role) {
	$user = $user_and_role[0];
	$role = $user_and_role[1];

	$users[] = array('id' => $user->getID(), 'name' => $user->getName(), 'admin' => $role ? true : false);
}
$smarty->assign('users', $users);
$smarty->assign('USERSROOTURL', UserConfig::$USERSROOTURL);
