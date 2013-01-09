<?php
require_once(__DIR__ . '/global.php');

require_once(__DIR__ . '/classes/User.php');

// Allow modules to auto-login (if supported)
$user = null;

foreach (UserConfig::$authentication_modules as $module) {
	$user = $module->processAutoLogin();
	if (!is_null($user)) {
		$remember = false;
		$user->setSession($remember);

		$return = User::getReturn();
		User::clearReturn();

		if (!is_null($return)) {
			header('Location: ' . $return);
		} else {
			header('Location: ' . UserConfig::$DEFAULTLOGINRETURN);
		}

		exit;
	}
}

if (array_key_exists('login', $_POST)) {
	$module = null;

	foreach (UserConfig::$authentication_modules as $module) {
		if ($module->getID() == $_GET['module']) {
			break;
		}
	}

	$remember = false;
	$user = $module->processLogin($_POST, $remember);

	if (is_null($user)) {
		header('Location: ' . UserConfig::$USERSROOTURL . '/login.php?module=' . $_GET['module'] . '&error=failed');
		exit;
	}

	$user->setSession($remember);

	$return = User::getReturn();
	User::clearReturn();
	if (!is_null($return)) {
		header('Location: ' . $return);
	} else {
		header('Location: ' . UserConfig::$DEFAULTLOGINRETURN);
	}

	exit;
}

require_once(UserConfig::$header);
?>
<div class="container-fluid" style="margin-top: 1em">
	<div class="row-fluid">
		<h2>Log in</h2>
		<?php
		foreach (UserConfig::$authentication_modules as $module) {
			$id = $module->getID();
			?>
			<div style="margin-bottom: 2em">
				<h3 name="<?php echo $id ?>"><?php echo $module->getTitle() ?></h3>
				<?php
				if (array_key_exists('module', $_GET) && $id == $_GET['module'] && array_key_exists('error', $_GET)) {
					?>
					<div class="alert alert-error">Login failed</div>
					<?php
				}

				$module->renderLoginForm("?module=$id");
				?></div>
			<?php
		}
		?>
	</div>
</div>

<?php
require_once(UserConfig::$footer);


