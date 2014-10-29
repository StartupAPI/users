<?php
require_once(__DIR__ . '/global.php');

UserTools::preventCSRF();

$current_user = User::require_login();
$account = $current_user->getCurrentAccount();

if (is_null($account) || $account->getUserRole($current_user) != Account::ROLE_ADMIN) {
	header('Location: ' . UserConfig::$USERSROOTURL . '/manage_account.php');
	exit;
}

$template_info = StartupAPI::getTemplateInfo();

$errors = array();

$new_account_name = NULL;
$selected_plan_slug = NULL;

if (array_key_exists('account_name', $_POST)) {
	$new_account_name = trim($_POST['account_name']);
	if (empty($new_account_name)) {
		$errors['edit-account']['name'][] = "Account name can't be empty";
	} else {
		$account->setName($new_account_name);
	}

	if (count($errors['edit-account']) == 0) {
		header('Location: ' . UserConfig::$USERSROOTURL . '/manage_account.php#message=updated');
		exit;
	}
}

$template_info['errors'] = $errors;
$template_info['PAGE']['SECTION'] = 'account';
$template_info['account_name'] = $account->getName();

StartupAPI::$template->display('account/edit_account.html.twig', $template_info);