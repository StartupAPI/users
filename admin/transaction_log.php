<?php
require_once(dirname(__FILE__).'/admin.php');

$ADMIN_SECTION = 'transaction_log';

# this yields Smarty object as $smarty 
include(dirname(__FILE__).'/view/transaction_log.php');

$smarty->setTemplateDir(dirname(__FILE__).'/templates');
$smarty->setCompileDir(UserConfig::$smarty_compile);
$smarty->setCacheDir(UserConfig::$smarty_cache);

require_once(UserConfig::$header);

$smarty->display('transaction_log.tpl');

require_once(UserConfig::$footer);
