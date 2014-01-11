<?php
require_once(__DIR__ . '/admin.php');

$account_id = htmlspecialchars($_REQUEST['id']);
if (is_null($account = Account::getByID($account_id))) {
	header('Location: ' . UserConfig::$USERSROOTURL . '/admin/accounts.php');
	exit;
}

/* ------------------- Handling form submission -------------------------------------- */
UserTools::preventCSRF();

if (array_key_exists('savefeatures', $_POST)) {
	$features_to_set = array();

	if (array_key_exists("feature", $_POST) && is_array($_POST['feature'])) {
		foreach (array_keys($_POST['feature']) as $feature_id) {
			$feature = Feature::getByID($feature_id);
			if (!is_null($feature) && !$feature->isRolledOutToAllUsers()) {
				$features_to_set[] = $feature;
			}
		}
	}

	$account->setFeatures($features_to_set);

	header('Location: ' . UserConfig::$USERSROOTURL . '/admin/account.php?id=' . $account_id . '#featuressaved');
	exit;
}

if (array_key_exists('remove_user', $_POST)) {
	$user = User::getUser($_POST['remove_user']);

	if (!is_null($user)) {
		$account->removeUser($user);
	}

	header('Location: ' . UserConfig::$USERSROOTURL . '/admin/account.php?id=' . $account_id);
	exit;
}

if (array_key_exists('promote_user', $_POST) || array_key_exists('demote_user', $_POST)) {
	$admin = null;
	if (array_key_exists('promote_user', $_POST)) {
		$user_id = $_POST['promote_user'];
		$admin = true;
	}

	if (array_key_exists('demote_user', $_POST)) {
		$user_id = $_POST['demote_user'];
		$admin = false;
	}

	if (!is_null($admin)) {
		$user = User::getUser($user_id);

		if (!is_null($user)) {
			$account->setUserRole($user, $admin);
		}
	}

	header('Location: ' . UserConfig::$USERSROOTURL . '/admin/account.php?id=' . $account_id);
	exit;
}
/* ------------------- / Handling form submission -------------------------------------- */

/* ------------------- Preparing data for template ------------------------------------- */
$template_data['CSRF_NONCE'] = UserTools::$CSRF_NONCE;

$template_data['useSubscriptions'] = UserConfig::$useSubscriptions;

$template_data['account_id'] = $account_id;

$template_data['account_name'] = $account->getName();
$template_data['account_isActive'] = $account->isActive();

$template_data['account_engine'] = is_null($account->getPaymentEngine()) ? 'None' : $account->getPaymentEngine()->getTitle();
$template_data['next_account_engine'] = is_null($account->getNextPaymentEngine()) ? 'None' : $account->getNextPaymentEngine()->getTitle();

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

if ($plan && UserConfig::$useSubscriptions) {
	$downgrade = $plan->getDowngradeToPlan();
	if ($downgrade) {
		$template_data['plan_downgrade_to'] = $downgrade->getName();
		$template_data['plan_downgrade_to_slug'] = $downgrade->getSlug();
	}

	$next_plan = $account->getNextPlan();

	if ($next_plan) {
		$template_data['next_plan_slug'] = $next_plan->getSlug();
		$template_data['next_plan_name'] = $next_plan->getName();
		$template_data['next_plan_description'] = $next_plan->getDescription();
		$template_data['next_plan_base_price'] = $next_plan->getBasePrice();
		$template_data['next_plan_base_period'] = $next_plan->getBasePeriod();
		$template_data['next_plan_details_url'] = $next_plan->getDetailsURL();
		$template_data['next_plan_grace_period'] = $next_plan->getGracePeriod();
	}

	$schedule = $account->getSchedule();
	if ($schedule) {
		$template_data['schedule_name'] = $schedule->getName();
		$template_data['schedule_description'] = $schedule->getDescription();
		$template_data['schedule_charge_amount'] = $schedule->getChargeAmount();
		$template_data['schedule_charge_period'] = $schedule->getChargePeriod();
	}

	$next_schedule = $account->getNextSchedule();
	if ($next_schedule) {
		$template_data['next_schedule'] = $next_schedule->getSlug();

		$template_data['next_schedule_name'] = $next_schedule->getName();
		$template_data['next_schedule_description'] = $next_schedule->getDescription();
		$template_data['next_schedule_charge_amount'] = $next_schedule->getChargeAmount();
		$template_data['next_schedule_charge_period'] = $next_schedule->getChargePeriod();
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
/* ------------------- / Preparing data for template -------------------------------------- */

$ADMIN_SECTION = 'accounts';
$BREADCRUMB_EXTRA = $account->getName();

require_once(__DIR__ . '/header.php');

StartupAPI::$template->display('@admin/account.html.twig', $template_data);

require_once(__DIR__ . '/footer.php');