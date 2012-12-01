<?php
require_once(dirname(__FILE__).'/global.php');
require_once(dirname(__FILE__).'/User.php');

User::require_login();

include(dirname(__FILE__).'/view/engine/choose_engine.php');

# this yields Smarty object as $smarty
$smarty->setTemplateDir(UserConfig::$smarty_templates.'/engine');
$smarty->setCompileDir(UserConfig::$smarty_compile);
$smarty->setCacheDir(UserConfig::$smarty_cache);

require_once(UserConfig::$header);

$smarty->display('choose_engine.tpl');

require_once(UserConfig::$footer);
