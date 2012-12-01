<?php
require_once(dirname(__FILE__).'/admin.php');

$ADMIN_SECTION = 'outstanding';

# this yields Smarty object as $smarty
include(dirname(__FILE__).'/view/outstanding.php');

$smarty->setTemplateDir(dirname(__FILE__).'/templates');
$smarty->setCompileDir(UserConfig::$smarty_compile);
$smarty->setCacheDir(UserConfig::$smarty_cache);

require_once(dirname(__FILE__).'/header.php');

$smarty->display('outstanding.tpl');

require_once(dirname(__FILE__).'/footer.php');
