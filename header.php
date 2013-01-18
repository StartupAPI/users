<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<title>Users</title>
		<?php StartupAPI::head() ?>
	</head>

	<body>
		<header class="startupapi-header">
			<div class="startupapi-header-strip pull-right">
				<?php
				StartupAPI::power_strip();
				?>
			</div>
			<h1>
				<a href="<?php echo UserConfig::$SITEROOTURL ?>" class="startupapi-header-appname">
					<?php echo is_null(UserConfig::$appName) ? '' : UserConfig::$appName; ?>
				</a>
			</h1>
		</header>