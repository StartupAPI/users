<?php
require_once(__DIR__.'/global.php');

if (!UserConfig::$useSubscriptions) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

$user = User::require_login();
$account = Account::getCurrentAccount($user);

if ($account->getUserRole($user) !== Account::ROLE_ADMIN) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

include(__DIR__.'/view/plan/plans.php');

require_once(UserConfig::$header);

StartupAPI::$template->display('plan/plans.html.twig', $template_data);

require_once(UserConfig::$footer);
