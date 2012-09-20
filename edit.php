<?php
require_once(dirname(__FILE__).'/global.php');

require_once(dirname(__FILE__).'/User.php');

$user = User::require_login();

UserTools::preventCSRF();

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
	throw new StartupAPIException('Wrong module specified');
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
	catch(ExistingUserException $ex)
	{
		$user_exists = true;
		$errors[$module->getID()] = $ex->getErrors();
	}
}

require_once(UserConfig::$header);

?>
<div id="startupapi-edit-info">
<h2>Edit Your Information</h2>

<div id="startupapi-edit-account">
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
<div id="startupapi-authlist">
<?php

foreach (UserConfig::$authentication_modules as $module)
{
	$id = $module->getID();

	?><div style="background: white; padding: 0 1em">
	<h3 name="<?php echo $id?>"><?php echo $module->getTitle()?></h3>
<?php
	if (array_key_exists($id, $errors) && is_array($errors[$id]) && count($errors[$id]) > 0)
	{
		?><div class="startupapi-errorbox"><ul><?php
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
<div style="clear: both"></div>
<?php
require_once(UserConfig::$footer);
