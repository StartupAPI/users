<?php
namespace StartupAPI;

require_once(__DIR__ . '/users.php');
# Load all existing modules and check, if they have cronHandler method.

foreach (UserConfig::$all_modules as $mod) {
	if (method_exists($mod, 'cronHandler')) {
		$mod->cronHandler();
	}
}
