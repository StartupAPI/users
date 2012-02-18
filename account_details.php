<?
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/User.php');

include(dirname(__FILE__).'/view/account/account_details.php');
# this yields Smarty onbject as $smarty 
$smarty->setTemplateDir(UserConfig::$smarty_templates.'/account');
$smarty->setCompileDir(UserConfig::$smarty_compile);
$smarty->setCacheDir(UserConfig::$smarty_cache);
$smarty->display('account_details.tpl');
