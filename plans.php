<?php
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/User.php');

include(dirname(__FILE__).'/view/account/plans.php');

User::require_login();

# this yields Smarty object as $smarty 
$smarty->setTemplateDir(UserConfig::$smarty_templates.'/account');
$smarty->setCompileDir(UserConfig::$smarty_compile);
$smarty->setCacheDir(UserConfig::$smarty_cache);

require_once(UserConfig::$header);

$smarty->display('plans.tpl');

require_once(UserConfig::$footer);
