<?php
require_once(dirname(__FILE__).'/admin.php');

include(dirname(__FILE__).'/view/debtors.php');
# this yields Smarty object as $smarty 
if (preg_match("/^Smarty-3/",$smarty->_version)) {
  $smarty->setTemplateDir(dirname(__FILE__).'/templates');
  $smarty->setCompileDir(UserConfig::$smarty_compile);
  $smarty->setCacheDir(UserConfig::$smarty_cache);
} elseif (preg_match("/^2\./",$smarty->_version)) {
  $smarty->template_dir = dirname(__FILE__).'/templates';
  $smarty->compile_dir = UserConfig::$smarty_compile;
  $smarty->cache_dir = UserConfig::$smarty_cache;
} else {
  die("Cannot handle smarty version '".$smarty->_version."'");
}

require_once(dirname(__FILE__).'/header.php');

$smarty->display('debtors.tpl');

require_once(dirname(__FILE__).'/footer.php');
