<?php
require_once(dirname(__FILE__).'/Plan.php');
require_once(dirname(__FILE__).'/Cohort.php');
require_once(dirname(__FILE__).'/Feature.php');
require_once(dirname(__FILE__).'/modules.php');
require_once(dirname(__FILE__).'/tools.php');

class UserConfig
{
	// list of all available modules
	public static $all_modules = array();

	// multiple email modules can be assigned for the same instance
	public static $authentication_modules = array();

	// Only one email module can exist
	public static $email_module;

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

	// Administrator users
	public static $admins = array();

	// key used in session storage to store user's ID
	public static $session_userid_key = 'users-userid';
	public static $session_return_key = 'users-return-to';

	public static $mysql_host = 'localhost';
	public static $mysql_port = 3306;
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

	// a list of cohort providers for cohort analysis
	public static $cohort_providers = array();

	/* A list of features in the system.
	   Key must be integer.
	   Values of the array are:
		- name of the feature (string)
		- if it is enabled or disabled globally (boolean)
		- if it is enabled for everybody, overriding account settings (boolean)
	*/
	public static $features = array();

	// returning user activity configs
	public static $last_login_key = 'users-last-login';
	public static $last_login_session_length = 30; // 30 minutes away considered returning user

	// tracking referrals
	public static $entry_referer_key = 'users-ref';

	// campaign tracking variables with Google Analytics defaults
	public static $entry_cmp_key = 'users-cmp';
	public static $campaign_variables = array(
		'cmp_source' => array('utm_source'),
		'cmp_medium' => array('utm_medium'),
		'cmp_keywords' => array('utm_term'),
		'cmp_content' => array('utm_content'),
		'cmp_name' => array('utm_campaign')
	);

	// don't display activity for some admin users
	public static $dont_display_activity_for = array();

	// functionality switches
	public static $enableRegistration = true;
	public static $registrationDisabledMessage = 'Registration is disabled.';

	public static $enableInvitations = false;
	public static $invitationRequiredMessage = 'Please enter your invitation code';

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

	// OAuth application name (not sent if null)
	public static $OAuthAppName = null;

	// key for storing OAuth User ID during the OAuth workflow
	public static $oauth_user_id_key = 'users-oauth-user-id';

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
			self::$db = new mysqli(self::$mysql_host, self::$mysql_user, self::$mysql_password, self::$mysql_db, self::$mysql_port);
			if (!self::$db->set_charset('utf8')) {
				error_log("[UserBase] Warning: Can't set utf8 charset for DB connection");
			}
		}

		return self::$db;
	}

	public static function setDB($db)
	{
		self::$db = $db;
	}

	public static function renderUserInvitationAction($code)
	{
		?><a href="mailto:?Subject=Invitation&Body=<?php echo UserConfig::$SITEROOTFULLURL?>users/register.php?invite=<?php echo urlencode($code)?>">Invite</a><?php
	}

	public static function renderUserInvitationFollowUpAction($code)
	{
		?><a href="mailto:?Subject=Re:+Invitation&Body=<?php echo UserConfig::$SITEROOTFULLURL?>users/register.php?invite=<?php echo urlencode($code)?>">Follow Up</a><?php
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

	public static function loadModule($modulename) {
		require_once(dirname(__FILE__).'/modules/'.$modulename.'/index.php');
	}

	public static function init()
	{
		UserConfig::$ROOTPATH = dirname(__FILE__);

		// Chopping of trailing slash which is not supposed to be there in Apache config
		// See: http://httpd.apache.org/docs/2.0/mod/core.html#documentroot
		$docroot = $_SERVER['DOCUMENT_ROOT'];
		if (substr($docroot, -1) == '/') {
			$docroot = substr($docroot, 0, -1);
		}
		$docrootlength = strlen($docroot);
		UserConfig::$USERSROOTURL = substr(UserConfig::$ROOTPATH, $docrootlength);

		// we assume that package is extracted into the root of the site
		UserConfig::$SITEROOTURL = substr(dirname(UserConfig::$ROOTPATH), $docrootlength).'/';
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
			error_log("[UserBase config] Warning: Can't determine site's host name, using www.example.com");
			$host = 'www.example.com';
		}

		UserConfig::$SITEROOTFULLURL = 'http://'.$host.UserConfig::$SITEROOTURL;
		UserConfig::$USERSROOTFULLURL = 'http://'.$host.substr(UserConfig::$ROOTPATH, $docrootlength);

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

		define('USERBASE_ACTIVITY_RETURN_DAILY',	1015);
		define('USERBASE_ACTIVITY_RETURN_WEEKLY',	1016);
		define('USERBASE_ACTIVITY_RETURN_MONTHLY',	1017);

		// Array of activities in the system.
		// Key must be integer (best if specified using a constant).
		// The values are an array with label and "points" value of activity.
		UserConfig::$activities = array(
			USERBASE_ACTIVITY_LOGIN_UPASS => array('Logged in using username and password',	1),
			USERBASE_ACTIVITY_LOGIN_FB => array('Logged in using Facebook',			1),
			USERBASE_ACTIVITY_LOGIN_GFC => array('Logged in using Google Friend Connect',	1),

			USERBASE_ACTIVITY_ADDED_UPASS => array('Added username and password',		1),
			USERBASE_ACTIVITY_ADDED_FB => array('Added Facebook credential',		1),
			USERBASE_ACTIVITY_ADDED_GFC => array('Added Google Friend Connect credential',	1),

			USERBASE_ACTIVITY_REMOVED_FB => array('Removed Facebook Connect',		0),
			USERBASE_ACTIVITY_REMOVED_GFC => array('Removed Google Friend Connect credential',0),

			USERBASE_ACTIVITY_LOGOUT => array('Logged out',					0),

			USERBASE_ACTIVITY_REGISTER_UPASS => array('Registered using a form',		1),
			USERBASE_ACTIVITY_REGISTER_FB => array('Registered using Facebook',		1),
			USERBASE_ACTIVITY_REGISTER_GFC => array('Registered using Google Friend Connect', 1),

			USERBASE_ACTIVITY_UPDATEUSERINFO => array('Updated user info',			0),

			USERBASE_ACTIVITY_UPDATEPASS => array('Updated their password',			0),
			USERBASE_ACTIVITY_RESETPASS => array('Reset forgotten password',		0),

			USERBASE_ACTIVITY_RETURN_DAILY => array('Returned to the site within a day',	3),
			USERBASE_ACTIVITY_RETURN_WEEKLY => array('Returned to the site within a week',	2),
			USERBASE_ACTIVITY_RETURN_MONTHLY => array('Returned to the site within a month', 1)
		);

		UserConfig::$cohort_providers[] = new GenerationCohorts(GenerationCohorts::MONTH);
		UserConfig::$cohort_providers[] = new GenerationCohorts(GenerationCohorts::WEEK);
		UserConfig::$cohort_providers[] = new GenerationCohorts(GenerationCohorts::YEAR);
		UserConfig::$cohort_providers[] = new RegMethodCohorts();
	}

	// Couldn't reuse it, but keeping it here because it might be still populated in user configs
	// Use UserConfig::$all_modules array instead of needed
	/* !!! DEPRECATED !!! */ public static $modules = array();
}

UserConfig::init();
