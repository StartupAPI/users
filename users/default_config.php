<?php
require_once(dirname(__FILE__).'/modules.php');

class UserConfig
{
	// modules list all modules available
	public static $modules;

	public static $ROOTPATH;
	public static $USERSROOTURL;
	public static $USERSROOTFULLURL;
	public static $SITEROOTURL;
	public static $SITEROOTFULLURL;
	public static $DEFAULTLOGINRETURN;
	public static $DEFAULTLOGOUTRETURN;
	public static $DEFAULTREGISTERRETURN;
	public static $DEFAULTUPDATEPASSWORDRETURN;

	// session secret - must be unique for each installation
	public static $SESSION_SECRET;

	// key used in session storage to store user's ID
	public static $session_userid_key = 'users-userid';
	public static $session_return_key = 'users-return-to';

	public static $mysql_host = 'localhost';
	public static $mysql_db;
	public static $mysql_user;
	public static $mysql_password;
	public static $mysql_prefix= 'u_';

	private static $db = null;

	public static $header;
	public static $footer;
	public static $maillist;

	// a list of activities
	public static $activities = array();

	// functionality switches
	public static $enableRegistration = true;
	public static $registrationDisabledMessage = 'Registration is disabled.';

	public static $enableInvitations = false;
	public static $invitationRequiredMessage = 'Invitation code is required to register.';

	// Support emails configuration
	public static $supportEmailFrom = 'User Support <support@example.com>';
	public static $supportEmailReplyTo = 'support@example.com';
	public static $supportEmailXMailer;

	// Password recovery email configuration
	public static $passwordRecoveryEmailSubject = 'Your Password';

	// TODO move all module-specific remember me configurations to module classes
	// Allow remembering user for longer then a session
	public static $allowRememberMe = true;
	// Automatically remember user for longer then a session when they register
	public static $rememberUserOnRegistration = true;
	// Time for long sessions - defaults to 10 years
	// can be set to relatively short, e.g. 2 weeks if needed
	public static $rememberMeTime = 315360000;
	// To check or not "remember me" box by default
	public static $rememberMeDefault = false;

	// use accounts or just users only
	public static $useAccounts = true;

	/*
	 * hooks
	 */

	// Invitation page action renderers
	public static $onRenderUserInvitationAction = 'UserConfig::renderUserInvitationAction';
	public static $onRenderUserInvitationFollowUpAction = 'UserConfig::renderUserInvitationFollowUpAction';
	// formatter for password recovery email
	public static $onRenderTemporaryPasswordEmail = 'UserConfig::renderTemporaryPasswordEmail';
	// handler to be called when new user is created
	public static $onCreate = null;
			
	public static function getDB()
	{
		if (is_null(self::$db))
		{
			self::$db = new mysqli(self::$mysql_host, self::$mysql_user, self::$mysql_password, self::$mysql_db);
		}

		return self::$db;
	}

	public static function setDB($db)
	{
		self::$db = $db;
	}

	public static function renderUserInvitationAction($code)
	{
		?><a href="mailto:?Subject=Invitation+to+HowDoable&Body=<?php echo UserConfig::$SITEROOTURL?>/users/register.php?invite=<?php echo urlencode($code)?>">Invite</a><?php
	}

	public static function renderUserInvitationFollowUpAction($code)
	{
		?><a href="mailto:?Subject=Re:+Invitation+to+HowDoable&Body=<?php echo UserConfig::$SITEROOTURL?>/users/register.php?invite=<?php echo urlencode($code)?>">Follow Up</a><?php
	}

	public static function renderTemporaryPasswordEmail($baseurl, $username, $temppass )
	{
		$message = <<<EOD
You're receieving this email because somebody requested to reset password for your user account

If it wasn't you, then just ignore this email - your current password will keep working fine.

Otherwise, just go and log in using your temporary password:

Login Page: $baseurl
Username: $username
Temporary Password: $temppass

You will be asked to enter your new password before you will be able to continue.

Temporary passwords only work for one day and will become invalid once you set your new password.


--
User Support
EOD;
		return $message;
	}

	public static function init()
	{
		UserConfig::$modules = array();

		UserConfig::$ROOTPATH = dirname(__FILE__);
		UserConfig::$USERSROOTURL = substr(UserConfig::$ROOTPATH, strlen($_SERVER['DOCUMENT_ROOT']));

		// we assume that package is extracted into the root of the site
		UserConfig::$SITEROOTURL = substr(dirname(UserConfig::$ROOTPATH), strlen($_SERVER['DOCUMENT_ROOT'])).'/';
		UserConfig::$DEFAULTLOGINRETURN = UserConfig::$SITEROOTURL;
		UserConfig::$DEFAULTLOGOUTRETURN = UserConfig::$SITEROOTURL;
		UserConfig::$DEFAULTREGISTERRETURN = UserConfig::$SITEROOTURL;
		UserConfig::$DEFAULTUPDATEPASSWORDRETURN = UserConfig::$SITEROOTURL;

		if (array_key_exists('HTTP_HOST', $_SERVER))
		{
			$host = $_SERVER['HTTP_HOST'];
		}
		else
		{
			error_log("Warning: Can't determine site's host name, using www.example.com");
			$host = 'www.example.com';
		}

		UserConfig::$SITEROOTFULLURL = 'http://'.$host.UserConfig::$SITEROOTURL;
		UserConfig::$USERSROOTFULLURL = 'http://'.$host.substr(UserConfig::$ROOTPATH, strlen($_SERVER['DOCUMENT_ROOT']));

		UserConfig::$supportEmailXMailer = 'UserBase (PHP/'.phpversion();

		UserConfig::$header = dirname(__FILE__).'/header.php';
		UserConfig::$footer = dirname(__FILE__).'/footer.php';

		// Built in activities 
		define('USERBASE_ACTIVITY_LOGIN_UPASS',		1000);
		define('USERBASE_ACTIVITY_LOGIN_FB',		1001);
		define('USERBASE_ACTIVITY_LOGIN_GFC',		1002);

		define('USERBASE_ACTIVITY_ADDED_UPASS',		1003);
		define('USERBASE_ACTIVITY_ADDED_FB',		1004);
		define('USERBASE_ACTIVITY_ADDED_GFC',		1005);

		define('USERBASE_ACTIVITY_REMOVED_FB',		1006);
		define('USERBASE_ACTIVITY_REMOVED_GFC',		1007);

		define('USERBASE_ACTIVITY_LOGOUT',		1008);

		define('USERBASE_ACTIVITY_REGISTER_UPASS',	1009);
		define('USERBASE_ACTIVITY_REGISTER_FB',		1010);
		define('USERBASE_ACTIVITY_REGISTER_GFC',	1011);

		define('USERBASE_ACTIVITY_UPDATEUSERINFO',	1012);

		define('USERBASE_ACTIVITY_UPDATEPASS',		1013);
		define('USERBASE_ACTIVITY_RESETPASS',		1014);

		// array of activities in the system velue is an array of label and value of activity
		UserConfig::$activities = array(
			USERBASE_ACTIVITY_LOGIN_UPASS => array('Logged in using username and password',	1),
			USERBASE_ACTIVITY_LOGIN_FB => array('Logged in using Facebook',			1),
			USERBASE_ACTIVITY_LOGIN_GFC => array('Logged in using Google Friend Connect',	1),

			USERBASE_ACTIVITY_ADDED_UPASS => array('Added username and password',		1),
			USERBASE_ACTIVITY_ADDED_FB => array('Added Facebook credential',		1),
			USERBASE_ACTIVITY_ADDED_GFC => array('A Google Friend Connect credential',	1),

			USERBASE_ACTIVITY_REMOVED_FB => array('Removed Facebook Connect',		0),
			USERBASE_ACTIVITY_REMOVED_GFC => array('Removed Google Friend Connect credential',0),

			USERBASE_ACTIVITY_LOGOUT => array('Logged out',					0),

			USERBASE_ACTIVITY_REGISTER_UPASS => array('Registered using a form',		0),
			USERBASE_ACTIVITY_REGISTER_FB => array('Registered using Facebook',		0),
			USERBASE_ACTIVITY_REGISTER_GFC => array('Registered using Google Friend Connect', 0),

			USERBASE_ACTIVITY_UPDATEUSERINFO => array('Updated user info',			0),

			USERBASE_ACTIVITY_UPDATEPASS => array('Updated their password',			0),
			USERBASE_ACTIVITY_RESETPASS => array('Reset forgotten password',		0)
		);
	}
}

UserConfig::init();
