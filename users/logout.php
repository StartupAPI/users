<?php
require_once(dirname(__FILE__).'/config.php');

require_once(dirname(__FILE__).'/User.php');

User::clearSession();

$return = User::getReturn();
User::clearReturn();

if (!is_null($return))
{
	header('Location: '.$return);
}
else
{
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
}
