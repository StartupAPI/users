<?php
require_once(dirname(__FILE__).'/config.php');

require_once(dirname(__FILE__).'/User.php');

$user = User::require_login();

$errors = array();

$module = UserConfig::$modules[0];
if (array_key_exists('module', $_GET))
{
	foreach (UserConfig::$modules as $module)
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

?><h1>Edit Your Information</h1>

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
<?php

foreach (UserConfig::$modules as $module)
{
	$id = $module->getID();

	?><div style="background: white; padding: 0 1em"><h2 name="<?php echo $id?>"><?php echo $module->getTitle()?></h2>
<?php
	if (array_key_exists($id, $errors) && is_array($errors[$id]) && count($errors[$id]) > 0)
	{
		?><div style="border: 1px solid black; padding: 0.5em; background: #FFFBCF; margin-bottom: 1em; max-width: 25em"><ul><?php
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
require_once(UserConfig::$footer);
