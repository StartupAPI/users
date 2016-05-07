<?php
require_once(__DIR__.'/global.php');

if (!UserConfig::$useSubscriptions) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

$user = User::require_login();
$account = Account::getCurrentAccount($user);
$template_info = StartupAPI::getTemplateInfo();

if (!session_id()) {
	session_start();
}

if (isset($_SESSION['message'])) {
	$template_info['message'] = $_SESSION['message'];
	unset($_SESSION['message']);
	$fatal = isset($_SESSION['fatal']) ? $_SESSION['fatal'] : 0;
	unset($_SESSION['fatal']);
	if ($fatal) {
		$template_info['fatal'] = 1;
		return;
	}
}

/*
 * Account information
 */
$template_info['account_name'] = $account->getName();
$template_info['account_isActive'] = $account->isActive();
$template_info['charges'] = $account->getCharges();
$template_info['balance'] = $account->getBalance();
$template_info['account_next_charge'] = preg_replace("/ .*/", "", $account->getNextCharge());


/*
 * Currently selected plan information
 */
$plan = $account->getPlan(); // can be FALSE
// If plan is not set, this page just does not make sense
Plan::enforcePlan($plan);

$template_info['plan_name'] = $plan->getName();
$template_info['plan_description'] = $plan->getDescription();
$template_info['plan_base_price'] = $plan->getBasePrice();
$template_info['plan_base_period'] = $plan->getBasePeriod();
$template_info['plan_details_url'] = $plan->getDetailsURL();
$template_info['plan_grace_period'] = $plan->getGracePeriod();

/*
 * Plan to downgrade to
 */
$downgrade = $plan->getDowngradeToPlan();
if ($downgrade) {
	$template_info['plan_downgrade_to_name'] = $downgrade->getName();
}

/*
 * Information about the next plan to be used when plan change request exists
 */
$next_plan = $account->getNextPlan();

if ($next_plan) {
	$template_info['next_plan_name'] = $next_plan->getName();
	$template_info['next_plan_description'] = $next_plan->getDescription();
	$template_info['next_plan_base_price'] = $next_plan->getBasePrice();
	$template_info['next_plan_base_period'] = $next_plan->getBasePeriod();
	$template_info['next_plan_details_url'] = $next_plan->getDetailsURL();
	$template_info['next_plan_grace_period'] = $next_plan->getGracePeriod();
}

/*
 * Current payment schedule information
 */
$schedule = $account->getSchedule();
if (!is_null($schedule)) {
	$template_info['schedule_name'] = $schedule->getName();
	$template_info['schedule_description'] = $schedule->getDescription();
	$template_info['schedule_charge_amount'] = $schedule->getChargeAmount();
	$template_info['schedule_charge_period'] = $schedule->getChargePeriod();
}

/*
 * Information about next payment schedule to be used when plan change request exists
 */
$next_schedule = $account->getNextSchedule();
if (!is_null($next_schedule)) {
	$template_info['next_schedule_name'] = $next_schedule->getName();
	$template_info['next_schedule_description'] = $next_schedule->getDescription();
	$template_info['next_schedule_charge_amount'] = $next_schedule->getChargeAmount();
	$template_info['next_schedule_charge_period'] = $next_schedule->getChargePeriod();
}

/*
 * Current payment engine information
 */
$engine = $account->getPaymentEngine();
$template_info['payment_engine'] = empty($engine) ? NULL : $engine->getTitle();

if ($account->getUserRole($user) !== Account::ROLE_ADMIN) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

$template_info['PAGE']['SECTION'] = 'subscription_details';
$template_info['account_name'] = $account->getName();

StartupAPI::$template->display('@startupapi/account/subscription_details.html.twig', $template_info);
