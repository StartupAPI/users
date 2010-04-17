<?
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

	// Facebook configuration
	public static $FacebookAPIKey;
	public static $FacebookSecret;

	private static $db = null;

	public static $header = 'header.php';
	public static $footer = 'footer.php';
	public static $maillist;

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

	// Allow remembering user for longer then a session
	public static $allowRememberMe = true;
	// Automatically remember user for longer then a session when they register
	public static $rememberUserOnRegistration = true;
	// Time for long sessions - defaults to 10 years
	// can be set to relatively short, e.g. 2 weeks if needed
	public static $rememberMeTime = 315360000;

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
		?><a href="mailto:?Subject=Invitation+to+HowDoable&Body=<?=UserConfig::$SITEROOTURL?>/users/register.php?invite=<?=urlencode($code)?>">Invite</a><?
	}

	public static function renderUserInvitationFollowUpAction($code)
	{
		?><a href="mailto:?Subject=Re:+Invitation+to+HowDoable&Body=<?=UserConfig::$SITEROOTURL?>/users/register.php?invite=<?=urlencode($code)?>">Follow Up</a><?
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
	}
}

UserConfig::init();
