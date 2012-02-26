<?php
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/User.php');

User::require_login();

# this yields Smarty object as $smarty 
include(dirname(__FILE__).'/view/account/transaction_log.php');

$smarty->setTemplateDir(UserConfig::$smarty_templates.'/account');
$smarty->setCompileDir(UserConfig::$smarty_compile);
$smarty->setCacheDir(UserConfig::$smarty_cache);

require_once(UserConfig::$header);

$smarty->display('transaction_log.tpl');

require_once(UserConfig::$footer);
