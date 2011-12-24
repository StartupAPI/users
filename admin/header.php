<?php
require_once(dirname(dirname(__FILE__)).'/config.php');
require_once(dirname(dirname(__FILE__)).'/User.php');

$current_user = User::require_login(false);

if (!$current_user->isAdmin()) {
	require_once(dirname(__FILE__).'/admin_access_only.php');
	exit;
}

if (array_key_exists('impersonate', $_POST)) {
	if ($current_user->isTheSameAs(User::getUser($_POST['impersonate']))) {
		header('Location: #msg=cantimpersonateself');
		exit;
	}

	$impersonated_user= $current_user->impersonate(User::getUser($_POST['impersonate']));
	if ($impersonated_user !== null) {
		header('Location: '.UserConfig::$DEFAULTLOGINRETURN);
		exit;
	}
	else
	{
		header('Location: #msg=cantimpersonate');
		exit;
	}
}

require_once(UserConfig::$admin_header);

if (!isset($ADMIN_SECTION)) {
	$ADMIN_SECTION = null;
}

if (UserConfig::$enableInvitations) {
	?><h2>Users | <a href="invitations.php">Invitations</a></h2><?php
}
?>
<div id="userbase_adminmenu">
<?php if ($ADMIN_SECTION != 'dashboard') {
	?><a href="./">Dashboard</a><?php
} else {
	?>Dashboard<?php
} ?> |
<?php if ($ADMIN_SECTION != 'cohorts') {
	?><a href="cohorts.php">Cohort Analysis</a><?php
} else {
	?>Cohort Analysis<?php
} ?> |
<?php if ($ADMIN_SECTION != 'activity') {
	?><a href="activity.php">Activity</a><?php
} else {
	?>Activity<?php
} ?> |
<?php if ($ADMIN_SECTION != 'registrations') {
	?><a href="registrations.php">Registered Users</a><?php
} else {
	?>Registered Users<?php
} ?> |
<?php if ($ADMIN_SECTION != 'bymodule') {
	?><a href="bymodule.php">Registrations By Module</a><?php
} else {
	?>Registrations By Module<?php
} ?>
</div>
