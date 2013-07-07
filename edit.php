<?php
require_once(__DIR__ . '/global.php');
require_once(__DIR__ . '/classes/User.php');

UserConfig::$IGNORE_REQUIRED_EMAIL_VERIFICATION = true;

$user = User::require_login();

UserTools::preventCSRF();

$errors = array();

function showErrors($id, $errors) {
	if (array_key_exists($id, $errors) && is_array($errors[$id]) && count($errors[$id]) > 0) {
		?>
		<div class="alert alert-block alert-error fade-in">
			<h4 style="margin-bottom: 0.5em">Snap!</h4>

			<ul>
				<?php
				foreach ($errors[$id] as $field => $errorset) {
					foreach ($errorset as $error) {
						?>
						<li>
							<label  style="cursor: pointer" for="startupapi-<?php echo $id ?>-edit-<?php echo $field ?>">
								<?php echo $error ?>
							</label>
						</li>
						<?php
					}
				}
				?>
			</ul>
		</div>
		<?php
	}
}

$current_module = null;
if (array_key_exists('module', $_GET)) {
	foreach (UserConfig::$authentication_modules as $current_module) {
		if ($current_module->getID() == $_GET['module']) {
			break;
		}
	}
}


if (is_null($current_module)) {
	$SECTION = 'profile_info';
	$compact_page = false;
} else {
	$compact_page = $current_module->isCompact();
	$SECTION = 'login_' . $current_module->getID();
}

$data = array();

if (array_key_exists('save', $_POST)) {
	if (array_key_exists('module', $_GET)) {
		try {
			if ($current_module->processEditUser($user, $_POST)) {
				header('Location: ' . UserConfig::$USERSROOTURL . '/edit.php?module=' . $_GET['module'] . '#saved');
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
	} else {
		$data = $_POST;

		if (array_key_exists('name', $data)) {
			$name = trim(mb_convert_encoding($data['name'], 'UTF-8'));
			if ($name == '') {
				$errors['profile-info']['name'][] = "Name can't be empty";
			}
		} else {
			$errors['profile-info']['name'][] = 'No name specified';
		}

		if (array_key_exists('email', $data)) {
			$email = trim(mb_convert_encoding($data['email'], 'UTF-8'));
			if (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) {
				$errors['profile-info']['email'][] = 'Invalid email address';
			}
		} else {
			$errors['profile-info']['email'][] = 'No email specified';
		}

		$existing_users = User::getUsersByEmailOrUsername($email);
		if (!array_key_exists('email', $errors['profile-info']) &&
				(count($existing_users) > 0 && !$existing_users[0]->isTheSameAs($user))
		) {
			$errors['profile-info']['email'][] = "This email is already used by another user, please enter another email address.";
		}

		if (!array_key_exists('profile-info', $errors) || count($errors['profile-info']) == 0) {
			$user->setName($name);
			$user->setEmail($email);
			$user->save();

			# TODO register activity and record it here
			#$user->recordActivity(USERBASE_ACTIVITY_UPDATEUSERINFO);

			header('Location: ' . UserConfig::$USERSROOTURL . '/edit.php');

			exit;
		}
	}
}

if (!is_null($current_module)) {
	$module_forms = array();
	foreach (UserConfig::$authentication_modules as $module) {
		$id = $module->getID();

		if (($compact_page && !$module->isCompact())
				|| (!$compact_page && $current_module->getID() != $id)) {
			continue;
		}

		// capturing form HTMLs for each module
		ob_start();
		$module->renderEditUserForm("?module=$id", array_key_exists($id, $errors) ? $errors[$id] : array(), $user, $_POST);
		$module_forms[$id] = ob_get_contents();
		ob_end_clean();
	}
}

require_once(__DIR__ . '/sidebar_header.php');
?>
<div>
	<?php
	if (is_null($current_module)) {
		?>
		<legend>Profile Information</legend>

		<?php showErrors('profile-info', $errors); ?>

		<form class="form-horizontal" action="" method="POST">
			<fieldset>
				<div class="control-group<?php if (array_key_exists('name', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['name'])) ?><?php } ?>">
					<label class="control-label" for="startupapi-profile-info-edit-name">Name</label>
					<div class="controls">
						<input id="startupapi-profile-info-edit-name" name="name" type="text" value="<?php echo UserTools::escape(array_key_exists('name', $data) ? $data['name'] : $user->getName()) ?>"/>
					</div>
				</div>

				<div class="control-group<?php if (array_key_exists('email', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['email'])) ?><?php } ?>">
					<label class="control-label" for="startupapi-profile-info-edit-email">Email</label>
					<div class="controls">
						<input id="startupapi-profile-info-edit-email" name="email" type="email" value="<?php echo UserTools::escape(array_key_exists('email', $data) ? $data['email'] : $user->getEmail()) ?>"/>
						<?php if ($user->getEmail() && !$user->isEmailVerified()) { ?><a id="startupapi-usernamepass-edit-verify-email" href="<?php echo UserConfig::$USERSROOTURL ?>/verify_email.php">Email address is not verified yet, click here to verify</a><?php } ?>
					</div>
				</div>

				<div class="control-group">
					<div class="controls">
						<button class="btn btn-primary" type="submit" name="save">Save changes</button>
					</div>
				</div>
			</fieldset>
			<?php UserTools::renderCSRFNonce(); ?>
		</form>
		<?php
	} else {
		if ($compact_page) {
			?>
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
			<?php showErrors($id, $errors); ?>

			<div>
				<a name="<?php echo $id ?>"></a>
				<?php
				?>
				<div style="margin-bottom: 2em">
					<?php echo $module_forms[$id] ?>
				</div>
			</div>
			<?php
		}
	}
	?>
</div>

<?php
require_once(__DIR__ . '/sidebar_footer.php');