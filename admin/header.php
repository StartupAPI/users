<?php
require_once(dirname(__DIR__) . '/global.php');
require_once(dirname(__DIR__) . '/classes/User.php');
require_once(__DIR__ . '/adminMenus.php');

$current_user = User::require_login(false);

if (!$current_user->isAdmin()) {
	require_once(__DIR__ . '/admin_access_only.php');
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

$admin_menu = new AdminMenu(array(
			new Menu('home', 'Home', $ADMIN_ROOT . '/', 'home'),
			/*
			  new menuSection('dashboards', 'Dashboards', array(
			  new menu('basic', 'Basic Metrics', $ADMIN_ROOT . '/', 'signal')
			  )),
			 */
			new MenuSection('users', 'Users', null, array(
				new Menu('activity', 'Activity', $ADMIN_ROOT . '/activity.php', 'signal'),
				new Menu('plans', 'Service Plans', null, 'folder-open'),
				new Menu('registrations', 'Registered Users', $ADMIN_ROOT . '/users.php', 'user'),
				new Menu('cohorts', 'Cohort Analysis', $ADMIN_ROOT . '/cohorts.php', 'th'),
				new Menu('bymodule', 'Registrations By Module', $ADMIN_ROOT . '/bymodule.php', 'th-large'),
				new Menu('invitations', 'Invitations', $ADMIN_ROOT . '/invitations.php', 'envelope', UserConfig::$adminInvitationOnly, 'Invitations are disabled in configuration'),
				new Menu('accounts', 'Accounts', $ADMIN_ROOT . '/accounts.php', 'folder-open'),
			)),
			new MenuSection('money', 'Money', null, array(
				new Menu('outstanding', 'Outstanding charges', $ADMIN_ROOT . '/outstanding.php', 'certificate', UserConfig::$useSubscriptions, 'Subscriptions are disabled in configuration'),
				new Menu('transactions', 'Transactions', null, 'list', UserConfig::$useSubscriptions),
				new Menu('payment_method', 'Payment methods', null, 'th-large', UserConfig::$useSubscriptions)
					), null, UserConfig::$useSubscriptions),
			new MenuSection('promotion', 'Promotion', null, array(
				new Menu('sources', 'Sources', $ADMIN_ROOT . '/sources.php', 'random'),
				new Menu('campaigns', 'Campaign management', $ADMIN_ROOT . '/campaigns.php', 'comment')
			)),
			new MenuSection('gamification', 'Gamification', null, array(
				new Menu('badges', 'Badges', $ADMIN_ROOT . '/badges.php', 'star')
			)),
			new MenuSection('settings', 'Settings', null, array(
				new Menu('systemsettings', 'System Settings', $ADMIN_ROOT . '/settings.php', 'wrench'),
				new Menu('modules', 'Modules', $ADMIN_ROOT . '/modules.php', 'th-large'),
				new Menu('features', 'Features', $ADMIN_ROOT . '/features.php', 'check', $features_num > 0, 'No features defined in this app')
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
		<title><?php echo is_null(UserConfig::$appName) ? '' : UserConfig::$appName; ?><?php $admin_menu->renderTitle($BREADCRUMB_EXTRA) ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<?php
		$bootstrapCSS = UserConfig::$USERSROOTURL . '/bootstrap2/css/bootstrap.min.css';
		if (!is_null(UserConfig::$bootstrapAdminCSS)) {
			$bootstrapCSS = UserConfig::$bootstrapAdminCSS;
		}
		?>
		<link href="<?php echo $bootstrapCSS ?>" rel="stylesheet">
		<link href="<?php echo $bootstrapCSS ?>" rel="stylesheet">
		<link href="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap2/css/bootstrap-responsive.min.css" rel="stylesheet">
		<script src="<?php echo UserConfig::$USERSROOTURL ?>/jquery-1.11.1.min.js"></script>
		<script src="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap2/js/bootstrap.min.js"></script>
		<style>
		/** Admin-specific styles formerly loaded from a theme */
		.startupapi-module {
			min-height: 100px;
			width: 45%;
			min-width: 30em;
		}
		.startupapi-module-not-installed {
			color: silver;
		}

		.startupapi-module-not-installed a {
			color: grey;
		}
		.startupapi-module-title {
			font-size: x-large;
			font-weight: bold;
		}
		.startupapi-module-logo {
			width: 100px;
			height: 100px;

			margin-left: 1em;
			margin-bottom: 1em;
		}
		.startupapi-module-not-installed .startupapi-module-logo {
			opacity: 0.3;
		}
		.startupapi-user-disabled {
			color: silver;
			text-decoration: line-through;
		}
		</style>
	</head>
	<body>
		<div class="navbar">
			<div class="navbar-inner">
				<a class="brand" href="<?php echo UserConfig::$SITEROOTURL ?>"><?php echo is_null(UserConfig::$appName) ? '' : UserConfig::$appName; ?></a>
				<span class="brand"><img class="startupapi-logo" width="20" height="20" src="<?php echo UserConfig::$USERSROOTURL ?>/images/header_icon.png"/></span>
				<span class="brand">Admin Panel</span>

				<span></span>

				<?php $admin_menu->renderTopNav() ?>

				<ul class="nav pull-right">
					<li><a href="<?php echo UserConfig::$USERSROOTURL ?>/edit.php" title="<?php echo UserTools::escape($current_user->getName()) ?>'s user information"><?php echo $current_user->getName() ?></a></li>
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
