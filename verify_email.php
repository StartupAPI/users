<?php
require_once(__DIR__ . '/global.php');

require_once(__DIR__ . '/classes/User.php');

UserConfig::$IGNORE_REQUIRED_EMAIL_VERIFICATION = true;

$user = User::get();
$email = is_null($user) ? null : $user->getEmail();

$template_info = StartupAPI::getTemplateInfo();

$errors = array();

if (array_key_exists('code', $_GET)) {
	try {
		if (User::verifyEmailLinkCode($_GET['code'], $user)) {
			$template_info['verification_complete'] = true;
		} else {
			throw new InputValidationException('Invalid code', 0, array(
				'code' => 'Invalid code'
			));
		}
	} catch (InputValidationException $ex) {
		$errors = $ex->getErrors();
	}
}

$template_info['email'] = $email;
$template_info['errors']['verify_email'] = $errors;

$template_info['return'] = User::getReturn();
User::clearReturn();
if (is_null($template_info['return'])) {
	$template_info['return'] = UserConfig::$DEFAULT_EMAIL_VERIFIED_RETURN;
}

StartupAPI::$template->display('verify_email.html.twig', $template_info);