<?php
/**
 * OAuth2 callback script used by all modules that subclass OAuth2Module
 */
require_once(__DIR__.'/global.php');
require_once(__DIR__.'/classes/User.php');

$current_user = User::get();

try
{
    if (!array_key_exists('module', $_GET)) {
        throw new StartupAPIException('module not specified');
    }

    if (!array_key_exists('code', $_GET)) {
        throw new StartupAPIException('OAuth2 "code" parameter required');
    }

    $module = AuthenticationModule::get($_GET['module']);

    $oauth2_client_id = null;
    try
    {
        $oauth2_client_id = $module->getOAuth2ClientIDByCode($_GET['code']);
    }
    catch (OAuth2Exception $e)
    {
        throw new StartupAPIException('Problem getting access token: '.$e->getMessage());
    }

    try
    {
        $identity = $module->getIdentity($oauth2_client_id);
    }
    catch (OAuth2Exception $e)
    {
        throw new StartupAPIException('Problem getting user identity: '.$e->getMessage());
    }

    if (is_null($identity)) {
        throw new StartupAPIException('No identity returned');
    }

    UserTools::debug("Current User: " . var_export($current_user, true));
    UserTools::debug("Identity: " . var_export($identity, true));

    $user = $module->getUserByOAuth2Identity($identity, $oauth2_client_id);

    UserTools::debug("User: " . var_export($user, true));

    if (is_null($current_user)) {
        // if user is not logged in yet, it means we're logging them in
        if (is_null($user)) {
            // This user doesn't exist yet, registering them
            $new_user = User::createNewWithoutCredentials(
                $module,
                $identity['name'],
                array_key_exists('email', $identity) ? $identity['email'] : null
            );

            $module->addUserOAuth2Identity($new_user, $identity, $oauth2_client_id);

            $new_user->setSession(true);
            $module->recordRegistrationActivity($new_user);
        } else {
            $user->setSession(true);
            $module->recordLoginActivity($user);
        }
    } else {
        // otherwise, we're adding their credential to an existing user
        if (!is_null($user)) {
            throw new StartupAPIException('Another user is already connected with this account');
        }

        $module->addUserOAuth2Identity($current_user, $identity, $oauth2_client_id);

        $module->recordAddActivity($current_user);
    }
} catch (Exception $e) {
    UserTools::debug("Exception when coming back from the server" . $e->getMessage());

    // we should delete temporary OAuth2 client ID
    if (!is_null($oauth2_client_id)) {
        $module->deleteOAuth2Client($oauth2_client_id);
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

$return = User::getReturn();
User::clearReturn();

if (is_null($return) && !is_null($current_user)) {
    $return = UserConfig::$USERSROOTURL.'/edit.php';
}

if (!is_null($return))
{
    header('Location: '.$return);
}
else
{
    header('Location: '.UserConfig::$DEFAULTLOGINRETURN);
}
