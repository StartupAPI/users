<?php
require_once(dirname(__FILE__).'/global.php');

require_once(dirname(__FILE__).'/User.php');

UserConfig::$IGNORE_REQUIRED_EMAIL_VERIFICATION = true;

// first, we need to auto-logout all modules that require that
$autologgedout_module_reached = false;
$module_logout_url = null;
foreach (UserConfig::$authentication_modules as $module) {
	if (array_key_exists('autologgedout', $_GET)) {
		// skip modules that were already autologged out
		if (!$autologgedout_module_reached && $module->getID() == $_GET['autologgedout']) {
			$autologgedout_module_reached = true;
			continue;
		}

		if ($autologgedout_module_reached) {
			$module_logout_url = $module->getAutoLogoutURL(UserConfig::$USERSROOTFULLURL.'/logout.php?autologgedout='.urlencode($module->getID()));
		}
	} else {
		// if we didn't auto-logout any modules yet, do it for first module
		$module_logout_url = $module->getAutoLogoutURL(UserConfig::$USERSROOTFULLURL.'/logout.php?autologgedout='.urlencode($module->getID()));
	}

	// if we reached a module that needs auto-logout, redirect there
	if (!is_null($module_logout_url)) {
		header('Location: '.$module_logout_url);
		exit;
	}
}

// if we're here, it means there were no auto-logout modules or all auto-logouts are complete
User::clearSession();

$user = User::get();
if (!is_null($user)) {
	$user->recordActivity(USERBASE_ACTIVITY_LOGOUT);
}

$return = User::getReturn();
User::clearReturn();

if (!is_null($return))
{
	header('Location: '.$return);
}
else
{
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
}
