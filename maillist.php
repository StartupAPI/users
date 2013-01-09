<?php
require_once(__DIR__ . '/global.php');

$user = User::require_login();

$SECTION = 'maillist';
require_once(__DIR__ . '/sidebar_header.php');

if (!is_null(UserConfig::$maillist) && file_exists(UserConfig::$maillist)) {
	include(UserConfig::$maillist);
}

require_once(__DIR__ . '/sidebar_footer.php');
