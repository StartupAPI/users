<?php

require_once(__DIR__ . '/global.php');

require_once(__DIR__ . '/classes/User.php');
require_once(__DIR__ . '/classes/Invitation.php');

$errors = array();

$user_exists = false;

if (UserConfig::$enableRegistration && array_key_exists('register', $_POST)) {
	$module = AuthenticationModule::get($_GET['module']);

	if (is_null($module)) {
		throw new StartupAPIException('Wrong module specified');
	}

	$invitation = null;

	if (UserConfig::$adminInvitationOnly && !array_key_exists('invite', $_GET)) {
		throw new StartupAPIException('Invitation code is not submitted');
	}

	if ((UserConfig::$enableUserInvitations || UserConfig::$adminInvitationOnly)
			&& array_key_exists('invite', $_GET) && $code = trim($_GET['invite'])
	) {
		$invitation = Invitation::getByCode($code);

		if (is_null($invitation) || $invitation->getStatus()) {
			throw new StartupAPIException('Invitation code is invalid');
		}

		$storage = new MrClay_CookieStorage(array(
			'secret' => UserConfig::$SESSION_SECRET,
			'path' => UserConfig::$SITEROOTURL,
			'expire' => 0,
			'httponly' => true
		));
		$storage->store(UserConfig::$invitation_code_key, $code);
	}

	try {
		$remember = false;
		$user = $module->processRegistration($_POST, $remember);

		if (is_null($user)) {
			header('Location: ' . UserConfig::$USERSROOTURL . '/register.php?module=' . $_GET['module'] . '&error=failed');
			exit;
		}

		$user->setSession($remember);

		$return = User::getReturn();
		User::clearReturn();
		if (!is_null($return)) {
			header('Location: ' . $return);
		} else {
			header('Location: ' . UserConfig::$DEFAULTREGISTERRETURN);
		}

		exit;
	} catch (InputValidationException $ex) {
		$errors[$module->getID()] = $ex->getErrors();
	} catch (ExistingUserException $ex) {
		$user_exists = true;
		$errors[$module->getID()] = $ex->getErrors();
	}
}

$template_info = StartupAPI::getTemplateInfo();

$show_registration_form = true;
$invitation_used = null;

if (array_key_exists('invite', $_GET)) {
	$invitation = Invitation::getByCode($_GET['invite']);

	if (!is_null($invitation) && !$invitation->getStatus()) {
		$invitation_used = $invitation;
	}
}

if (UserConfig::$adminInvitationOnly) {
	$show_registration_form = false;

	if (array_key_exists('invite', $_GET)) {
		if (is_null($invitation_used)) {
			$template_info['message'] = 'Invitation code you entered is not valid';
		} else {
			$show_registration_form = true;
		}
	}

	if (array_key_exists('invite', $_GET)) {
		$template_info['invite_code'] = $_GET['invite'];
	}
}

if ($show_registration_form) {
	foreach (UserConfig::$authentication_modules as $module) {
		$id = $module->getID();
		$action_url = "?module=$id";

		if (!is_null($invitation_used)) {
			$action_url .= '&invite=' . urlencode($invitation_used->getCode());
		}
		$module_errors = array_key_exists($id, $errors) ? $errors[$id] : array();

		$template_info['module_forms'][$id] = $module->renderRegistrationForm(
				$template_info, true, $action_url, $module_errors, $_POST
		);
	}

	$template_info['show_registration_form'] = TRUE;
}

$template_info['errors'] = $errors;

StartupAPI::$template->display('@startupapi/register.html.twig', $template_info);
