<?php

$account_id = htmlspecialchars($_REQUEST['id']);
if (is_null($account = Account::getByID($account_id))) {
	$template_data['message'] = array("Can't find account with id $account_id");
	$template_data['fatal'] = 1;
	return;
}

$template_data['CSRF_NONCE'] = UserTools::$CSRF_NONCE;

$plan_data = array('slug', 'name', 'description', 'base_price', 'base_period', 'details_url', 'grace_period');

$schedule_data = array('name', 'description', 'charge_amount', 'charge_period');

$BREADCRUMB_EXTRA = $account->getName();

$template_data['useSubscriptions'] = UserConfig::$useSubscriptions;

$template_data['account_id'] = $account_id;

$template_data['account_name'] = $account->getName();
$template_data['account_isActive'] = $account->isActive();

$template_data['account_engine'] = is_null($account->getPaymentEngine()) ? 'None' : $account->getPaymentEngine()->getTitle();

$next_charge = $account->getNextCharge();
if (!is_null($next_charge)) {
	$template_data['account_next_charge'] = preg_replace("/ .*/", "", $next_charge);
}

$plan = $account->getPlan(); // can be FALSE
$template_data['planIsSet'] = $plan ? TRUE : FALSE;

if ($plan) {
	foreach ($plan_data as $d) {
		$template_data['plan_' . $d] = $plan->$d;
	}
}

if ($plan && UserConfig::$useSubscriptions) {
	$downgrade = Plan::getPlanBySlug($plan->downgrade_to);
	if ($downgrade) {
		$template_data['plan_downgrade_to'] = $downgrade->name;
		$template_data['plan_downgrade_to_slug'] = $downgrade->slug;
	}

	$next_plan = $account->getNextPlan();
	if ($next_plan) {
		foreach ($plan_data as $d) {
			$template_data['next_plan_' . $d] = $next_plan->$d;
		}
	}

	$schedule = $account->getSchedule();
	if ($schedule) {
		foreach ($schedule_data as $d) {
			$template_data['schedule_' . $d] = $schedule->$d;
		}
	}

	$schedule = $account->getNextSchedule();
	if ($schedule) {
		foreach ($schedule_data as $d) {
			$template_data['next_schedule_' . $d] = $schedule->$d;
		}
	}

	$template_data['charges'] = $account->getCharges();
	$template_data['balance'] = $account->getBalance();
}

$account_users = $account->getUsers();
uasort($account_users, function ($a, $b) {
			// sort by role first
			if ($a[1] !== $b[1]) {
				return $b[1] - $a[1];
			}

			// then sort by user name
			return strcmp($a[0]->getName(), $b[0]->getName());
		});

$users = array();
$admins = array();

foreach ($account_users as $user_and_role) {
	$user = $user_and_role[0];
	$role = $user_and_role[1];
	$disabled = $user->isDisabled();

	$user_role = array(
		'id' => $user->getID(),
		'name' => $user->getName(),
		'admin' => $role ? true : false,
		'disabled' => $disabled
	);
	$users[] = $user_role;

	if ($role) {
		$admins[] = $user_role;
	}
}
$template_data['users'] = $users;
$template_data['admins'] = $admins;
$template_data['USERSROOTURL'] = UserConfig::$USERSROOTURL;

if ($account->isIndividual()) {
	$template_data['account_isIndividual'] = true;

	if (count($admins) > 0) {
		$template_data['user'] = $admins[0];
	}
} else {
	$template_data['account_isIndividual'] = false;
}

$template_data['individual_no_admins'] = false;
if ($template_data['account_isIndividual'] && count($admins) == 0) {
	$template_data['individual_no_admins'] = true;
}

$template_data['show_user_list'] = TRUE;
if ($template_data['account_isIndividual'] && count($admins) == 1 && count($users) == 1) {
	$template_data['show_user_list'] = FALSE;
};

// features
$features = Feature::getAll();
$template_data['has_features_to_save'] = false;

$template_data['features'] = array();
foreach ($features as $id => $feature) {
	$feature_data = array();

	$feature_data['id'] = $feature->getID();
	$feature_data['name'] = $feature->getName();
	$feature_data['enabled'] = $feature->isEnabled();
	$feature_data['shut_down'] = $feature->isShutDown();
	$feature_data['rolled_out_to_all'] = $feature->isRolledOutToAllUsers();
	$feature_data['enabled_for_plan'] = ($plan ? TRUE : FALSE) && $plan->hasFeatureEnabled($feature);

	$feature_data['disable_editing'] = !$feature->isEnabled() || $feature->isShutDown()
			|| $feature->isRolledOutToAllUsers() || $feature_data['enabled_for_plan'];

	$feature_data['enabled_for_account'] = $feature->isEnabledForAccount($account, true);
	$feature_data['is_checked'] = $feature->isRolledOutToAllUsers() || $feature_data['enabled_for_plan']
			|| $feature_data['enabled_for_account'];

	$template_data['features'][] = $feature_data;

	if ($feature_data['enabled'] && !$feature_data['rolled_out_to_all'] && !$feature_data['enabled_for_plan']) {
		$template_data['has_features_to_save'] = true;
	}
}
