<?php
require_once(dirname(__FILE__).'/User.php');

include(dirname(__FILE__).'/view/plan/plans.php');

User::require_login();

if (!UserConfig::$useSubscriptions) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

# this yields Smarty object as $smarty
$smarty->setTemplateDir(UserConfig::$smarty_templates.'/plan');
$smarty->setCompileDir(UserConfig::$smarty_compile);
$smarty->setCacheDir(UserConfig::$smarty_cache);

require_once(UserConfig::$header);

$smarty->display('plans.tpl');

require_once(UserConfig::$footer);
