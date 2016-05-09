<?php
namespace StartupAPI;

require_once(dirname(dirname(__DIR__)).'/global.php');

$module = AuthenticationModule::get('facebook');

$module->renderAutoLogoutForm(StartupAPI::getTemplateInfo());
