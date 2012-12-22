<?php
require_once(dirname(__FILE__) . '/global.php');

$user = User::require_login();

$SECTION = 'maillist';
require_once(dirname(__FILE__) . '/sidebar_header.php');

if (!is_null(UserConfig::$maillist) && file_exists(UserConfig::$maillist)) {
	include(UserConfig::$maillist);
}

require_once(dirname(__FILE__) . '/sidebar_footer.php');
