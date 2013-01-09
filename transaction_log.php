<?php
require_once(__DIR__.'/global.php');

if (!UserConfig::$useSubscriptions) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

include(__DIR__.'/view/account/transaction_log.php');

if ($account->getUserRole($user) !== Account::ROLE_ADMIN) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

$SECTION = 'manage_account';
require_once(__DIR__ . '/sidebar_header.php');

StartupAPI::$template->display('account/transaction_log.html.twig', $template_data);

require_once(__DIR__ . '/sidebar_footer.php');