<?php
namespace StartupAPI;

require_once(__DIR__ . '/global.php');

UserConfig::$IGNORE_REQUIRED_EMAIL_VERIFICATION = true;

$user = User::require_login();

$email = $user->getEmail();

if (!is_null($email)) {
	$user->sendEmailVerificationCode();
}

$template_info = StartupAPI::getTemplateInfo();
$template_info['email'] = $email;
$template_info['PAGE']['SECTION'] = 'profile_info';

StartupAPI::$template->display('@startupapi/send_email_verification_code.html.twig', $template_info);
