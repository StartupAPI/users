<?php
namespace StartupAPI;

require_once(dirname(__DIR__).'/users.php');

UserTools::preventCSRF();
