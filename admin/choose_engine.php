<?php
require_once(dirname(__FILE__).'/admin.php');

$ADMIN_SECTION = 'engine';

include(dirname(__FILE__).'/view/choose_engine.php');

# this yields Smarty object as $smarty 
$smarty->setTemplateDir(dirname(__FILE__).'/templates');
$smarty->setCompileDir(UserConfig::$smarty_compile);
$smarty->setCacheDir(UserConfig::$smarty_cache);

require_once(dirname(__FILE__).'/header.php');

$smarty->display('choose_engine.tpl');

require_once(dirname(__FILE__).'/footer.php');
