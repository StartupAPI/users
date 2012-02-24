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

$links = array(
	'dashboard' => 'Dashboard',
	'cohorts' => 'Cohort Analysis',
	'activity' => 'Activity',
	'registrations' => 'Registered Users',
	'bymodule' => 'Registrations By Module',
	'features' => 'Features'
	);
?>
<div id="userbase_adminmenu">
<?
	$first = !UserConfig::$enableInvitations;
	foreach($links as $k => $v) {
		if(!$first)
			echo " | ";
		$first = 0;
		
		if($ADMIN_SECTION == $k)
			echo $v;
		else 
			echo "<a href=\"".UserConfig::$USERSROOTURL."/admin/".$k.".php\">".$v."</a>";
	}

	foreach (UserConfig::$all_modules as $m)
		if (method_exists($m,'renderAdminMenuItem'))
			echo $menu_item = $m->renderAdminMenuItem();
			
?>
</div>
