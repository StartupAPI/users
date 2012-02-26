<?php
require_once(dirname(__FILE__).'/admin.php');

$ADMIN_SECTION = 'debtors';

# this yields Smarty object as $smarty 
include(dirname(__FILE__).'/view/debtors.php');

$smarty->setTemplateDir(dirname(__FILE__).'/templates');
$smarty->setCompileDir(UserConfig::$smarty_compile);
$smarty->setCacheDir(UserConfig::$smarty_cache);

require_once(dirname(__FILE__).'/header.php');

$smarty->display('debtors.tpl');

require_once(dirname(__FILE__).'/footer.php');
