<?php
require_once(dirname(__FILE__).'/global.php');

if (!UserConfig::$useSubscriptions) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

include(dirname(__FILE__).'/view/account/subscription_details.php');

if ($account->getUserRole($user) !== Account::ROLE_ADMIN) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

require_once(UserConfig::$header);

StartupAPI::$template->display('account/subscription_details.html.twig', $template_data);

require_once(UserConfig::$footer);
