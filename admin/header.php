<?php
require_once(dirname(dirname(__FILE__)).'/config.php');

require_once(dirname(dirname(__FILE__)).'/User.php');

$user = User::require_login();

if (!in_array($user->getID(), UserConfig::$admins)) {
	require_once(dirname(__FILE__).'/admin_access_only.php');
	exit;
}

require_once(UserConfig::$header);

if (!isset($ADMIN_SECTION)) {
	$ADMIN_SECTION = null;
}

?><h2>Users<?php if (UserConfig::$enableInvitations) { ?> | <a href="invitations.php">Invitations</a><?php } ?></h2>
<div style="background: white; padding: 0">
<h3>
<?php if ($ADMIN_SECTION != 'dashboard') {
	?><a href="./">Dashboard</a><?php
} else {
	?>Dashboard<?php
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
