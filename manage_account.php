<?php

require_once(__DIR__ . '/global.php');
require_once(__DIR__ . '/classes/User.php');

$user = User::require_login();
$account = $user->getCurrentAccount();

if ($account->getUserRole($user) !== Account::ROLE_ADMIN) {
	header('Location: ' . UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

UserTools::preventCSRF();
$template_data['CSRF_NONCE'] = UserTools::$CSRF_NONCE;

$template_data['useSubscriptions'] = UserConfig::$useSubscriptions;

$template_data['account_id'] = $account->getID();

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
	$template_data['plan_slug'] = $plan->getSlug();
	$template_data['plan_name'] = $plan->getName();
	$template_data['plan_description'] = $plan->getDescription();
	$template_data['plan_base_price'] = $plan->getBasePrice();
	$template_data['plan_base_period'] = $plan->getBasePeriod();
	$template_data['plan_details_url'] = $plan->getDetailsURL();
	$template_data['plan_grace_period'] = $plan->getGracePeriod();
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


$SECTION = 'manage_account';
require_once(__DIR__ . '/sidebar_header.php');

StartupAPI::$template->display('account/manage_account.html.twig', $template_data);

require_once(__DIR__ . '/sidebar_footer.php');
