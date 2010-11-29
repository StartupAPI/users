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

	$remember = false;
	$user = $module->processLogin($_POST, $remember);

	if (is_null($user))
	{
		header('Location: '.UserConfig::$USERSROOTURL.'/login.php?module='.$_GET['module'].'&error=failed');
		exit;
	}

	$user->setSession($remember);

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

?><h2>Log in</h2><div style="background: white; padding: 0 1em"><?php

foreach (UserConfig::$modules as $module)
{
	$id = $module->getID();

	?>
	<div style="margin-bottom: 2em">
	<h3 name="<?php echo $id?>"><?php echo $module->getTitle()?></h3>
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
