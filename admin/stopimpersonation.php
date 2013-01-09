<?php
require_once(__DIR__.'/admin.php');

$real_user = User::get(false); // getting real user, ignoring impersonation

if (!$real_user || !$real_user->isAdmin()) {
	header('Location: '.UserConfig::$DEFAULTLOGINRETURN);
	exit;
}

$impersonated_user = User::get(); // now getting user even if he's impersonated

if (!is_null($impersonated_user) &&
	!$impersonated_user->isTheSameAs($real_user) &&
	$impersonated_user->isImpersonated()
) {
	// If impersonating, stop
	User::stopImpersonation();
}

header('Location: '.UserConfig::$USERSROOTURL.'/admin/');
