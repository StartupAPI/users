<?php
require_once(dirname(__FILE__).'/global.php');

if (!UserConfig::$useSubscriptions) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

include(dirname(__FILE__).'/view/account/transaction_log.php');

require_once(UserConfig::$header);

StartupAPI::$template->display('account/transaction_log.html.twig', $template_data);

require_once(UserConfig::$footer);
