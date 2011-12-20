<?php
require_once(dirname(__FILE__).'/config.php');

require_once(dirname(__FILE__).'/User.php');

// Allow modules to auto-login (if supported)
$user = null;

foreach (UserConfig::$authentication_modules as $module)
{
	$user = $module->processAutoLogin();
	if (!is_null($user)) {
		$remember = false;
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
}

if (array_key_exists('login', $_POST))
{
	$module = null;

	foreach (UserConfig::$authentication_modules as $module)
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

?>
<style>
.userbase-errorbox {
	background: #f7dfb9;
	font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
	padding: 0.4em 1em;
	margin: 1em 0;
	width: 475px;
	border: 4px solid #f77;
	border-radius: 7px;
	-moz-border-radius: 7px;
	-webkit-border-radius: 7px;
	font-size: 1.2em;
	color: #500;
	font-weight: bold;
}

#userbase-authlist {
	font: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
	background: white;
	padding: 0 1em;
	margin: 0 auto;
	width: 480px;
}

#userbase-authlist h2 {
	font-weight: bold;
	font-size: 2.5em;
}
#userbase-authlist h3 {
	font-weight: bold;
	font-size: 1.5em;
}
</style>

<div id="userbase-authlist">
<h2>Log in</h2>
<?php

foreach (UserConfig::$authentication_modules as $module)
{
	$id = $module->getID();

	?>
	<div style="margin-bottom: 2em">
	<h3 name="<?php echo $id?>"><?php echo $module->getTitle()?></h3>
<?php
	if (array_key_exists('module', $_GET) && $id == $_GET['module'] && array_key_exists('error', $_GET))
	{
		?><div class="userbase-errorbox">Login failed</div><?php
	}

	$module->renderLoginForm("?module=$id");
	?></div>
<?php
}

?></div><?php
require_once(UserConfig::$footer);
