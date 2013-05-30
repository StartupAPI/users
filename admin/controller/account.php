<?php

require_once(dirname(__DIR__) . '/admin.php');

$account_id = htmlspecialchars($_REQUEST['id']);

if (!is_null($account = Account::getByID($account_id))) {
	if (array_key_exists('savefeatures', $_POST)) {
		$features_to_set = array();

		if (array_key_exists("feature", $_POST) && is_array($_POST['feature'])) {
			foreach (array_keys($_POST['feature']) as $featureid) {
				$feature = Feature::getByID($featureid);
				if (!is_null($feature) && $feature->isEnabled() && !$feature->isRolledOutToAllUsers()) {
					$features_to_set[] = $feature;
				}
			}
		}

		$account->setFeatures($features_to_set);
		header('Location: ' . UserConfig::$USERSROOTURL . '/admin/account.php?id=' . $account_id . '#featuressaved');
		exit;
	}

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
	exit;
} else {
	header('Location: ' . UserConfig::$USERSROOTURL . '/admin/accounts.php');
	exit;
}
