<?php
require_once(dirname(__FILE__) . '/admin.php');

if (!array_key_exists('id', $_GET) || !$_GET['id']) {
	header("HTTP/1.0 400 User ID is not specified");
	?><h1>400 User ID is not specified</h1><?php
	exit;
}

$user_id = intval(trim($_GET['id']));

$user = User::getUser($user_id);
if (is_null($user)) {
	header("HTTP/1.0 404 User Not Found");
	?><h1>404 User Not Found</h3><?php
	exit;
}

if (array_key_exists("savefeatures", $_POST)) {
	$features_to_set = array();

	if (array_key_exists("feature", $_POST) && is_array($_POST['feature'])) {
		foreach (array_keys($_POST['feature']) as $featureid) {
			$feature = Feature::getByID($featureid);
			if (!is_null($feature) && $feature->isEnabled()) {
				$features_to_set[] = $feature;
			}
		}
	}

	$user->setFeatures($features_to_set);
}

if (array_key_exists("activate", $_POST)) {
	$user->setStatus(true);
	$user->save();

	header('Location: ' . UserConfig::$USERSROOTURL . '/admin/user.php?id=' . $_GET['id']);
	exit;
}

if (array_key_exists("deactivate", $_POST)) {
	$user->setStatus(false);
	$user->save();

	header('Location: ' . UserConfig::$USERSROOTURL . '/admin/user.php?id=' . $_GET['id']);
	exit;
}

$ADMIN_SECTION = 'registrations';
$BREADCRUMB_EXTRA = $user->getName();
require_once(dirname(__FILE__) . '/header.php');
?>
<div class="span9">

	<form action="" method="POST">
		<h2><?php echo UserTools::escape($user->getName()); ?>
			<div class="pull-right">
				<?php
				if ($user->isDisabled()) {
					?>
					<b style="color: red">Deactivated</b>
					<input class="btn btn-success" type="submit" name="activate" value="Activate" style="font: small" onclick="return confirm('Are you sure you want to activate this user?')"/>
					<?php
				} else {
					if (!$user->isTheSameAs($current_user)) {
						?>
						<form name="imp" action="" method="POST"><input class="btn btn-inverse" type="submit" value="impersonate" style="font: small"/><input type="hidden" name="impersonate" value="<?php echo $user->getID() ?>"/>
							<?php UserTools::renderCSRFNonce(); ?>
						</form>
						<?php
					}
					?>
					<input type="submit" class="btn btn-danger" name="deactivate" value="Deactivate" style="font: small" onclick="return confirm('Are you sure you want to disable access for this user?')"/>
					<?php
				}
				UserTools::renderCSRFNonce();
				?>
			</div>
		</h2>
	</form>

	<p>
		<?php
		$email = $user->getEmail();

		if ($email) {
			?>
			<a href="mailto:<?php echo urlencode(UserTools::escape($email)) ?>">
				<i class="icon-envelope"></i> <?php echo UserTools::escape($email) ?>
			</a>
			<?php
		}
		?>
	</p>

	<p>
		<b>Activity points:</b> <span class="badge"><?php echo $user->getPoints(); ?></span> <a class="btn btn-small" href="activity.php?userid=<?php echo $user->getID() ?>"><i class="icon-signal"></i> See activity</a>
	</p>




	<h3>Source of registration</h3>
	<p>Referer: <?php
		$referer = $user->getReferer();

		if (is_null($referer)) {
			?><i>unknown</i><?php
	} else {
			?><a href="<?php echo UserTools::escape($referer) ?>"><?php echo UserTools::escape($referer) ?></a><?php
	}
		?>
	</p>

	<h3>Authentication Credentials</h3>
	<ul><?php
		foreach (UserConfig::$authentication_modules as $module) {
			$creds = $module->getUserCredentials($user);

			if (!is_null($creds)) {
				?>
				<li><b><?php echo $module->getID() ?>: </b><?php echo $creds->getHTML() ?></li>
				<?php
			}
		}
		?>
	</ul>
	<?php
	if (UserConfig::$useAccounts) {
		?>
		<h3>Accounts:</h3>
		<ul>
			<?php
			$accounts = $user->getAccounts();

			foreach ($accounts as $user_account) {
				?><li>
					<?php echo UserTools::escape($user_account->getName()) ?> (<?php echo UserTools::escape($user_account->getPlan()->getName()) ?>)<?php
			if ($user_account->getUserRole() == Account::ROLE_ADMIN) {
						?> (admin)<?php
		}
					?></li><?php
		}
				?>
		</ul>
		<?php
	}

	$features = Feature::getAll();
	if (count($features) > 0) {
		$has_features_to_save = false;
		?><h3>Features</h3>
		<form class="form" action="" method="POST">
			<?php foreach ($features as $id => $feature) {
				?><div<?php if (!$feature->isEnabled()) { ?> style="color: grey; text-decoration: line-through"<?php } ?>>
					<label class="checkbox">
						<input id="feature_<?php echo UserTools::escape($feature->getID()) ?>"
							   type="checkbox"
							   name="feature[<?php echo UserTools::escape($feature->getID()) ?>]"
							   <?php if ($feature->isEnabledForUser($user)) { ?> checked="true"<?php } ?>
							   <?php if (!$feature->isEnabled() || $feature->isRolledOutToAllUsers()) { ?> disabled="disabled"<?php
				   } else {
					   $has_features_to_save = true;
				   }
							   ?>
							   >
							   <?php echo UserTools::escape($feature->getName()) ?>
					</label>
				</div>
			<?php } ?>
			<input class="btn btn-primary"
				   type="submit"
				   name="savefeatures"
				   value="update features"
				   <?php if (!$has_features_to_save) { ?> disabled="disabled"<?php } ?>
				   >
				   <?php UserTools::renderCSRFNonce();
				   ?>
		</form>
		<?php
	}
	?>

</div>
<?php
require_once(dirname(__FILE__) . '/footer.php');
