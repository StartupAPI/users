<?php
require_once(dirname(__FILE__).'/config.php');

require_once(dirname(__FILE__).'/User.php');

if (array_key_exists('login', $_POST))
{
	$module = null;

	foreach (UserConfig::$modules as $module)
	{
		if ($module->getID() == $_GET['module']) {
			break;
		}
	}

	$user = $module->processLogin($_POST);

	if (is_null($user))
	{
		header('Location: '.UserConfig::$USERSROOTURL.'/login.php?module='.$_GET['module'].'&error=failed');
		exit;
	}

	$user->setSession(array_key_exists('remember', $_POST) ? true : false);

	$return = User::getReturn();
	User::clearReturn();
	if (!is_null($return))
	{
		header('Location: '.$return);
	}
	else
	{
		header('Location: '.UserConfig::$DEFAULTLOGINRETURN);
	}

	exit;
}

require_once(UserConfig::$header);

?><h1>Log in</h1><div style="background: white; padding: 0 1em"><?php

foreach (UserConfig::$modules as $module)
{
	$id = $module->getID();

	?>
	<div style="margin-bottom: 2em">
	<h2 name="<?php echo $id?>"><?php echo $module->getTitle()?></h2>
<?php
	if (array_key_exists('module', $_GET) && $id == $_GET['module'] && array_key_exists('error', $_GET))
	{
		?><div style="color: red; border: 1px solid black; padding: 10px; margin: 0.5em">Login failed</div><?php
	}

	$module->renderLoginForm("?module=$id");
	?></div>
<?php
}

?></div><?php
require_once(UserConfig::$footer);
