<?php
namespace StartupAPI;

require_once(dirname(dirname(__DIR__)) . '/global.php');

UserTools::preventCSRF();

$current_user = User::require_login();
$account = $current_user->getCurrentAccount();

if (!is_null($account) && $account->getUserRole($current_user) == Account::ROLE_ADMIN) {
	if (array_key_exists('remove_user', $_POST)) {
		$user = User::getUser($_POST['remove_user']);

		if (!is_null($user) && !$user->isTheSameAs($current_user)) {
			$account->removeUser($user);

			header('Location: ' . UserConfig::$USERSROOTURL . '/manage_account.php#message=user-removed');
			exit;
		}
	}

	$user_id = null;
	$admin = null;
	if (array_key_exists('promote_user', $_POST)) {
		$user_id = $_POST['promote_user'];
		$admin = true;
	}

	if (array_key_exists('demote_user', $_POST)) {
		$user_id = $_POST['demote_user'];
		$admin = false;
	}

	if (!is_null($admin) && !is_null($user_id)) {
		$user = User::getUser($user_id);

		if (!is_null($user) && !$user->isTheSameAs($current_user)) {
			$account->setUserRole($user, $admin);

			header('Location: ' . UserConfig::$USERSROOTURL . '/manage_account.php#message=user-' . ($admin ? 'promoted' : 'demoted'));
			exit;
		}
	}
}

header('Location: ' . UserConfig::$USERSROOTURL . '/manage_account.php');
