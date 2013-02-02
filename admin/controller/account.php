<?php

require_once(dirname(__DIR__) . '/admin.php');

$account_id = htmlspecialchars($_REQUEST['id']);

if (!is_null($account = Account::getByID($account_id))) {
	if (array_key_exists('remove_user', $_POST)) {
		$user = User::getUser($_POST['remove_user']);

		if (!is_null($user)) {
			$account->removeUser($user);
		}
	}

	$admin = null;
	if (array_key_exists('promote_user', $_POST)) {
		$user_id = $_POST['promote_user'];
		$admin = true;
	}

	if (array_key_exists('demote_user', $_POST)) {
		$user_id = $_POST['demote_user'];
		$admin = false;
	}

	if (!is_null($admin)) {
		$user = User::getUser($user_id);

		if (!is_null($user)) {
			$account->setUserRole($user, $admin);
		}
	}

	header('Location: ' . UserConfig::$USERSROOTURL . '/admin/account.php?id=' . $account_id);
} else {
	header('Location: ' . UserConfig::$USERSROOTURL . '/admin/accounts.php');
}

