<?php
namespace StartupAPI;

require_once(__DIR__ . '/global.php');

$user = User::require_login();

UserTools::preventCSRF();

$template_info = StartupAPI::getTemplateInfo();

$current_account = null;
$template_info['can_invite_to_account'] = false;

$current_account = $user->getCurrentAccount();
if (!$current_account->isIndividual()
		&& $current_account->getUserRole($user) === Account::ROLE_ADMIN
) {
	$template_info['can_invite_to_account'] = true;
}

$errors = array();

if (array_key_exists('send', $_POST)) {
	$invitation_name = trim($_POST['invitation_name']);
	if (empty($invitation_name)) {
		$errors['name'][] = "Please provide persons name";
	}

	$invitation_email = trim($_POST['invitation_email']);
	if (empty($invitation_email)) {
		$errors['email'][] = "Please provide persons email";
	}

	$invitation_note = trim($_POST['invitation_note']);

	$invite_to_account = null;
	if ($template_info['can_invite_to_account'] && array_key_exists('invite_to_account', $_POST)) {
		// can only invite to user's current account
		$invite_to_account = $current_account;
	}

	if (count($errors) == 0) {
		Invitation::sendUserInvitation($user, $invitation_name, $invitation_email, $invitation_note, $invite_to_account);
		header("Location: #message=sent");
		exit;
	}
}

if (array_key_exists('resend', $_POST)) {
	foreach (array_keys($_POST['resend']) as $code) {
		$invitation = Invitation::getByCode($code);

		if ($invitation->getIssuer()->isTheSameAs($user)) {
			$invitation->send();
			header("Location: #message=resent");
			exit;
		} else {
			header("Location: #message=wrongissuer");
			exit;
		}
	}
}

if (array_key_exists('cancel', $_POST)) {
	foreach (array_keys($_POST['cancel']) as $code) {
		$invitation = Invitation::getByCode($code);

		if ($invitation->getIssuer()->isTheSameAs($user)) {
			$invitation->cancel();
			header("Location: #message=cancelled");
			exit;
		} else {
			header("Location: #message=wrongissuer");
			exit;
		}
	}
}

$template_info['PAGE']['SECTION'] = 'invitations';
$template_info['errors']['invitations'] = $errors;
$template_info['message_placeholder'] = call_user_func_array(UserConfig::$onRenderUserInvitationMessagePlaceholder, array($user));
$template_info['join_account'] = array_key_exists('joinaccount', $_GET);

$now = time();
foreach ($user->getSentInvitations() as $invitation) {
	$template_info['sent_invitations'][] = array(
		'code' => $invitation->getCode(),
		'email' => trim($invitation->getSentToEmail()),
		'email_spaceencoded' => UserTools::spaceencode(trim($invitation->getSentToEmail())),
		'name' => $invitation->getSentToName(),
		'note' => trim($invitation->getNote()),
		'created' => date('M j, h:iA', $invitation->getTimeCreated()),
		'ago' => intval(floor(($now - $invitation->getTimeCreated()) / 86400))
	);
}

foreach ($user->getAcceptedInvitations() as $invitation) {
	$template_info['accepted_invitations'][] = array(
		'code' => $invitation->getCode(),
		'invited_name' => $invitation->getUser()->getName(),
		'email' => trim($invitation->getSentToEmail()),
		'email_spaceencoded' => UserTools::spaceencode(trim($invitation->getSentToEmail())),
		'name' => $invitation->getSentToName(),
		'note' => trim($invitation->getNote()),
		'created' => date('M j, h:iA', $invitation->getTimeCreated()),
		'ago' => intval(floor(($now - $invitation->getTimeCreated()) / 86400))
	);
}

StartupAPI::$template->display('@startupapi/invitations.html.twig', $template_info);
