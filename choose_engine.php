<?php
require_once(__DIR__.'/global.php');

if (!UserConfig::$useSubscriptions) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

$user = User::require_login();
$account = Account::getCurrentAccount($user);

if ($account->getUserRole($user) != Account::ROLE_ADMIN) {
	header('Location: ' . UserConfig::$USERSROOTURL . '/edit.php');
	exit;
}


include(__DIR__.'/view/engine/choose_engine.php');

require_once(UserConfig::$header);

StartupAPI::$template->display('engine/choose_engine.html.twig', $template_data);

require_once(UserConfig::$footer);
