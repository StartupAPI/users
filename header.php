<?php

StartupAPI::$template->display('header.html.twig', array(
	'USERSROOTURL' => UserConfig::$USERSROOTURL,
	'SITEROOTURL' => UserConfig::$SITEROOTURL,
	'APPNAME' => UserConfig::$appName,
	'HEAD' => StartupAPI::renderHeadHTML()
));
