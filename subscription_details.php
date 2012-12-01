<?php
require_once(dirname(__FILE__).'/global.php');
require_once(dirname(__FILE__).'/User.php');

User::require_login();

if (!UserConfig::$useSubscriptions) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

include(dirname(__FILE__).'/view/account/subscription_details.php');

# this yields Smarty object as $smarty
$smarty->setTemplateDir(UserConfig::$smarty_templates.'/account');
$smarty->setCompileDir(UserConfig::$smarty_compile);
$smarty->setCacheDir(UserConfig::$smarty_cache);

require_once(UserConfig::$header);

$smarty->display('subscription_details.tpl');

require_once(UserConfig::$footer);
