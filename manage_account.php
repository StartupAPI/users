<?php
namespace StartupAPI;

require_once(__DIR__ . '/global.php');

$user = User::require_login();
$account = $user->getCurrentAccount();

UserTools::preventCSRF();

$template_info = StartupAPI::getTemplateInfo();

$template_info['account_isAdmin'] = ($account->getUserRole($user) == Account::ROLE_ADMIN);

$template_info['useSubscriptions'] = UserConfig::$useSubscriptions;

$template_info['account_id'] = $account->getID();

$template_info['account_name'] = $account->getName();
$template_info['account_isActive'] = $account->isActive();

$template_info['account_engine'] = is_null($account->getPaymentEngine()) ? 'None' : $account->getPaymentEngine()->getTitle();

$next_charge = $account->getNextCharge();
if (!is_null($next_charge)) {
	$template_info['account_next_charge'] = preg_replace("/ .*/", "", $next_charge);
}

$plan = $account->getPlan(); // can be FALSE
$template_info['planIsSet'] = $plan ? TRUE : FALSE;

if ($plan) {
	$template_info['plan_slug'] = $plan->getSlug();
	$template_info['plan_name'] = $plan->getName();
	$template_info['plan_description'] = $plan->getDescription();
	$template_info['plan_base_price'] = $plan->getBasePrice();
	$template_info['plan_base_period'] = $plan->getBasePeriod();
	$template_info['plan_details_url'] = $plan->getDetailsURL();
	$template_info['plan_grace_period'] = $plan->getGracePeriod();
}

$account_users = $account->getUsers();
uasort($account_users, function($a, $b) {
			return strcmp($a[0]->getName(), $b[0]->getName());
		});

$users = array();
$admins = array();

foreach ($account_users as $user_and_role) {
	$account_user = $user_and_role[0];
	$role = $user_and_role[1];
	$disabled = $account_user->isDisabled();

	$user_role = array(
		'id' => $account_user->getID(),
		'name' => $account_user->getName(),
		'admin' => $role ? true : false,
		'disabled' => $disabled
	);

	if ($user->isTheSameAs($account_user)) {
		$user_role['self'] = true;
	}

	$users[] = $user_role;

	if ($role) {
		$admins[] = $user_role;
	}
}

$template_info['users'] = $users;
$template_info['admins'] = $admins;

if ($account->isIndividual()) {
	$template_info['account_isIndividual'] = true;

	if (count($admins) > 0) {
		$template_info['user'] = $admins[0];
	}
} else {
	$template_info['account_isIndividual'] = false;
}

$template_info['individual_no_admins'] = false;
if ($template_info['account_isIndividual'] && count($admins) == 0) {
	$template_info['individual_no_admins'] = true;
}

$template_info['show_user_list'] = TRUE;
if ($template_info['account_isIndividual'] && count($admins) == 1 && count($users) == 1) {
	$template_info['show_user_list'] = FALSE;
};

$template_info['PAGE']['SECTION'] = 'account';

StartupAPI::$template->display('@startupapi/account/manage_account.html.twig', $template_info);
