<?php
require_once(dirname(dirname(__FILE__)).'/users.php');
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

#$ADMIN_SECTION = 'registrations';
require_once(dirname(__FILE__).'/header.php');
?>
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
<form name="imp" action="" method="POST"><input type="submit" value="impersonate" style="font: small"/><input type="hidden" name="impersonate" value="<?php echo $user->getID()?>"/></form>
</ul>
<?php if (UserConfig::$useAccounts) { ?>
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
}?>
<?php
require_once(dirname(__FILE__).'/footer.php');
