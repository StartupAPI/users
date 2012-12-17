<?php

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

$account_id = htmlspecialchars($_REQUEST['account_id']);
if (is_null($account = Account::getByID($account_id))) {
	$template_data['message'] = array("Can't find account with id $account_id");
	$template_data['fatal'] = 1;
	return;
}

$BREADCRUMB_EXTRA = $account->getName();

$template_data['account_name'] = $account->getName();
$template_data['account_id'] = $account->getID();

$current_engine = $account->getPaymentEngine();
$current_engine_slug = empty($current_engine) ? NULL : $current_engine->getSlug();

$engines = array();
foreach (UserConfig::$payment_modules as $mod) {
	$engine = array();
	$engine['slug'] = $mod->getSlug();
	$engine['title'] = $mod->getTitle();
	$engine['current'] = ($engine['slug'] == $current_engine_slug);

	$engines[] = $engine;
}

$template_data['engines'] = $engines;
$template_data['USERSROOTURL'] = UserConfig::$USERSROOTURL;
$template_data['CSRFNonce'] = UserTools::$CSRF_NONCE; // Required for POST
