<?php
mb_language('uni');
mb_internal_encoding('UTF-8');
header('Content-type: text/html; charset=utf-8');

if (!session_id()) {
  session_start();
}

require_once(__DIR__.'/classes/StartupAPI.php');

require_once(__DIR__.'/default_config.php');
require_once(dirname(__DIR__).'/users_config.php');

StartupAPI::init();
