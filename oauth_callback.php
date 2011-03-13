<?php
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/User.php');

if (!array_key_exists('module', $_GET)) {
	error_log('module not specified');
	header('Location: '.UserConfig::$USERSROOTURL.'/login.php?error=failed');
	exit;
}

if (!array_key_exists('oauth_token', $_GET) || !array_key_exists('oauth_verifier', $_GET)) {
	error_log('oauth_token & oauth_varifier required');
	header('Location: '.UserConfig::$USERSROOTURL.'/login.php?module='.$_GET['module'].'&error=failed');
	exit;
}

$module = null;

foreach (UserConfig::$modules as $module)
{
	if ($module->getID() == $_GET['module']) {
		break;
	}
}

$storage = new MrClay_CookieStorage(array(
	'secret' => UserConfig::$SESSION_SECRET,
	'mode' => MrClay_CookieStorage::MODE_ENCRYPT,
	'path' => UserConfig::$SITEROOTURL,
	'httponly' => true
));

$oauth_user_id = $storage->fetch(UserConfig::$oauth_user_id_key);
$storage->delete(UserConfig::$oauth_user_id_key);

if (is_null($oauth_user_id)) {
	error_log("can't determine OAuth User ID");
	header('Location: '.UserConfig::$USERSROOTURL.'/login.php?module='.$_GET['module'].'&error=failed');
	exit;
}

try
{
	$module->getAccessToken($oauth_user_id);
}
catch (OAuthException2 $e)
{
	error_log('problem getting access token');
	header('Location: '.UserConfig::$USERSROOTURL.'/login.php?module='.$_GET['module'].'&error=failed');
	exit;
}

try
{
	$identity = $module->getIdentity($oauth_user_id);
}
catch (OAuthException2 $e)
{
	error_log('problem getting user identity');
	header('Location: '.UserConfig::$USERSROOTURL.'/login.php?module='.$_GET['module'].'&error=failed');
	exit;
}

if (is_null($identity)) {
	error_log('no identity returned');
	header('Location: '.UserConfig::$USERSROOTURL.'/login.php?module='.$_GET['module'].'&error=failed');
	exit;
}

$user = $module->getUserByOAuthIdentity($identity, $oauth_user_id);

if (is_null($user)) {
	header('Location: '.UserConfig::$USERSROOTURL.'/login.php?module='.$_GET['module'].'&error=failed');
	exit;
}

$user->setSession(true);

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
