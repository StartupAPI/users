<?php
require_once(dirname(dirname(dirname(__FILE__))).'/global.php');

require_once(dirname(dirname(dirname(__FILE__))).'/User.php');

$module = AuthenticationModule::get('facebook');

$module->renderAutoLogoutForm();
