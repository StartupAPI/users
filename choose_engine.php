<?php
require_once(dirname(__FILE__).'/global.php');

if (!UserConfig::$useSubscriptions) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

include(dirname(__FILE__).'/view/engine/choose_engine.php');

require_once(UserConfig::$header);

StartupAPI::$template->display('engine/choose_engine.html.twig', $template_data);

require_once(UserConfig::$footer);
