<?
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/User.php');

include(dirname(__FILE__).'/view/account/account_details.php');
# this yields Smarty onbject as $smarty 
if (strstr($smarty->_version,"Smarty-3")) {
  $smarty->setTemplateDir(UserConfig::$smarty_templates.'/account');
  $smarty->setCompileDir(UserConfig::$smarty_compile);
  $smarty->setCacheDir(UserConfig::$smarty_cache);
} elseif (strstr($smarty->_version,"Smarty-2")) {
  $smarty->template_dir(UserConfig::$smarty_templates.'/account');
  $smarty->compile_dir(UserConfig::$smarty_compile);
  $smarty->cache_dir(UserConfig::$smarty_cache);
} else {
  die "Cannot handle smarty version ".$smarty->_version;
}

require_once(UserConfig::$header);

$smarty->display('account_details.tpl');

require_once(UserConfig::$footer);
