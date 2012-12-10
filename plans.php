<?php
require_once(dirname(__FILE__).'/User.php');

include(dirname(__FILE__).'/view/plan/plans.php');

if (!UserConfig::$useSubscriptions) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

require_once(UserConfig::$header);

StartupAPI::$template->display('plan/plans.html.twig', $template_data);

require_once(UserConfig::$footer);
