<?php
require_once(dirname(dirname(__DIR__)).'/global.php');

require_once(dirname(dirname(__DIR__)).'/classes/User.php');

$module = AuthenticationModule::get('facebook');

$module->renderAutoLogoutForm(StartupAPI::getTemplateInfo());
