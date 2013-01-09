<?php
require_once(__DIR__ . '/global.php');
require_once(__DIR__ . '/classes/User.php');

UserConfig::$IGNORE_REQUIRED_EMAIL_VERIFICATION = true;

$user = User::require_login();

UserTools::preventCSRF();

$errors = array();

$current_module = UserConfig::$authentication_modules[0];
if (array_key_exists('module', $_GET)) {
	foreach (UserConfig::$authentication_modules as $current_module) {
		if ($current_module->getID() == $_GET['module']) {
			break;
		}
	}
}

$compact_page = $current_module->isCompact();

$SECTION = 'login_' . $current_module->getID();

if (is_null($current_module)) {
	throw new StartupAPIException('Wrong module specified');
}

if (array_key_exists('save', $_POST)) {
	try {
		if ($current_module->processEditUser($user, $_POST)) {
			header('Location: ' . UserConfig::$USERSROOTURL . '/edit.php?module=' . $_GET['module']);
		} else {
			header('Location: ' . UserConfig::$USERSROOTURL . '/edit.php?module=' . $_GET['module'] . '&error=failed');
		}

		exit;
	} catch (InputValidationException $ex) {
		$errors[$current_module->getID()] = $ex->getErrors();
	} catch (ExistingUserException $ex) {
		$user_exists = true;
		$errors[$current_module->getID()] = $ex->getErrors();
	}
}

require_once(__DIR__ . '/sidebar_header.php');
?>
<div>
	<?php if ($compact_page) { ?>
		<legend>Connect other accounts</legend>
		<?php
	}
	foreach (UserConfig::$authentication_modules as $module) {
		$id = $module->getID();

		if (($compact_page && !$module->isCompact())
				|| (!$compact_page && $current_module->getID() != $id)) {
			continue;
		}
		?>
		<div>
			<a name="<?php echo $id ?>"></a>
			<?php
			if (array_key_exists($id, $errors) && is_array($errors[$id]) && count($errors[$id]) > 0) {
				?>
				<div class="alert alert-block alert-error fade-in">
					<h4 style="margin-bottom: 0.5em">Snap!</h4>

					<ul>
						<?php
						foreach ($errors[$id] as $field => $errorset) {
							foreach ($errorset as $error) {
								?><li><label  style="cursor: pointer" for="startupapi-<?php echo $id ?>-edit-<?php echo $field ?>"><?php echo $error ?></label></li><?php
			}
		}
						?>
					</ul>
				</div>
				<?php
			}
			?>
			<div style="margin-bottom: 2em">
				<?php
				$module->renderEditUserForm("?module=$id", array_key_exists($id, $errors) ? $errors[$id] : array(), $user, $_POST);
				?>
			</div>
		</div>
		<?php
	}
	?>
</div>

<?php
require_once(__DIR__ . '/sidebar_footer.php');
