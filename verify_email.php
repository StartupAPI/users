<?php
require_once(__DIR__ . '/global.php');

require_once(__DIR__ . '/classes/User.php');

UserConfig::$IGNORE_REQUIRED_EMAIL_VERIFICATION = true;

$user = User::get();
$email = is_null($user) ? null : $user->getEmail();

$template_info = StartupAPI::getTemplateInfo();

if (array_key_exists('code', $_GET)) {
	if (User::verifyEmailLinkCode($_GET['code'], $user)) {
		$template_info['verification_complete'] = true;
	} else {
		$template_info['errors']['code'][] = 'Invalid code';
	}
}

$template_info['email'] = $email;
$template_info['slug'] = 'verify_email';

$template_info['return'] = User::getReturn();
User::clearReturn();

if (is_null($template_info['return'])) {
	$template_info['return'] = UserConfig::$DEFAULT_EMAIL_VERIFIED_RETURN;
}
$template_info['PAGE']['SECTION'] = 'profile_info';

StartupAPI::$template->display('verify_email.html.twig', $template_info);