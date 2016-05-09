<?php
namespace StartupAPI;

require_once(__DIR__ . '/global.php');

// Allow modules to auto-login (if supported)
$user = null;

foreach (UserConfig::$authentication_modules as $module) {
	$user = $module->processAutoLogin();
	if (!is_null($user)) {
		$remember = false;
		$user->setSession($remember);

		$return = User::getReturn();
		User::clearReturn();

		if (!is_null($return)) {
			header('Location: ' . $return);
		} else {
			header('Location: ' . UserConfig::$DEFAULTLOGINRETURN);
		}

		exit;
	}
}

if (array_key_exists('login', $_POST)) {
	$module = null;

	foreach (UserConfig::$authentication_modules as $module) {
		if ($module->getID() == $_GET['module']) {
			break;
		}
	}

	$remember = false;
	$user = $module->processLogin($_POST, $remember);

	if (is_null($user)) {
		header('Location: ' . UserConfig::$USERSROOTURL . '/login.php?module=' . $_GET['module'] . '&error=failed');
		exit;
	}

	$user->setSession($remember);

	$return = User::getReturn();
	User::clearReturn();
	if (!is_null($return)) {
		header('Location: ' . $return);
	} else {
		header('Location: ' . UserConfig::$DEFAULTLOGINRETURN);
	}

	exit;
}

$template_info = StartupAPI::getTemplateInfo();

foreach (UserConfig::$authentication_modules as $module) {
	$id = $module->getID();

	$template_info['module_forms'][$id] = $module->renderLoginForm($template_info, "?module=$id");
}

if (array_key_exists('module', $_GET) && $id == $_GET['module'] && array_key_exists('error', $_GET)) {
	$template_info['login_failed'][$id] = TRUE;
}

StartupAPI::$template->display('@startupapi/login.html.twig', $template_info);
