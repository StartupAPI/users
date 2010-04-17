<?
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

<? if (!is_null(UserConfig::$maillist))
{
?>
<div style="float: right; width: 400px">
<? include(UserConfig::$maillist); ?>
</div>
<?
}

foreach (UserConfig::$modules as $module)
{
	$id = $module->getID();

	?><div style="background: white; padding: 1em"><h2 name="<?=$id?>"><?=$module->getTitle()?></h2>
<?
	if (array_key_exists($id, $errors) && is_array($errors[$id]) && count($errors[$id]) > 0)
	{
		?><div style="border: 1px solid black; padding: 0.5em; background: #FFFBCF; margin-bottom: 1em; max-width: 25em"><ul><?
		foreach ($errors[$id] as $field => $errorset)
		{
			foreach ($errorset as $error)
			{
				?><li><?=$error?></li><?
			}
		}
		?></ul></div><?
	}

	$module->renderEditUserForm("?module=$id",
		array_key_exists($id, $errors) ? $errors[$id] : array(),
		$user,
		$_POST);
	?></div><?
}
require_once(UserConfig::$footer);
