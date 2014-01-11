<?php

$user = User::require_login();
$account = Account::getCurrentAccount($user);

session_start();
if (isset($_SESSION['message'])) {
	$template_data['message'] = $_SESSION['message'];
	unset($_SESSION['message']);
	$fatal = isset($_SESSION['fatal']) ? $_SESSION['fatal'] : 0;
	unset($_SESSION['fatal']);
	if ($fatal) {
		$template_data['fatal'] = 1;
		return;
	}
}

/*
 * System information
 */
$template_data['USERSROOTURL'] = UserConfig::$USERSROOTURL;

/*
 * Account information
 */
$template_data['account_name'] = $account->getName();
$template_data['account_isActive'] = $account->isActive();
$template_data['charges'] = $account->getCharges();
$template_data['balance'] = $account->getBalance();
$template_data['account_next_charge'] = preg_replace("/ .*/", "", $account->getNextCharge());


/*
 * Currently selected plan information
 */
$plan = $account->getPlan(); // can be FALSE
// If plan is not set, this page just does not make sense
Plan::enforcePlan($plan);

$template_data['plan_name'] = $plan->getName();
$template_data['plan_description'] = $plan->getDescription();
$template_data['plan_base_price'] = $plan->getBasePrice();
$template_data['plan_base_period'] = $plan->getBasePeriod();
$template_data['plan_details_url'] = $plan->getDetailsURL();
$template_data['plan_grace_period'] = $plan->getGracePeriod();

/*
 * Plan to downgrade to
 */
$downgrade = $plan->getDowngradeToPlan();
if ($downgrade) {
	$template_data['plan_downgrade_to_name'] = $downgrade->getName();
}

/*
 * Information about the next plan to be used when plan change request exists
 */
$next_plan = $account->getNextPlan();

if ($next_plan) {
	$template_data['next_plan_name'] = $next_plan->getName();
	$template_data['next_plan_description'] = $next_plan->getDescription();
	$template_data['next_plan_base_price'] = $next_plan->getBasePrice();
	$template_data['next_plan_base_period'] = $next_plan->getBasePeriod();
	$template_data['next_plan_details_url'] = $next_plan->getDetailsURL();
	$template_data['next_plan_grace_period'] = $next_plan->getGracePeriod();
}

/*
 * Current payment schedule information
 */
$schedule_data = array('name', 'description', 'charge_amount', 'charge_period');

$schedule = $account->getSchedule();
if (!is_null($schedule)) {
	foreach ($schedule_data as $d) {
		$template_data['schedule_' . $d] = $schedule->$d;
	}
}

/*
 * Information about next payment schedule to be used when plan change request exists
 */
$schedule = $account->getNextSchedule();
if (!is_null($schedule)) {
	foreach ($schedule_data as $d) {
		$template_data['next_schedule_' . $d] = $schedule->$d;
	}
}

/*
 * Current payment engine information
 */
$engine = $account->getPaymentEngine();
$template_data['payment_engine'] = empty($engine) ? NULL : $engine->getTitle();
