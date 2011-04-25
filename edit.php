<?php
require_once(dirname(__FILE__).'/config.php');

require_once(dirname(__FILE__).'/User.php');

$user = User::require_login();

$errors = array();

$module = UserConfig::$authentication_modules[0];
if (array_key_exists('module', $_GET))
{
	foreach (UserConfig::$authentication_modules as $module)
	{
		if ($module->getID() == $_GET['module']) {
			break;
		}
	}
}

if (is_null($module))
{
	throw new Exception('Wrong module specified');
}

if (array_key_exists('save', $_POST))
{
	try
	{
		if ($module->processEditUser($user, $_POST))
		{
			header('Location: '.UserConfig::$USERSROOTURL.'/edit.php?module='.$_GET['module']);
		}
		else
		{
			header('Location: '.UserConfig::$USERSROOTURL.'/edit.php?module='.$_GET['module'].'&error=failed');
		}

		exit;
	}
	catch(InputValidationException $ex)
	{
		$errors[$module->getID()] = $ex->getErrors();
	}
}

require_once(UserConfig::$header);

?>
<style>
#userbase-edit-info {
	font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
	background: white;
	padding: 0 1em;
	margin: 0;
}

#userbase-edit-info h2 {
	font-weight: bold;
	font-size: 2.5em;
}

#userbase-edit-info h3 {
	font-weight: bold;
	font-size: 1.5em;
}

.userbase-errorbox {
	background: #f7dfb9;
	padding: 0.4em 1em;
	margin: 1em 0;
	width: 515px;
	border: 4px solid #f77;
	border-radius: 7px;
	-moz-border-radius: 7px;
	-webkit-border-radius: 7px;
	font-size: 1.2em;
	color: #500;
	font-weight: bold;
}
</style>
<div id="userbase-edit-info">
<h2>Edit Your Information</h2>

<div style="float: right; width: 400px">
<div>
<?php if (UserConfig::$useAccounts) { ?>
<h2>Accounts:</h2>
<?php
	$accounts = $user->getAccounts();

	foreach ($accounts as $user_account) {
		?><div>
		<?php echo $user_account->getName() ?> (<?php echo $user_account->getPlan()->getName() ?>)<?php
		if ($user_account->getUserRole() == Account::ROLE_ADMIN) {
			?> - <a href="<?php echo UserConfig::$USERSROOTURL.'/manage_account.php?account='.$user_account->getID(); ?>">manage</a><?php
		}
		?></div><?php
	}
}
?>
</div>

<?php if (!is_null(UserConfig::$maillist) && file_exists(UserConfig::$maillist))
{
?>
<?php include(UserConfig::$maillist); ?>
<?php
}
?></div>
<div id="userbase-authlist">
<?php

foreach (UserConfig::$authentication_modules as $module)
{
	$id = $module->getID();

	?><div style="background: white; padding: 0 1em">
	<h3 name="<?php echo $id?>"><?php echo $module->getTitle()?></h3>
<?php
	if (array_key_exists($id, $errors) && is_array($errors[$id]) && count($errors[$id]) > 0)
	{
		?><div class="userbase-errorbox"><ul><?php
		foreach ($errors[$id] as $field => $errorset)
		{
			foreach ($errorset as $error)
			{
				?><li><?php echo $error?></li><?php
			}
		}
		?></ul></div><?php
	}

	$module->renderEditUserForm("?module=$id",
		array_key_exists($id, $errors) ? $errors[$id] : array(),
		$user,
		$_POST);
	?></div><?php
}
?>
</div>

</div>
<?php
require_once(UserConfig::$footer);
