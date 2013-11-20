<?php
/**
 * Testing if code is there
 */
// well, we got main project if we are running this code
$code_ready = true;

/**
 * Now testing for all files included from submodules
 *
 * NOTE FOR MAINTAINERS:
 * See .gitmodules for the list
 * (except for maintenance tools like PHP Tidy, for example)
 *
 * No need to test for all files, but testing only for folders is not enough
 * as git repo has folders, but until git submodule update is ran,
 * files will not be there. So testing for main file included is satisfactory.
 */
$submodules = array(
	array(
		'path' => __DIR__ . '/oauth-php/',
		'file' => __DIR__ . '/oauth-php/library/OAuthStore.php',
		'name' => 'OAuth library',
		'description' => 'A PHP library for OAuth 1.0a consumers and servers. Used to enable logins for OAuth 1.0a providers.',
		'git-repo' => 'git://github.com/sergeychernyshev/oauth-php.git',
		'url' => 'http://code.google.com/p/oauth-php/',
		'ready' => true
	),
	array(
		'path' => __DIR__ . '/modules/facebook/facebook-php-sdk/',
		'file' => __DIR__ . '/modules/facebook/facebook-php-sdk/src/base_facebook.php',
		'name' => 'Facebook SDK for PHP',
		'description' => 'The Facebook SDK for PHP provides a rich set of server-side functionality for accessing Facebook’s server-side API calls. These include all of the features of the Graph API and FQL. Used in Facebook authentication and API module.',
		'git-repo' => 'git://github.com/facebook/facebook-php-sdk.git',
		'url' => 'https://developers.facebook.com/docs/reference/php/',
		'ready' => true
	),
	array(
		'path' => __DIR__ . '/admin/swfobject/',
		'file' => __DIR__ . '/admin/swfobject/swfobject/swfobject.js',
		'name' => 'SWF Object Flash insertion script',
		'description' => 'SWFObject is a free, open-source tool for embedding swf content in websites. Used for Flash fallback detection in admin UI.',
		'git-repo' => 'git://github.com/swfobject/swfobject.git',
		'github-user' => 'swfobject',
		'github-repo' => 'swfobject',
		'url' => 'https://github.com/swfobject/swfobject',
		'ready' => true
	),
	array(
		'path' => __DIR__ . '/dbupgrade/',
		'file' => __DIR__ . '/dbupgrade/lib.php',
		'name' => 'DBUpgrade library',
		'description' => 'DBUpgrade is a simple open source tool for MySQL schema upgrade management.',
		'git-repo' => 'git://github.com/sergeychernyshev/DBUpgrade.git',
		'github-user' => 'sergeychernyshev',
		'github-repo' => 'DBUpgrade',
		'url' => 'http://www.dbupgrade.org/',
		'ready' => true
	),
	array(
		'path' => __DIR__ . '/calendarview/',
		'file' => __DIR__ . '/calendarview/javascripts/calendarview.js',
		'name' => 'Calendarview widget',
		'description' => 'A lightweight JavaScript calendar widget that follows current web standards and best practices. It was developed for use with the Prototype JavaScript framework (requires Prototype 1.6 or greater). Used for calendar pickers in admin UI.',
		'git-repo' => 'git://github.com/jsmecham/calendarview.git',
		'github-user' => 'jsmecham',
		'github-repo' => 'calendarview',
		'url' => 'http://calendarview.org/',
		'ready' => true
	),
	array(
		'path' => __DIR__ . '/twig/',
		'file' => __DIR__ . '/twig/lib/Twig/Autoloader.php',
		'name' => 'Twig templating library',
		'description' => 'The flexible, fast, and secure template engine.',
		'git-repo' => 'git://github.com/fabpot/Twig.git',
		'github-user' => 'fabpot',
		'github-repo' => 'Twig',
		'url' => 'http://twig.sensiolabs.org/',
		'ready' => true
	),
	array(
		'path' => __DIR__ . '/trunk8/',
		'file' => __DIR__ . '/trunk8/trunk8.js',
		'name' => 'trunk8 JavaScript library',
		'description' => 'trunk8 is an intelligent text truncation extension to jQuery. When applied to a large block of text, trunk8 will cut off just enough text to prevent it from spilling over.',
		'git-repo' => 'git://github.com/rviscomi/trunk8.git',
		'github-user' => 'rviscomi',
		'github-repo' => 'trunk8',
		'url' => 'https://github.com/rviscomi/trunk8',
		'ready' => true
	),
);

$code_none_ready = true;
foreach ($submodules as &$submodule) {
	if (file_exists($submodule['file'])) {
		$submodule['ready'] = true;
		$code_none_ready = false;
	} else {
		$submodule['ready'] = false;
		$code_ready = false;
	}
}

/**
 * Testing for all dependencies StartupAPI has
 * http://startupapi.org/Startup_API/Installation#Prerequisites
 */
$dependencies_ready = true;

$dependencies = array(
	'php-version' => array(
		'name' => 'PHP Version',
		'description' => 'Startup API requires PHP version 5.3.0 or above',
		'url' => 'http://php.net/releases/',
		'ready' => true
	),
	'mysqli' => array(
		'name' => 'PHP mysqli extension',
		'description' => 'PHP mysqli database extension is needed for security and performance',
		'url' => 'http://us3.php.net/manual/en/book.mysqli.php',
		'ready' => true
	),
	'mcrypt' => array(
		'name' => 'PHP Mcrypt support',
		'description' => 'PHP Mcrypt library support needed for security of user information',
		'url' => 'http://us3.php.net/manual/en/book.mcrypt.php',
		'ready' => true
	),
	'curl' => array(
		'name' => 'PHP Curl support',
		'description' => 'Startup API uses curl for API requests',
		'url' => 'http://us3.php.net/manual/en/book.curl.php',
		'ready' => true
	),
	'mbstring' => array(
		'name' => 'PHP Bultibyte string support (mbstring)',
		'description' => 'Multibyte string support is needed for managing internationalized user data',
		'url' => 'http://us3.php.net/manual/en/book.mbstring.php',
		'ready' => true
	)
);

$required_php_version = '5.3.0';
$current_php_version = phpversion();
if (!version_compare($current_php_version, $required_php_version, '>=')) {
	$dependencies_ready = false;
	$dependencies['php-version'] = false;
}

$required_extensions = array('mysqli', 'mcrypt', 'curl', 'mbstring');
$current_php_extensions = get_loaded_extensions();
foreach ($required_extensions as $extension) {
	if (!in_array($extension, $current_php_extensions)) {
		$dependencies_ready = false;
		$dependencies[$extension]['ready'] = false;
	}
}

/**
 * Testing if configuration file is in place already
 */
$config_file_ready = file_exists(dirname(__DIR__) . '/users_config.php');

$config_exception = null;
$session_secret_ready = null;
if ($code_ready && $dependencies_ready && $config_file_ready) {
	try {
		require_once __DIR__ . '/global.php';
	} catch (Exception $ex) {
		$config_exception = $ex;
	}

	/**
	 * Check if UserConfig::$SESSION_SECRET was modified from it's default value
	 * in sample config file
	 */
	$session_secret_ready = UserConfig::$SESSION_SECRET != '...type.some.random.characters.here...';
}

$config_ready = $config_file_ready && is_null($config_exception) && $session_secret_ready;

/**
 * Checking if database configuration is set and database schema matches the code
 */
$database_disabled = true;
$admin_disabled = true;

$database_ready = false;
$database_exception = null;
if ($code_ready && $dependencies_ready && $config_ready) {
	$database_disabled = false;

	try {
		$dbupgrade_interactive = false;
		require_once(__DIR__ . '/dbupgrade.php');

		$db_version = $dbupgrade->get_db_version();

		if ($db_version == max(array_keys($versions))) {
			$database_ready = true;

			// Now when database is ready, we can start registering users
			$admin_disabled = false;
		}
	} catch (DBException $ex) {
		$database_exception = $ex;
	}
}

/**
 * Setting up admin user
 */
$admin_user_ready = false;
if ($code_ready && $dependencies_ready && $config_ready && $database_ready) {
	$num_users = User::getTotalUsers();

	if (count(UserConfig::$admins) > 0) {
		$admin = User::getUser(UserConfig::$admins[0]);
		$admin_user_ready = !is_null($admin);
	}
}

/**
 * If all dependencies satisfied, go ahead and redirect to either login page
 * or to user info editing page (if user is logged in)
 */
if ($code_ready && $dependencies_ready && $config_ready && $database_ready && $admin_user_ready) {
	$current_user = StartupAPI::getUser();

	if (is_null($current_user)) {
		header('Location: ' . UserConfig::$USERSROOTURL . '/login.php');
	} else if ($current_user->isAdmin()) {
		header('Location: ' . UserConfig::$USERSROOTURL . '/admin/');
	} else {
		header('Location: ' . UserConfig::$USERSROOTURL . '/edit.php');
	}

	exit;
}
?><!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<title>Startup API Installation</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
		<link href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
		<script src="jquery-1.10.2.min.js"></script>
		<script src="bootstrap/js/bootstrap.min.js"></script>

		<link rel="stylesheet" type="text/css" href="themes/classic/startupapi.css">
	</head>

	<body>
		<header>
			<div class="navbar navbar-inverse">
				<div class="navbar-inner">
					<div class="container">
						<a class="brand" href="./index.html">Startup API Installation</a>
						<!--
						<div class="nav-collapse collapse">
							<ul class="nav">
								<li class="active">
									<a href="./index.html">Home</a>
								</li>
								<li class="">
									<a href="./getting-started.html">Get started</a>
								</li>
								<li class="">
									<a href="./scaffolding.html">Scaffolding</a>
								</li>
								<li class="">
									<a href="./base-css.html">Base CSS</a>
								</li>
								<li class="">
									<a href="./components.html">Components</a>
								</li>
								<li class="">
									<a href="./javascript.html">JavaScript</a>
								</li>
								<li class="">
									<a href="./customize.html">Customize</a>
								</li>
							</ul>
						</div>
						-->
					</div>
				</div>
			</div>
		</header>
		<div class="container-fluid" style="margin-top: 1em">
			<div class="row-fluid">
				<div class="container">
					<h1>Almost there!</h1>
					<ul class="nav nav-tabs">
						<?php
						$active = false;
						$active_section = 'code';
						?>
						<?php
						if ($dependencies_ready || $active) {
							$class = '';
						} else {
							$active_section = 'dependencies';
							$class = 'active';
							$active = true;
						}
						?>
						<li class="<?php echo $class ?>">
							<a href="#dependencies" data-toggle="tab">
								<?php if ($dependencies_ready) { ?>
									<span class="label label-success"><i class="icon-ok icon-white"></i></span>
								<?php } else { ?>
									<span class="label label-important"><i class="icon-remove icon-white"></i></span>
								<?php } ?>
								1. Dependencies
							</a>
						</li>

						<?php
						if ($code_ready || $active) {
							$class = '';
						} else {
							$active_section = 'code';
							$class = 'active';
							$active = true;
						}
						?>
						<li class="<?php echo $class ?>">
							<a href="#code" data-toggle="tab">
								<?php if ($code_ready) { ?>
									<span class="label label-success"><i class="icon-ok icon-white"></i></span>
								<?php } else { ?>
									<span class="label label-important"><i class="icon-remove icon-white"></i></span>
								<?php } ?>
								2. Get The Code
							</a>
						</li>
						<?php
						if ($config_ready || $active) {
							$class = '';
						} else {
							$active_section = 'config';
							$class = 'active';
							$active = true;
						}
						?>
						<li class="<?php echo $class ?>">
							<a href="#config" data-toggle="tab">
								<?php if ($config_ready) { ?>
									<span class="label label-success"><i class="icon-ok icon-white"></i></span>
								<?php } else { ?>
									<span class="label label-important"><i class="icon-remove icon-white"></i></span>
								<?php } ?>
								3. Create Configuration File
							</a>
						</li>
						<?php
						if ($database_ready || $active) {
							$class = '';
						} else {
							$active_section = 'database';
							$class = 'active';
							$active = true;
						}

						if ($database_disabled) {
							$class = 'disabled';
						}
						?>
						<li class="<?php echo $class ?>">
							<a href="#database" <?php if (!$database_disabled) { ?> data-toggle="tab"<?php } ?>>
								<?php if (!$database_disabled) { ?>
									<?php if ($database_ready) { ?>
										<span class="label label-success"><i class="icon-ok icon-white"></i></span>
									<?php } else { ?>
										<span class="label label-important"><i class="icon-remove icon-white"></i></span>
									<?php } ?>
								<?php } ?>
								4. Create Database Tables
							</a>
						</li>
						<?php
						if ($admin_user_ready || $active) {
							$class = '';
						} else {
							$active_section = 'admin';
							$class = 'active';
							$active = true;
						}

						if ($admin_disabled) {
							$class = 'disabled';
						}
						?>
						<li class="<?php echo $class ?>">
							<a href="#admin" <?php if (!$admin_disabled) { ?> data-toggle="tab"<?php } ?>>
								<?php if (!$admin_disabled) { ?>
									<?php if ($admin_user_ready) { ?>
										<span class="label label-success"><i class="icon-ok icon-white"></i></span>
									<?php } else { ?>
										<span class="label label-important"><i class="icon-remove icon-white"></i></span>
									<?php } ?>
								<?php } ?>
								5. Register Admin User
							</a>
						</li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane<?php if ($active_section == 'code') { ?> active<?php } ?>" id="code">
							<h3>Startup API and other code</h3>
							<?php
							if ($code_none_ready && file_exists(__DIR__ . '/.git')) {
								?>
								<div class="alert alert-error">
									<p>It looks like you are running Startup API from Git repository, but didn't retrieve any submodules.</p>
									<p>Try running the following command in <?php echo __DIR__ ?> folder:
									<pre>git submodule init && git submodule update</pre>
									</p>
								</div>
							<?php }
							?>
							<table class="table">
								<?php foreach ($submodules as $submodule) {
									?>
									<tr>
										<td>
											<?php
											if ($submodule['ready']) {
												?>
												<span class="label label-success"><i class="icon-ok icon-white"></i></span>
												<?php
											} else {
												?>
												<span class="label label-important"><i class="icon-remove icon-white"></i></span>
												<?php
											}
											?>
										</td>
										<td>
											<p class="startupapi-dep-title"><?php echo $submodule['name'] ?></p>
											<?php
											if (!$submodule['ready']) {
												?>
												<div class="alert">
													<p>You don't have required <?php echo $submodule['name'] ?> files in <span class="startupapi-file-path"><?php echo $submodule['path'] ?></span> folder.</p>
												</div>
												<?php
											}
											?>
											<p><?php echo $submodule['description'] ?></p>
											<p><i class="icon-home"></i> <a href="<?php echo $submodule['url'] ?>" target="_blank"><?php echo $submodule['url'] ?></a></p>
										</td>
									</tr>
									<?php
								}
								?>
							</table>
						</div>
						<div class="tab-pane<?php if ($active_section == 'dependencies') { ?> active<?php } ?>" id="dependencies">
							<h3>System dependencies</h3>
							<table class="table">
								<?php foreach ($dependencies as $key => $dependency) {
									?>
									<tr>
										<td>
											<?php
											if ($dependency['ready']) {
												?>
												<span class="label label-success"><i class="icon-ok icon-white"></i></span>
												<?php
											} else {
												?>
												<span class="label label-important"><i class="icon-remove icon-white"></i></span>
												<?php
											}
											?>
										</td>
										<td>
											<p class="startupapi-dep-title"><?php echo $dependency['name'] ?></p>
											<?php
											if (!$dependency['ready']) {
												?>
												<div class="alert">
													<p>You don't have <?php echo $dependency['name'] ?> installed.</p>
												</div>
												<?php
											}
											?>
											<p><?php echo $dependency['description'] ?></p>
											<p><i class="icon-home"></i> <a href="<?php echo $dependency['url'] ?>" target="_blank"><?php echo $dependency['url'] ?></a></p>
										</td>
									</tr>
									<?php
								}
								?>
							</table>
						</div>
						<div class="tab-pane<?php if ($active_section == 'config') { ?> active<?php } ?>" id="config">
							<h3>Configuration</h3>
							<table class="table">
								<tr>
									<td>
										<?php if ($config_file_ready) { ?>
											<span class="label label-success"><i class="icon-ok icon-white"></i></span>
										<?php } else { ?>
											<span class="label label-important"><i class="icon-remove icon-white"></i></span>
										<?php } ?>
									</td>
									<td>
										<p class="startupapi-dep-title">Configuration file</p>
										<?php if ($config_file_ready) { ?>
											<p>
												Found configuration file <span class="startupapi-file-path"><?php echo dirname(__DIR__) . '/users_config.php'; ?></span>
											</p>
										<?php } else { ?>
											<p>
												You need to create configuration file
												<span class="startupapi-file-path"><?php echo dirname(__DIR__) . '/users_config.php'; ?></span>!
											</p>
											<p>
												You can start by copying sample file and modifying it.
											<pre>cp "<?php echo __DIR__ . '/users_config.sample.php'; ?>" "<?php echo dirname(__DIR__) . '/users_config.php'; ?>"</pre>
											</p>
										<?php } ?>
										<p>
											You can always see all configuration settings with current values and code to include in configuration file on <a href="admin/settings.php">Settings page</a> in admin UI.
										</p>
									</td>
								</tr>
								<tr>
									<td>
										<?php if ($config_file_ready) { ?>
											<?php if (is_null($config_exception)) { ?>
												<span class="label label-success"><i class="icon-ok icon-white"></i></span>
											<?php } else { ?>
												<span class="label label-important"><i class="icon-remove icon-white"></i></span>
											<?php } ?>
										<?php } else { ?>
											<span class="label"><i class="icon-minus icon-white"></i></span>
										<?php } ?>
									</td>
									<td>
										<p class="startupapi-dep-title">Basic configuration test</p>
										<?php if ($config_file_ready) { ?>
											<?php if (is_null($config_exception)) { ?>
												<p>Configuration successful!</p>
											<?php } else { ?>
												<p>Configuration problem:</p>
												<div class="alert alert-error">
													<p><?php echo $config_exception->getMessage() ?> (Code: <?php echo $config_exception->getCode() ?>)</p>
													<pre>Stack trace:<?php echo "\n" . $config_exception->getTraceAsString() ?></pre>
												</div>
											<?php } ?>
										<?php } else { ?>
											<p>You need to create configuration file before it can be tested.</p>
										<?php } ?>
									</td>
								</tr>
								<tr>
									<td>
										<?php if ($config_file_ready) { ?>
											<?php if ($session_secret_ready) { ?>
												<span class="label label-success"><i class="icon-ok icon-white"></i></span>
											<?php } else { ?>
												<span class="label label-important"><i class="icon-remove icon-white"></i></span>
											<?php } ?>
										<?php } else { ?>
											<span class="label"><i class="icon-minus icon-white"></i></span>
										<?php } ?>
									</td>
									<td>
										<p class="startupapi-dep-title">Set <tt>UserConfig::$SESSION_SECRET</tt> variable to a long random string</p>
										<?php if ($config_file_ready) { ?>
											<?php if ($session_secret_ready) { ?>
												<p>Session secret is set</p>
											<?php } else { ?>
												<p>Session secret is still set to a sample value, change it to some random value</p>
											<?php } ?>
										<?php } else { ?>
											<p>You need to create configuration file and set <tt>UserConfig::$SESSION_SECRET</tt> variable to some random value.</p>
										<?php } ?>
									</td>
								</tr>
							</table>
						</div>
						<div class="tab-pane<?php if ($active_section == 'database') { ?> active<?php } ?>" id="database">
							<h3>Database</h3>
							<table class="table">
								<tr>
									<td>
										<?php if (is_null($database_exception)) { ?>
											<span class="label label-success"><i class="icon-ok icon-white"></i></span>
										<?php } else { ?>
											<span class="label label-important"><i class="icon-remove icon-white"></i></span>
										<?php } ?>
									</td>
									<td>
										<?php if (is_null($database_exception)) { ?>
											<p class="startupapi-dep-title">Database connection</p>
											<p>Database connection successful!</p>
										<?php } else { ?>
											<p class="startupapi-dep-title">Database connection problem</p>
											<div class="alert alert-error">
												<p><?php echo $database_exception->getMessage() ?> (Code: <?php echo $database_exception->getCode() ?>)</p>
												<pre>Stack trace:<?php echo "\n" . $database_exception->getTraceAsString() ?></pre>
											</div>
										<?php } ?>
									</td>
								</tr>
								<tr>
									<td>
										<?php if (is_null($database_exception)) { ?>
											<?php if ($database_ready) { ?>
												<span class="label label-success"><i class="icon-ok icon-white"></i></span>
											<?php } else { ?>
												<span class="label label-important"><i class="icon-remove icon-white"></i></span>
											<?php } ?>
										<?php } else { ?>
											<span class="label"><i class="icon-minus icon-white"></i></span>
										<?php } ?>
									</td>
									<td>
										<p class="startupapi-dep-title disabled">Database tables</p>
										<?php if (is_null($database_exception)) { ?>
											<?php if ($database_ready) { ?>
												<p>Database is upgraded</p>
											<?php } else { ?>
												<div class="alert">
													<p>Database is not up to date</p>
													<a href="dbupgrade.php" target="_blank" class="btn btn-primary">Update tables now</a>
												</div>
											<?php } ?>
										<?php } else { ?>
										<?php } ?>
									</td>
								</tr>
							</table>
						</div>
						<div class="tab-pane<?php if ($active_section == 'admin') { ?> active<?php } ?>" id="admin">
							<h3>Admin User</h3>
							<table class="table">
								<tr>
									<td>
										<?php
										if ($num_users > 0) {
											?>
											<span class="label label-success"><i class="icon-ok icon-white"></i></span>
											<?php
										} else {
											?>
											<span class="label label-important"><i class="icon-remove icon-white"></i></span>
											<?php
										}
										?>
									</td>
									<td>
										<p class="startupapi-dep-title">Register your first user</p>
										<?php if ($num_users == 0) { ?>
											<div class="alert">
												<p>You need to register your first user to promote them to administrator.</p>
												<a class="btn btn-primary" href="register.php" target="_blank">Register a user</a>
											</div>
										<?php } else { ?>
											<?php
											$users = User::getUsers(0, 1, null, null, null, true);
											$first_user = $users[0];
											?>
											<p>Your first user: <i class="icon-user"></i> <?php echo UserTools::escape($first_user->getName()) ?> (ID: <?php echo UserTools::escape($first_user->getID()) ?>)</p>
										<?php } ?>
									</td>
								</tr>
								<tr>
									<td>
										<?php if ($num_users > 0) { ?>
											<?php if ($admin_user_ready) { ?>
												<span class="label label-success"><i class="icon-ok icon-white"></i></span>
											<?php } else { ?>
												<span class="label label-important"><i class="icon-remove icon-white"></i></span>
											<?php } ?>
										<?php } else { ?>
											<span class="label"><i class="icon-minus icon-white"></i></span>
										<?php } ?>
									</td>
									<td>
										<p class="startupapi-dep-title disabled">Administrators</p>
										<?php if ($num_users > 0) { ?>
											<?php if ($admin_user_ready) { ?>
												<p>Administrators:
													<?php
													$admin_users = User::getUsersByIDs(UserConfig::$admins);

													$first = true;
													foreach ($admin_users as $admin) {
														if ($first) {
															?>
															,
															<?php
															$first = false;
														}
														?>
														<i class="icon-user"></i> <?php echo UserTools::escape($admin->getName()) ?> (ID: <?php echo UserTools::escape($admin->getID()) ?>)
														<?php
													}
													?>
												</p>
											<?php } else { ?>
												<div class="alert alert-error">
													<p>Add a user to a list of administrators, add the following line to config file <span class="startupapi-file-path"><?php echo dirname(__DIR__) . '/users_config.php'; ?></span></p>
													<pre>UserConfig::$admins[] = <?php echo $first_user->getID() ?>; // <?php echo UserTools::escape($first_user->getName()) ?></pre>
												</div>
											<?php } ?>
										<?php } ?>
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
