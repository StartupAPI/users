<?php
require_once(dirname(__FILE__).'/config.php');

require_once(dirname(__FILE__).'/User.php');

User::clearSession();

$return = User::getReturn();
User::clearReturn();

$user = User::get();
if (!is_null($user)) {
	$user->recordActivity(USERBASE_ACTIVITY_LOGOUT);
}

if (!is_null($return))
{
	header('Location: '.$return);
}
else
{
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
}
