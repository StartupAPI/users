<?php
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

require_once(dirname(dirname(dirname(__FILE__))).'/User.php');

$module = AuthenticationModule::get('facebook');

$module->renderAutoLogoutForm();
