<?php
require_once(__DIR__ . '/global.php');

require_once(__DIR__ . '/classes/User.php');
require_once(__DIR__ . '/classes/Invitation.php');

$errors = array();

$user_exists = false;

if (UserConfig::$enableRegistration && array_key_exists('register', $_POST)) {
	$module = AuthenticationModule::get($_GET['module']);

	if (is_null($module)) {
		throw new StartupAPIException('Wrong module specified');
	}

	$invitation = null;

	if (UserConfig::$adminInvitationOnly && !array_key_exists('invite', $_GET)) {
		throw new StartupAPIException('Invitation code is not submitted');
	}

	if ((UserConfig::$enableUserInvitations || UserConfig::$adminInvitationOnly) && array_key_exists('invite', $_GET)) {
		$code = trim($_GET['invite']);

		$invitation = Invitation::getByCode($code);

		if (is_null($invitation) || $invitation->getStatus()) {
			throw new StartupAPIException('Invitation code is invalid');
		}

		$_SESSION[UserConfig::$invitation_code_key] = $code;
	}

	try {
		$remember = false;
		$user = $module->processRegistration($_POST, $remember);

		if (is_null($user)) {
			header('Location: ' . UserConfig::$USERSROOTURL . '/register.php?module=' . $_GET['module'] . '&error=failed');
			exit;
		}

		$user->setSession($remember);

		$return = User::getReturn();
		User::clearReturn();
		if (!is_null($return)) {
			header('Location: ' . $return);
		} else {
			header('Location: ' . UserConfig::$DEFAULTREGISTERRETURN);
		}

		exit;
	} catch (InputValidationException $ex) {
		$errors[$module->getID()] = $ex->getErrors();
	} catch (ExistingUserException $ex) {
		$user_exists = true;
		$errors[$module->getID()] = $ex->getErrors();
	}
}

require_once(UserConfig::$header);
?>
<div id="startupapi-authlist">
	<h2>Sign up</h2>
	<?php
	if (UserConfig::$enableRegistration) {
		$show_registration_form = true;
		$invitation_used = null;

		if (UserConfig::$adminInvitationOnly) {
			$message = null;

			$show_registration_form = false;

			if (array_key_exists('invite', $_GET)) {
				$invitation = Invitation::getByCode($_GET['invite']);

				if (is_null($invitation) || $invitation->getStatus()) {
					$message = 'Invitation code you entered is not valid';
				} else {
					$invitation_used = $invitation;

					$show_registration_form = true;
				}
			}

			if (!$show_registration_form) {
				?>
				<form id="form" action="" method="GET">
					<fieldset>
						<legend><?php echo UserConfig::$invitationRequiredMessage ?></legend>
						<?php
						if (!is_null($message)) {
							?>
							<div class="alert alert-error"><?php echo $message ?></div>
							<?php
						}
						?>
						<input name="invite" class="input input-xlarge" value="<?php echo UserTools::escape(array_key_exists('invite', $_GET) ? $_GET['invite'] : '') ?>"/>
						<button class="btn btn-primary" type="submit">Continue &rarr;</button>
					</fieldset>
				</form>
				<?php
			}
		}

		if ($show_registration_form) {
			foreach (UserConfig::$authentication_modules as $module) {
				$id = $module->getID();
				?>
				<div style="margin-bottom: 2em">
					<h3 name="<?php echo $id ?>"><?php echo $module->getTitle() ?></h3>
					<?php
					if (array_key_exists($id, $errors) && is_array($errors[$id]) && count($errors[$id]) > 0) {
						?>
						<div class="alert alert-block alert-error fade-in">
							<h4 style="margin-bottom: 0.5em">Form errors</h4>
							<ul>
								<?php
								foreach ($errors[$id] as $field => $errorset) {
									foreach ($errorset as $error) {
										?><li><label  style="cursor: pointer" for="startupapi-<?php echo $id ?>-registration-<?php echo $field ?>"><?php echo $error ?></label></li><?php
					}
				}
								?>
							</ul>
						</div>
						<?php
					}

					$module->renderRegistrationForm(true, "?module=$id&invite=" . (is_null($invitation_used) ? '' : $invitation_used->getCode()), array_key_exists($id, $errors) ? $errors[$id] : array(), $_POST);
					?></div>
				<?php
			}
		}
	} else {
		?>
		<p><?php echo UserConfig::$registrationDisabledMessage ?></p>

		<p>If you already have an account, you can <a href="<?php echo UserConfig::$USERSROOTURL ?>/login.php">log in here</a>.</p>
		<?php
	}
	?>
</div>
<?php
require_once(UserConfig::$footer);
