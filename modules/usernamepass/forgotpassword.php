<?php

require_once(dirname(dirname(__DIR__)) . '/global.php');

require_once(dirname(dirname(__DIR__)) . '/classes/User.php');

if (array_key_exists('recover', $_POST)) {
	$users = User::getUsersByEmailOrUsername(mb_convert_encoding($_POST['emailorusername'], 'UTF-8'));

	$subject = UserConfig::$passwordRecoveryEmailSubject;

	if (!is_null(UserConfig::$onRenderTemporaryPasswordEmail)) {
		$baseurl = UserConfig::$USERSROOTFULLURL . '/login.php';

		foreach ($users as $user) {
			$temppass = $user->generateTemporaryPassword();
			$tempass_enc = urlencode($temppass);

			$username = $user->getUsername();
			$name_enc = urlencode($username);

			$email = $user->getEmail();
			$name = $user->getName();

			$message_body = call_user_func_array(UserConfig::$onRenderTemporaryPasswordEmail, array($baseurl, $username, $temppass));

			$message = Swift_Message::newInstance($subject, $message_body);
			$message->setFrom(array(UserConfig::$supportEmailFromEmail => UserConfig::$supportEmailFromName));
			$message->setTo(array($email => $name));
			$message->setReplyTo(array(UserConfig::$supportEmailReplyTo));

			$headers = $message->getHeaders();
			$headers->addTextHeader('X-Mailer', UserConfig::$supportEmailXMailer);

			UserConfig::$mailer->send($message);
		}

		// We always report "sent" to avoid information disclosure
		// e.g. letting hackers know which usernames and emails are available
		header('Location: ' . UserConfig::$USERSROOTURL . '/modules/usernamepass/forgotpassword.php?status=sent');

		exit;
	} else {
		throw new StartupAPIException('Can\'t render temporary password email, check if UserConfig::$onRenderTemporaryPasswordEmail is set');
	}
}

$template_info = StartupAPI::getTemplateInfo();

if (array_key_exists('status', $_GET) && $_GET['status'] == 'sent') {
	$template_info['sent'] = TRUE;
}

$template_info['slug'] = 'usernamepass';

StartupAPI::$template->display('@startupapi/modules/usernamepass/forgotpassword.html.twig', $template_info);
