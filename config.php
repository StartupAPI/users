<?php
mb_language('uni');
mb_internal_encoding('UTF-8');
header('Content-type: text/html; charset=utf-8');

require_once(dirname(__FILE__).'/default_config.php');
require_once(dirname(dirname(__FILE__)).'/users_config.php');

// Initializing more structures based on user configurations
if(UserConfig::$useAccounts && UserConfig::$useSubscriptions) {
	Plan::init(UserConfig::$PLANS);
}
