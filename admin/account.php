<?php
require_once(dirname(__FILE__).'/admin.php');

$ADMIN_SECTION = 'accounts';

include(dirname(__FILE__).'/view/account.php');

# this yields Smarty object as $smarty
$smarty->setTemplateDir(dirname(__FILE__).'/templates');
$smarty->setCompileDir(UserConfig::$smarty_compile);
$smarty->setCacheDir(UserConfig::$smarty_cache);

require_once(dirname(__FILE__).'/header.php');

$smarty->display('account.tpl');

require_once(dirname(__FILE__).'/footer.php');