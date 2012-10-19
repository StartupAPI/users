<?php
require_once(dirname(__FILE__).'/admin.php');

if (!array_key_exists('id', $_GET) || !$_GET['id']) {
	header("HTTP/1.0 400 User ID is not specified");
	?><h1>400 User ID is not specified</h1><?php
	exit;
}

$user = User::getUser($_GET['id']);
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
}

if (array_key_exists("deactivate", $_POST)) {
	$user->setStatus(false);
	$user->save();
}

$ADMIN_SECTION = 'registrations';
$BREADCRUMB_EXTRA = $user->getName();
require_once(dirname(__FILE__).'/header.php');
?>
<div class="span9">

<h2>User information: <?php echo UserTools::escape($user->getName()); ?></h2>

<p><b>Email:</b>
<?php
$email = $user->getEmail();
if ($email) {
	?><a href="mailto:<?php echo urlencode(UserTools::escape($email)) ?>"><?php echo UserTools::escape($email) ?></a><?php
} else {
	?><i>not specified</i><?php
}
?></p>

<p><b>Total points:</b> <?php echo $user->getPoints(); ?> (<a href="activity.php?userid=<?php echo $user->getID() ?>">see activity</a>)
</p>

<h2>Status</h2>
<?php
if ($user->isDisabled()) {
?>
<form action="" method="POST">
<b style="background: red; padding: 0.5em; color: white">Deactivated</b>
<input type="submit" name="activate" value="activate" style="font: small" onclick="return confirm('Are you sure you want to activate this user?')"/>
<?php UserTools::renderCSRFNonce(); ?>
</form>
<?php
} else {
?>
<form action="" method="POST">
Active
<input type="submit" name="deactivate" value="deactivate" style="font: small" onclick="return confirm('Are you sure you want to disable access for this user?')"/>
<?php UserTools::renderCSRFNonce(); ?>
</form>
<?php
}
?>


<h2>Source of registration</h2>
<p>Referer: <?php

$referer = $user->getReferer();

if (is_null($referer)) {
	?><i>unknown</i><?php
} else {
	?><a href="<?php echo UserTools::escape($referer)?>"><?php echo UserTools::escape($referer)?></a><?php
}
?>
</p>

<h2>Authentication Credentials</h2>
<ul><?php
foreach (UserConfig::$authentication_modules as $module)
{
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

if (!$user->isTheSameAs($current_user)) {
?>
<form name="imp" action="" method="POST"><input type="submit" value="impersonate" style="font: small"/><input type="hidden" name="impersonate" value="<?php echo $user->getID()?>"/>
<?php UserTools::renderCSRFNonce(); ?>
</form>
<?php
}

if (UserConfig::$useAccounts) { ?>
	<h2>Accounts:</h2>
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
	?><h2>Features</h2>
	<form action="" method="POST">
	<?php foreach ($features as $id => $feature) {
		?><div<?php if (!$feature->isEnabled()) {?> style="color: grey; text-decoration: line-through"<?php } ?>>
		<label>
		<input id="feature_<?php echo UserTools::escape($feature->getID()) ?>" type="checkbox" name="feature[<?php echo UserTools::escape($feature->getID()) ?>]"<?php echo $feature->isEnabledForUser($user) ? ' checked="true"' : '' ?><?php echo !$feature->isEnabled() || $feature->isRolledOutToAllUsers() ? ' disabled="disabled"' : '' ?>>
		<?php echo UserTools::escape($feature->getName()) ?></label>
		</div><?php
	} ?>
	<input type="submit" name="savefeatures" value="update features">
	<?php UserTools::renderCSRFNonce(); ?>
	</form>
<?php
}
?>

</div>
<?php
require_once(dirname(__FILE__).'/footer.php');
