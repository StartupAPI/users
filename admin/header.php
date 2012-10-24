<?php
require_once(dirname(dirname(__FILE__)) . '/global.php');
require_once(dirname(dirname(__FILE__)) . '/User.php');
require_once(dirname(__FILE__) . '/adminMenus.php');

$current_user = User::require_login(false);

if (!$current_user->isAdmin()) {
	require_once(dirname(__FILE__) . '/admin_access_only.php');
	exit;
}

if (array_key_exists('impersonate', $_POST)) {
	if ($current_user->isTheSameAs(User::getUser($_POST['impersonate']))) {
		header('Location: #msg=cantimpersonateself');
		exit;
	}

	$impersonated_user = $current_user->impersonate(User::getUser($_POST['impersonate']));
	if ($impersonated_user !== null) {
		header('Location: ' . UserConfig::$DEFAULTLOGINRETURN);
		exit;
	} else {
		header('Location: #msg=cantimpersonate');
		exit;
	}
}

$ADMIN_ROOT = UserConfig::$USERSROOTURL . '/admin';

$features_num = count(Feature::getAll());

$admin_menu = new adminMenu(array(
			new menu('home', 'Home', $ADMIN_ROOT . '/', 'home'),
			/*
			  new menuSection('dashboards', 'Dashboards', array(
			  new menu('basic', 'Basic Metrics', $ADMIN_ROOT . '/', 'signal')
			  )),
			 */ new menuSection('users', 'Users', null, array(
				new menu('activity', 'Activity', $ADMIN_ROOT . '/activity.php', 'signal'),
				new menu('registrations', 'Registered Users', $ADMIN_ROOT . '/registrations.php', 'user'),
				new menu('cohorts', 'Cohort Analysis', $ADMIN_ROOT . '/cohorts.php', 'th'),
				new menu('bymodule', 'Registrations By Module', $ADMIN_ROOT . '/bymodule.php', 'th-large'),
				new menu('invitations', 'Invitations', $ADMIN_ROOT . '/invitations.php', 'envelope', UserConfig::$enableInvitations, 'Invitations are disabled in configuration')
			)),
			new menuSection('settings', 'Settings', null, array(
				new menu('features', 'Features', $ADMIN_ROOT . '/features.php', 'check', $features_num > 0, 'No features defined in this app'),
				new menu('templates', 'Templates', $ADMIN_ROOT . '/templates.php', 'list-alt', false),
			)),
			new menuSection('promotion', 'Promotion', null, array(
				new menu('sources', 'Sources', $ADMIN_ROOT . '/sources.php', 'random', FALSE),
				new menu('campaigns', 'Campaign management', $ADMIN_ROOT . '/campaigns.php', 'comment', FALSE)
			)),
			new menuSection('gamification', 'Gamification', null, array(
				new menu('badges', 'Badges', $ADMIN_ROOT . '/badges.php', 'star')
			))
		));

if (isset($ADMIN_SECTION)) {
	$admin_menu->setActive($ADMIN_SECTION);
}

if (!isset($BREADCRUMB_EXTRA)) {
	$BREADCRUMB_EXTRA = null;
}
?><!DOCTYPE html>
<html lang="en">
	<head>
		<title><?php echo is_null(UserConfig::$appName) ? '' : UserConfig::$appName; ?> Admin / </title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link href="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap/css/bootstrap.css" rel="stylesheet">
		<link href="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
		<script src="<?php echo UserConfig::$USERSROOTURL ?>/jquery-1.8.2.min.js"></script>
		<script src="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap/js/bootstrap.min.js"></script>
		<style>
			.startupapi-sidebar.affix {
				top: 4em;
			}

			body {
				padding-top: 40px;
			}

			.logo {
				margin-right: 0.5em;
			}

			.footer {
				padding: 70px 0;
				margin-top: 70px;
				border-top: 1px solid #E5E5E5;
				background-color: whiteSmoke;
			}
		</style>
	</head>
	<body>
		<div class="navbar">
			<div class="navbar-inner navbar-fixed-top">
				<span class="brand"><a href="<?php echo UserConfig::$SITEROOTURL ?>"><img class="logo" src="<?php echo UserConfig::$USERSROOTURL ?>/images/header_icon.png"/><?php echo is_null(UserConfig::$appName) ? '' : UserConfig::$appName; ?></a></span>

				<span></span>

				<?php $admin_menu->renderTopNav() ?>

				<ul class="nav pull-right">
					<li class="navbar-text"><?php echo $current_user->getName() ?></li>
					<li><a href="<?php echo UserConfig::$USERSROOTURL ?>/logout.php">Logout</a></li>
				</ul>
			</div>
		</div>
		<div class="container-fluid">
			<div class="row-fluid">
				<div class="span3">
					<div class="well sidebar-nav startupapi-sidebar">

						<?php $admin_menu->render() ?>

					</div>
					<!--Sidebar content-->
				</div>

				<!-- admin header ends -->

				<div class="span9">
					<?php
					$admin_menu->renderBreadCrumbs($BREADCRUMB_EXTRA);
					?>
				</div>
