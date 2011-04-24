<?php
require_once(dirname(dirname(__FILE__)).'/config.php');
require_once(dirname(dirname(__FILE__)).'/User.php');

$current_user = User::require_login();

if (!in_array($current_user->getID(), UserConfig::$admins)) {
	require_once(dirname(__FILE__).'/admin_access_only.php');
	exit;
}

if (array_key_exists('impersonate', $_POST)) {
	$impersonated_user= User::getUser($_POST['impersonate']);
	if ($impersonated_user !== null) {
		$impersonated_user->setSession(false); // always impersonate only for the browser session
		header('Location: '.UserConfig::$DEFAULTLOGINRETURN);
	}
	else
	{
		header('Location: #msg=cantimpersonate');
	}
}

require_once(UserConfig::$header);

if (!isset($ADMIN_SECTION)) {
	$ADMIN_SECTION = null;
}

if (UserConfig::$enableInvitations) {
	?><h2>Users | <a href="invitations.php">Invitations</a></h2><?php
}
?>
<div style="background: white; padding: 0">
<h3>
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
	?>Registered Users<?php
} ?>
</h3>
