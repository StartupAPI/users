<?php

StartupAPI::$template->display('footer.html.twig', array(
	'USERSROOTURL' => UserConfig::$USERSROOTURL,
	'SITEROOTURL' => UserConfig::$SITEROOTURL,
	'YEAR' => date('Y'),
	'APPNAME' => UserConfig::$appName
));
