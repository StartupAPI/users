<?php
require_once(dirname(__FILE__).'/global.php');

if (!UserConfig::$useSubscriptions) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

include(dirname(__FILE__).'/view/account/transaction_log.php');

if ($account->getUserRole($user) !== Account::ROLE_ADMIN) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

$SECTION = 'manage_account';
require_once(dirname(__FILE__) . '/sidebar_header.php');

StartupAPI::$template->display('account/transaction_log.html.twig', $template_data);

require_once(dirname(__FILE__) . '/sidebar_footer.php');