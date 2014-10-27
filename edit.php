<?php
require_once(__DIR__ . '/global.php');
require_once(__DIR__ . '/classes/User.php');

UserConfig::$IGNORE_REQUIRED_EMAIL_VERIFICATION = true;

$user = User::require_login();

UserTools::preventCSRF();

$template_info = StartupAPI::getTemplateInfo();

$current_module = null;
if (array_key_exists('module', $_GET)) {
	foreach (UserConfig::$authentication_modules as $current_module) {
		if ($current_module->getID() == $_GET['module']) {
			break;
		}
	}
}

if (is_null($current_module)) {
	$template_info['PAGE']['SECTION'] = 'profile_info';
	$compact_page = false;
} else {
	$compact_page = $current_module->isCompact();
	$template_info['PAGE']['SECTION'] = 'login_' . $current_module->getID();
	$template_info['current_module']['id'] = $current_module->getID();
}

$template_info['compact_page'] = $compact_page;

$data = array();
$errors = array();
if (array_key_exists('save', $_POST)) {
	if (array_key_exists('module', $_GET)) {
		try {
			if ($current_module->processEditUser($user, $_POST)) {
				header('Location: ' . UserConfig::$USERSROOTURL . '/edit.php?module=' . $_GET['module'] . '#saved');
			} else {
				header('Location: ' . UserConfig::$USERSROOTURL . '/edit.php?module=' . $_GET['module'] . '&error=failed');
			}

			exit;
		} catch (InputValidationException $ex) {
			$errors[$current_module->getID()] = $ex->getErrors();
		} catch (ExistingUserException $ex) {
			$user_exists = true;
			$errors[$current_module->getID()] = $ex->getErrors();
		}
	} else {
		$data = $_POST;

		if (array_key_exists('name', $data)) {
			$name = trim(mb_convert_encoding($data['name'], 'UTF-8'));
			if ($name == '') {
				$errors['profile-info']['name'][] = "Name can't be empty";
			}
		} else {
			$errors['profile-info']['name'][] = 'No name specified';
		}

		if (array_key_exists('email', $data)) {
			$email = trim(mb_convert_encoding($data['email'], 'UTF-8'));
			if (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) {
				$errors['profile-info']['email'][] = 'Invalid email address';
			}
		} else {
			$errors['profile-info']['email'][] = 'No email specified';
		}

		$existing_users = User::getUsersByEmailOrUsername($email);
		if (!array_key_exists('email', $errors['profile-info']) &&
				(count($existing_users) > 0 && !$existing_users[0]->isTheSameAs($user))
		) {
			$errors['profile-info']['email'][] = "This email is already used by another user, please enter another email address.";
		}

		if (!array_key_exists('profile-info', $errors) || count($errors['profile-info']) == 0) {
			$user->setName($name);
			$user->setEmail($email);
			$user->save();

			# TODO register activity and record it here
			#$user->recordActivity(USERBASE_ACTIVITY_UPDATEUSERINFO);

			header('Location: ' . UserConfig::$USERSROOTURL . '/edit.php');
			exit;
		}
	}
}

if (!is_null($current_module)) {
	foreach (UserConfig::$authentication_modules as $module) {
		$id = $module->getID();

		if (($compact_page && !$module->isCompact()) || (!$compact_page && $current_module->getID() != $id)) {
			continue;
		}

		// capturing form HTMLs for each module
		ob_start();
		$module->renderEditUserForm("?module=$id", array_key_exists($id, $errors) ? $errors[$id] : array(), $user, $_POST);
		$template_info['module_forms'][$id] = ob_get_contents();
		ob_end_clean();
	}
}

$template_info['errors'] = $errors;

StartupAPI::$template->display('edit.html.twig', $template_info);