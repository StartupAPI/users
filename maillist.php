<?php

require_once(__DIR__ . '/global.php');

if (!UserConfig::$maillist || !file_exists(UserConfig::$maillist)) {
	header('Location: ' . UserConfig::$USERSROOTURL . '/edit.php');
	exit;
}

$user = User::require_login();
$template_info = StartupAPI::getTemplateInfo();
$template_info['PAGE']['SECTION'] = 'maillist';

if (!is_null(UserConfig::$maillist)) {
	$template_info['maillist_html'] = file_get_contents(UserConfig::$maillist);
}

StartupAPI::$template->display('@startupapi/maillist.html.twig', $template_info);