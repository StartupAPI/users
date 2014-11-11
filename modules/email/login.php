<?php
require_once(dirname(dirname(__DIR__)) . '/global.php');

require_once(dirname(dirname(__DIR__)) . '/classes/User.php');

$errors = array();

if (array_key_exists('code', $_GET) && array_key_exists('email', $_GET)) {
	$module = StartupAPIModule::get('email');

	try {
		$user = $module->processLoginLink($_GET['email'], $_GET['code']);

		if (!is_null($user)) {
			$return = User::getReturn();
			User::clearReturn();
			if (!is_null($return)) {
				header('Location: ' . $return);
			} else {
				header('Location: ' . UserConfig::$DEFAULTLOGINRETURN);
			}

			exit;
		} else {
			throw new InputValidationException('Invalid code', 0, array(
				'code' => array('Invalid code')
			));
		}
	} catch (InputValidationException $ex) {
		$errors = $ex->getErrors();
	}
}

$template_info = StartupAPI::getTemplateInfo();

if (array_key_exists('email', $_GET)) {
	$template_info['email'] = trim($_GET['email']);
}
if (array_key_exists('code', $_GET)) {
	$template_info['code'] = trim($_GET['code']);
}

$template_info['slug'] = 'emaillogin';
$template_info['errors']['emaillogin'] = $errors;

#var_export($template_info['errors']); exit;

StartupAPI::$template->display('modules/email/login.html.twig', $template_info);