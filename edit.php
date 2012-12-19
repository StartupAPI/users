<?php
require_once(dirname(__FILE__) . '/global.php');

require_once(dirname(__FILE__) . '/User.php');

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

require_once(dirname(__FILE__) . '/sidebar_header.php');
?>
<div>
	<?php
	if (!is_null(UserConfig::$maillist) && file_exists(UserConfig::$maillist)) {
		?>
		<?php include(UserConfig::$maillist); ?>
		<?php
	}
	?>
</div>

<div>
	<?php
	foreach (UserConfig::$authentication_modules as $module) {
		$id = $module->getID();

		if ($current_module->getID() != $id) {
			continue;
		}
		?>
		<div>
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

			$module->renderEditUserForm("?module=$id", array_key_exists($id, $errors) ? $errors[$id] : array(), $user, $_POST);
			?>
		</div>
		<?php
	}
	?>
</div>

<?php
if (UserConfig::$enableGamification) {
	$available_badges = Badge::getAvailableBadges();

	if (count($available_badges) > 0) {
		?>
		<div>
			<h2>Badges:</h2>
			<?php
			$user_badges = $user->getBadges();

			foreach ($available_badges as $badge) {

				if (array_key_exists($badge->getID(), $user_badges)) {
					$badge_level = $user_badges[$badge->getID()][1];
					?>
					<a href="<?php echo UserConfig::$USERSROOTURL . '/show_badge.php?name=' . $badge->getSlug() ?>"><img class="startupapi-badge" src="<?php echo $badge->getImageURL(UserConfig::$badgeListingSize, $badge_level) ?>" title="<?php echo $badge->getTitle() ?>" width="<?php echo UserConfig::$badgeListingSize ?>" height="<?php echo UserConfig::$badgeListingSize ?>"/></a>
					<?php
				} else {
					?>
					<img class="startupapi-badge" src="<?php echo $badge->getPlaceholderImageURL(UserConfig::$badgeListingSize) ?>" title="Hint: <?php echo $badge->getHint() ?>" width="<?php echo UserConfig::$badgeListingSize ?>" height="<?php echo UserConfig::$badgeListingSize ?>"/>
					<?php
				}
			}
			?>
		</div>
		<?php
	}
}
?>
<?php
require_once(dirname(__FILE__) . '/sidebar_footer.php');
