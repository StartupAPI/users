<?php
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/User.php');

$current_user = User::get();

$oauth_user_id = null;

try
{
	if (!array_key_exists('module', $_GET)) {
		throw new Exception('module not specified');
	}

	if (!array_key_exists('oauth_token', $_GET) || !array_key_exists('oauth_verifier', $_GET)) {
		throw new Exception('oauth_token & oauth_varifier required');
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
		throw new Exception("can't determine OAuth User ID");
	}

	try
	{
		$module->getAccessToken($oauth_user_id);
	}
	catch (OAuthException2 $e)
	{
		throw new Exception('problem getting access token');
	}

	try
	{
		$identity = $module->getIdentity($oauth_user_id);
	}
	catch (OAuthException2 $e)
	{
		throw new Exception('problem getting user identity');
	}

	if (is_null($identity)) {
		throw new Exception('no identity returned');
	}

	#error_log(
	#	'$identity = '.var_export($identity, true).
	#	'$oauth_user_id = '.$oauth_user_id
	#);

	$user = $module->getUserByOAuthIdentity($identity, $oauth_user_id);

	if (is_null($current_user)) {
		// if user is not logged in yet, it means we're logging them in
		if (is_null($user)) {
			// This user doesn't exist yet, registering them
			$new_user = User::createNewWithoutCredentials(
				$identity['name'],
				array_key_exists('email', $identity) ? $identity['email'] : null
			);

			$module->addUserOAuthIdentity($new_user, $identity, $oauth_user_id);

			// TODO record registration activity

			$new_user->setSession(true);
		} else {
			$user->setSession(true);

			// TODO record login activity
		}
	} else {
		// otherwise, we're adding their credential to an existing user
		if (!is_null($user)) {
			throw new Exception('another user is already connected with this account');
		}

		$module->addUserOAuthIdentity($current_user, $identity, $oauth_user_id);

		// TODO record connect account activity
	}
} catch (Exception $e) {
	error_log($e->getMessage());

	// we should delete temporary OAuth User ID
	if (!is_null($oauth_user_id)) {
		$module->deleteOAuthUser($oauth_user_id);
	}

	if (is_null($current_user)) {
		header('Location: '.UserConfig::$USERSROOTURL.'/login.php?'.
			(array_key_exists('module', $_GET) ? 'module='.$_GET['module'].'&' : '').
			'error=failed');
	} else {
		header('Location: '.UserConfig::$USERSROOTURL.'/edit.php?'.
			(array_key_exists('module', $_GET) ? 'module='.$_GET['module'].'&' : '').
			'error=failed');
	}
	exit;
}

$return = null;

if (!is_null($current_user)) {
	$return = UserConfig::$USERSROOTURL.'/edit.php';
} else {
	$return = User::getReturn();
	User::clearReturn();
}

if (!is_null($return))
{
	header('Location: '.$return);
}
else
{
	header('Location: '.UserConfig::$DEFAULTLOGINRETURN);
}
