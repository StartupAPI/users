<?php
/**
 * @package StartupAPI
 */
require_once(dirname(__FILE__) . '/Plan.php');
require_once(dirname(__FILE__) . '/TransactionLogger.php');
require_once(dirname(__FILE__) . '/Cohort.php');
require_once(dirname(__FILE__) . '/Feature.php');
require_once(dirname(__FILE__) . '/Badge.php');
require_once(dirname(__FILE__) . '/StartupAPIModule.php');
require_once(dirname(__FILE__) . '/tools.php');

/**
 * This class contains a bunch of static variables defining how Startup API instance
 * would behave with reasonable defaults that can be overriden in users_config.php
 *
 * @package StartupAPI
 *
 * @todo Move read-only arrays into appropriate classes and make them private
 */
class UserConfig {
	/* ========================================================================
	 *
	 * Modules and extensions
	 *
	 * ===================================================================== */

	/**
	 * @var array List of all available modules (StartupAPIModule objects). Do not modify!
	 */
	public static $all_modules = array();

	/**
	 * List of authentication modules (AuthenticationModule objects). Do not modify!
	 * Multiple authentication modules can be assigned for the same instance
	 *
	 * @var array
	 */
	public static $authentication_modules = array();
	// payment modules
	public static $payment_modules = array();

	/**
	 * Email module responsible for syncronization with newsletter service. Only one can be assigned. Do not modify!
	 *
	 * @var EmailModule
	 */
	public static $email_module;


	/* ========================================================================
	 *
	 * Debugging
	 *
	 * ===================================================================== */

	/**
	 * @var boolean Enable / disable debug messages
	 */
	public static $DEBUG = false;

	/* ========================================================================
	 *
	 * Paths and URLs
	 *
	 * ===================================================================== */

	/**
	 * @var string Root path of the project on the file system
	 */
	public static $ROOTPATH;

	/**
	 * @var string Root URL of Startup API code (relative, e.g. /myapp/users/)
	 */
	public static $USERSROOTURL;

	/**
	 * @var string Root URL of Startup API code (full, e.g. http://example.com/myapp/users/)
	 */
	public static $USERSROOTFULLURL;

	/**
	 * @var string Root URL of the application using Startup API (relative, e.g. /myapp/)
	 */
	public static $SITEROOTURL;

	/**
	 * @var string Root URL of the application using Startup API (full, e.g. http://example.com/myapp/)
	 */
	public static $SITEROOTFULLURL;

	/**
	 * @var string Default location URL to return to upon login
	 */
	public static $DEFAULTLOGINRETURN;

	/**
	 * @var string Default location URL to return to upon logout
	 */
	public static $DEFAULTLOGOUTRETURN;

	/**
	 * @var string Default location URL to return to upon registration
	 */
	public static $DEFAULTREGISTERRETURN;

	/**
	 * @var string Default location URL to return to upon password reset (for username/password auth)
	 */
	public static $DEFAULTUPDATEPASSWORDRETURN;

	/**
	 * @var string Default location URL to return to upon successful email verification
	 */
	public static $DEFAULT_EMAIL_VERIFIED_RETURN;

	/* ========================================================================
	 *
	 * Sessions and cookies
	 *
	 * All cookies are stored encrypted using a session secret
	 *
	 * ===================================================================== */

	/**
	 * @var string Session secret - must be unique for each installation
	 *
	 * @todo Add some validation to ensure that this instance actually specified a unique secret
	 */
	public static $SESSION_SECRET;

	/**
	 * @var string Cookie name for csrf nonce storage
	 */
	public static $csrf_nonce_key = 'users-csrf-nonce';

	/**
	 * @var string Cookie name for User ID, indicates that user is logged in.
	 */
	public static $session_userid_key = 'users-userid';

	/**
	 * Cookie name for the URL to return to for redirect-based actions
	 * like login, registration and etc.
	 *
	 * @var string
	 */
	public static $session_return_key = 'users-return-to';

	/**
	 * @var string Cookie name for User ID of the user being impersonated
	 */
	public static $impersonation_userid_key = 'users-userid-impr';

	/**
	 * @var string Facebook session storage cookie name prefix
	 */
	public static $facebook_storage_key_prefix = 'users-fb';

	/**
	 * @var string Cookie name for OAuth User ID during the OAuth workflow
	 */
	public static $oauth_user_id_key = 'users-oauth-user-id';

	/**
	 * @var string Cookie name for storing referrer between anonymous user's arrival and their registration
	 */
	public static $entry_referer_key = 'users-ref';

	/**
	 * @var string Cookie name for storing campaign object between anonymous user's arrival and their registration
	 */
	public static $entry_cmp_key = 'users-cmp';

	/**
	 * @var string Cookie name for last login cookie
	 */
	public static $last_login_key = 'users-last-login';

	/**
	 * @var boolean Allow remembering a user beyond their browser session, true by default
	 */
	public static $allowRememberMe = true;

	/**
	 * @var boolean Automatically remember user  beyond their browser session when they register, true by default
	 */
	public static $rememberUserOnRegistration = true;

	/**
	 * Time in seconds for long sessions - defaults to 10 years, can be set to relatively short, e.g. 2 weeks if needed
	 *
	 * @var int
	 */
	public static $rememberMeTime = 315360000;

	/**
	 * @var boolean Checks "remember me" box on registration and login forms (false by default)
	 */
	public static $rememberMeDefault = false;

	/* ========================================================================
	 *
	 * Admin UI and access
	 *
	 * ===================================================================== */

	/**
	 * Array of integer IDs for instance administrators (who have access to adin UI).
	 *
	 * Usually first user with ID of 1 is administrator, but defining it without
	 * having this user in the system might be dangerous if IDs didnt generate
	 * as you expected upon data re-import or something.
	 *
	 * @var array
	 */
	public static $admins = array();

	/* ========================================================================
	 *
	 * DB cpnnectivity
	 *
	 * ===================================================================== */

	/**
	 * @var string MySQL host
	 */
	public static $mysql_host = 'localhost';

	/**
	 * @var int MySQL port
	 */
	public static $mysql_port = 3306;

	/**
	 * MySQL socket path on file system. If specified, StartupAPI will not use TCP/IP,
	 * but a socket connection instead.
	 *
	 * @var string
	 */
	public static $mysql_socket;

	/**
	 * @var string MySQL database name
	 */
	public static $mysql_db;

	/**
	 * @var string MySQL database user. See access permissions requirements at http://StartupAPI.org/StartupAPI/DB_privileges
	 */
	public static $mysql_user;

	/**
	 * @var string MySQL password
	 */
	public static $mysql_password;

	/**
	 * @var string MySQL table prefix for all StartupAPI tables ('u_' by default)
	 */
	public static $mysql_prefix = 'u_';

	/**
	 * @var mysqli Database connection singleton
	 */
	private static $db = null;

	/* ========================================================================
	 *
	 * Headers, footers and look and feel
	 *
	 * ===================================================================== */

	/**
	 * @var string Application name
	 */
	public static $appName;

	/**
	 * @var string File system path to header HTML file.
	 */
	public static $header;

	/**
	 * @var string File system path to header HTML file.
	 */
	public static $footer;

	/**
	 * File system path to maillist management widget file to be included on profile management page.
	 *
	 * @var string
	 */
	public static $maillist;

	/**
	 * @var string File system path to admin UI header HTML file.
	 */
	public static $admin_header;

	/**
	 * @var string File system path to admin UI footer HTML file.
	 */
	public static $admin_footer;

	/**
	 * @var array Array of available theme slugs
	 */
	public static $available_themes = array('classic');

	/**
	 * @var string Theme slug for current theme
	 */
	public static $theme = 'classic';


	/* ========================================================================
	 *
	 * Activity tracking and analytics
	 *
	 * ===================================================================== */

	/**
	 * @var array An array of activity entries
	 *
	 * @todo Create Activity class and rewrite everything to use the class instead of params array
	 */
	public static $activities = array();

	/**
	 * @var boolean Only consider users active if they had activities with non-zero value points assigned
	 */
	public static $adminActiveOnlyWithPoints = false;

	/**
	 * @var array An array of cohort providers (CohortProvider objects) for cohort analysis
	 */
	public static $cohort_providers = array();

	// returning user activity configs

	/**
	 * @var int Number of minutes for considering a user as returning user, 30 minutes by default
	 */
	public static $last_login_session_length = 30;

	/**
	 * Array of arrays of URL parameters to be used for campaign tracking.
	 * Google Analytics (Urchin) defaults are pre-configured, you can append your keys.
	 *
	 * The following keys are used:
	 * - cmp_source - campaign source ('utm_source' is tracked by default)
	 * - cmp_medium - campaign medium ('utm_medium' is tracked by default)
	 * - cmp_keywords - campaign keyworkds ('utm_term' is tracked by default)
	 * - cmp_content - campaign content ('utm_content' is tracked by default)
	 * - cmp_name - campaign name ('utm_campaign' is tracked by default)
	 *
	 * @var array
	 */
	public static $campaign_variables = array(
		'cmp_source' => array('utm_source'),
		'cmp_medium' => array('utm_medium'),
		'cmp_keywords' => array('utm_term'),
		'cmp_content' => array('utm_content'),
		'cmp_name' => array('utm_campaign')
	);

	/**
	 * An array of user IDs to exclude from activity listing in admin UI.
	 * Try not to use it unless absolutely necessary - transparency is very important for operations.
	 *
	 * @var array
	 */
	public static $dont_display_activity_for = array();


	/* ========================================================================
	 *
	 * Gamification
	 *
	 * ===================================================================== */

	/**
	 * @var boolean If set to true, enables gamification features
	 */
	public static $enableGamification = false;

	/**
	 * @var int Size of badge images on the badge listing pages
	 */
	public static $badgeListingSize = 100;

	/**
	 * @var int Size of the badge image on badge page
	 */
	public static $badgeLargeSize = 300;


	/* ========================================================================
	 *
	 * Systems features
	 *
	 * ===================================================================== */

	/**
	 *
	 * [DEPRECATED] A list of features in the system.
	 *
	 * This way of defining features is deprecated, use Feature class instead.
	 *
	 * Key must be a unique integer, usually defined as a constant
	 *
	 * Values of the array are arrays with following elements:
	 * [0] name of the feature (string)
	 * [1] if feature is enabled or disabled globally (boolean)
	 * [2] if featurei s enabled for everybody, overriding account settings (boolean)
	 *
	 * @var array
	 *
	 * @deprecated
	 */
	public static $features = array();


	/* ========================================================================
	 *
	 * Startup API functionality switches
	 *
	 * ===================================================================== */

	/**
	 * @var boolean Set to false to disable registration of new users
	 */
	public static $enableRegistration = true;

	/**
	 * @var string Disabled registration message, e.g. "Registration is disabled." (default) or "Coming soon"
	 */
	public static $registrationDisabledMessage = 'Registration is disabled.';

	/**
	 * @var boolean Enables admin invitations
	 */
	public static $enableInvitations = false;

	/**
	 * @var string Message to be displayed on registration page if person came without an invitation
	 */
	public static $invitationRequiredMessage = 'Please enter your invitation code';

	/**
	 * @var string URL of Terms of Service Document
	 */
	public static $termsOfServiceURL;

	/**
	 * @var string Absolute URL of Terms of Service Document (used in emails and such)
	 */
	public static $termsOfServiceFullURL;

	/**
	 * @var string URL of Privacy Policy Document
	 */
	public static $privacyPolicyURL;

	/**
	 * @var string Absolute URL of Privacy Policy Document (used in emails and such)
	 */
	public static $privacyPolicyFullURL;

	/**
	 * Version of the Terms Of Service Document users consent to when signing up,
	 * increment it when you change TOS document contents
	 *
	 * @var int
	 */
	public static $currentTOSVersion;


	/* ========================================================================
	 *
	 * System emails settings
	 *
	 * ===================================================================== */

	/**
	 * @var string Name and email to send invitations from (e.g. 'User Support <support@example.com>')
	 */
	public static $supportEmailFrom = 'User Support <support@example.com>';

	/**
	 * @var string Reply-To email address for return emails
	 */
	public static $supportEmailReplyTo = 'support@example.com';

	/**
	 * @var string Email agent header (X-Mailer), 'Startup API (PHP/'.phpversion().')' by default.
	 * '
	 * @todo Figure out if there are best practices to be applied here,
	 * e.g. administrator's email address or backlink to the site, etc.
	 */
	public static $supportEmailXMailer;

	/**
	 * @var string Password recovery email subject line
	 */
	public static $passwordRecoveryEmailSubject = 'Your Password';

	/**
	 * @var string Email verification message subject line
	 */
	public static $emailVerificationSubject = 'Please verify your email';

	/**
	 * Set to true if you want to require users to verify their email addresses
	 * before they can log in.
	 *
	 * @var boolean
	 */
	public static $requireVerifiedEmail = false;

	/**
	 * @var int Amount of days email verification code is valid for
	 */
	public static $emailVerificationCodeExpiresInDays = 5;

	/**
	 * Bypasses required email verificatiob flag if set to true
	 *
	 * THIS SHOULD ONLY BE SET ON EMAIL VERIFICATION PAGE
	 * SETTING THIS ON OTHER PAGES CAN RESULT IN SECURITY BREACH
	 *
	 * @var boolean
	 *
	 * @internal
	 */
	public static $IGNORE_REQUIRED_EMAIL_VERIFICATION = false;


	/* ========================================================================
	 *
	 * Accounts
	 *
	 * ===================================================================== */

	/**
	 * Use accounts in addition to users, disable this only if youare not going to
	 * charge subscription fees and 100% sure that you will never have multiple
	 * users using same data in your system.
	 *
	 * @var boolean
	 *
	 * @deprecated
	 */
	public static $useAccounts = true;

	/**
	 * @var string Destination URL used when account is switched (current page by default, if null)
	 */
	public static $accountSwitchDestination = null;


	/* ========================================================================
	 *
	 * OAuth client configuration
	 *
	 * ===================================================================== */

	/**
	 * @var string OAuth application name, not sent if null (default) - apps use registered name most of the time anyway
	 */
	public static $OAuthAppName = null;


	/* ========================================================================
	 *
	 * Hooks
	 *
	 * ===================================================================== */

	/**
	 * @var callable Hook for rendering invitation action UI in admin interface
	 */
	public static $onRenderUserInvitationAction = 'UserConfig::renderUserInvitationAction';

	/**
	 * @var callable Hook for rendering invitation followup action UI in admin interface
	 */
	public static $onRenderUserInvitationFollowUpAction = 'UserConfig::renderUserInvitationFollowUpAction';

	/**
	 * @var callable Formatter for password recovery email
	 */
	public static $onRenderTemporaryPasswordEmail = 'UserConfig::renderTemporaryPasswordEmail';

	/**
	 * @var callable Formatter for email verification message
	 */
	public static $onRenderVerificationCodeEmail = 'UserConfig::renderVerificationCodeEmail';

	/**
	 * @var callable Handler to be called when new user is created, newly created user object is passed in
	 */
	public static $onCreate = null;

	/**
	 * @var callable Hook for rendering extra links on power strip
	 */
	public static $onLoginStripLinks = null;

	/**
	 * @var callable Hook for rendering Terms of Service and Privacy Policy verbiage on signup forms
	 */
	public static $onRenderTOSLinks = 'UserConfig::renderTOSLinks';


	/* ========================================================================
	 *
	 * Subscription data
	 *
	 * ===================================================================== */

	public static $useSubscriptions = false; // works only if $useAccounts is true!!!
	// free plan id, set to user with registration
	public static $plan_free = 'PLAN_FREE';
	// subscription plans list, MUST have free plan index
	public static $PLANS = array(
		'PLAN_FREE' => array(
			'id' => 0,
			'name' => 'Free account',
			'description' => 'Free access with basic functionality',
			'capabilities' => array(
				'individual' => true
			)
		)
	);
	// default plan
	public static $default_plan_slug = 'PLAN_FREE';
	// default schedule
	public static $default_schedule_slug = 'default';


	/* ========================================================================
	 *
	 * Some global functions and default hooks, as well as static initializer
	 *
	 * ===================================================================== */

	/**
	 * Singleton call for getting database connection object
	 *
	 * Creates new connection if none made yet or uses existing connection aleady opened previously.
	 *
	 * Example:
	 * <code>
	 * $db = UserConfig::getDB();
	 * </code>
	 *
	 * @return mysqli
	 *
	 * @throws DBException
	 */
	public static function getDB() {
		if (is_null(self::$db)) {
			self::$db = new mysqli(self::$mysql_host, self::$mysql_user, self::$mysql_password, self::$mysql_db, self::$mysql_port, self::$mysql_socket);
			if (is_null(self::$db) || self::$db->connect_error) {
				throw new DBException(self::$db, null, "Couldn't connect to database");
			}

			if (!self::$db->set_charset('utf8')) {
				error_log("[Startup API] Warning: Can't set utf8 charset for DB connection");
			}
		}

		return self::$db;
	}

	/**
	 * Sets database connection object (mysqli)
	 *
	 * Can be used in users_config.php instead of defining connection parameters,
	 * useful when your app is using same connection which is configured elsewhere
	 *
	 * @param mysqli $db
	 */
	public static function setDB($db) {
		self::$db = $db;
	}

	/**
	 * Default handler for UserConfig::$onRenderTOSLinks hook
	 */
	public static function renderTOSLinks() {
		?><p style="font-size: smaller">By signing up you agree to our <a target="_blank" href="<?php echo UserConfig::$termsOfServiceURL ?>">Terms of Service</a>
			and that you have read our <a target="_blank" href="<?php echo UserConfig::$privacyPolicyURL ?>">Privacy Policy</a>.</p><?php
	}

	/**
	 * Default handler for UserConfig::$onRenderUserInvitationAction hook
	 *
	 * @param string $code Invitation code
	 */
	public static function renderUserInvitationAction($code) {
		?><a class="btn btn-info" href="mailto:?Subject=Invitation&Body=<?php echo UserConfig::$SITEROOTFULLURL ?>users/register.php?invite=<?php echo urlencode($code) ?>"><i class="icon-envelope icon-white"></i> Invite</a><?php
	}

	/**
	 * Default handler for UserConfig::$onRenderUserInvitationFollowUpAction hook
	 *
	 * @param string $code Invitation code
	 */
	public static function renderUserInvitationFollowUpAction($code) {
		?><a class="btn btn-warning"href="mailto:?Subject=Re:%20Invitation&Body=<?php echo UserConfig::$SITEROOTFULLURL ?>users/register.php?invite=<?php echo urlencode($code) ?>"><i class="icon-envelope icon-white"></i> Follow Up</a><?php
	}

	/**
	 * Default handler for UserConfig::$onrenderTemporaryPasswordEmail hook
	 *
	 * Create your own like this to override outgoing password recovery emails.
	 *
	 * The password sent is actually a password recovery token disguised as
	 * temporary password to avoid user confusion with different form fields.
	 *
	 * This token has limited lifespan and gets reset on password update or upon
	 * successful entry of previous (remembered) password.
	 * It's probably the most secure thing you can do for sending
	 * recovery tokens to the user by email.
	 *
	 * @param string $baseurl URL for login page
	 * @param string $username User's login name
	 * @param string $temppass One time password / password recovery token
	 *
	 * @return string Text email body
	 */
	public static function renderTemporaryPasswordEmail($baseurl, $username, $temppass) {
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
		if (!is_null(UserConfig::$appName)) {
			$message .= "\n" . UserConfig::$appName;
		}

		return $message;
	}

	/**
	 * Default handler for UserConfig::onRenderVerificationCodeEmail hook
	 *
	 * Create your own like this to override email verification message
	 *
	 * @param string $verification_link Verification link to be clicked be a user
	 * @param string $code Verification code user can manually type in
	 *
	 * @return string Email messge to send
	 */
	public static function renderVerificationCodeEmail($verification_link, $code) {
		$verify_email_url = UserConfig::$USERSROOTFULLURL . '/verify_email.php';

		$message = <<<EOD
Please verify your email address by clicking on this link:
$verification_link

Or just go to $verify_email_url and enter the code: $code

--
User Support
EOD;
		if (!is_null(UserConfig::$appName)) {
			$message .= "\n" . UserConfig::$appName;
		}

		return $message;
	}

	/**
	 * Loads Startup API module by ID/folder name.
	 *
	 * @param string $modulename Module ID / folder name.
	 *
	 * @throws StartupAPIException
	 */
	public static function loadModule($modulename) {
		if (is_dir(dirname(__FILE__) . '/modules/' . $modulename)) {
			require_once(dirname(__FILE__) . '/modules/' . $modulename . '/index.php');
		} else {
			throw new StartupAPIException('Module ' . $modulename . ' does not exist in ' . dirname(__FILE__) . '/modules/ folder');
		}
	}

	/**
	 * Initializing static variables *BEFORE* user overrides them.
	 *
	 * If any initialization needs to happen after user changes are done,
	 * you have toput them into config.php
	 */
	public static function init() {
		UserConfig::$ROOTPATH = dirname(__FILE__);

		// Chopping of trailing slash which is not supposed to be there in Apache config
		// See: http://httpd.apache.org/docs/2.0/mod/core.html#documentroot
		$docroot = $_SERVER['DOCUMENT_ROOT'];
		if (substr($docroot, -1) == DIRECTORY_SEPARATOR) {
			$docroot = substr($docroot, 0, -1);
		}
		$docrootlength = strlen($docroot);
		UserConfig::$USERSROOTURL = substr(UserConfig::$ROOTPATH, $docrootlength);

		// we assume that package is extracted into the root of the site
		UserConfig::$SITEROOTURL = substr(dirname(UserConfig::$ROOTPATH), $docrootlength) . '/';
		UserConfig::$DEFAULTLOGINRETURN = UserConfig::$SITEROOTURL;
		UserConfig::$DEFAULTLOGOUTRETURN = UserConfig::$SITEROOTURL;
		UserConfig::$DEFAULTREGISTERRETURN = UserConfig::$SITEROOTURL;
		UserConfig::$DEFAULTUPDATEPASSWORDRETURN = UserConfig::$SITEROOTURL;
		UserConfig::$DEFAULT_EMAIL_VERIFIED_RETURN = UserConfig::$SITEROOTURL;

		// Default locations for terms of service and privacy policy documents
		UserConfig::$termsOfServiceURL = UserConfig::$SITEROOTURL . 'terms_of_service.php';
		UserConfig::$termsOfServiceFullURL = UserConfig::$SITEROOTFULLURL . 'terms_of_service.php';
		UserConfig::$privacyPolicyURL = UserConfig::$SITEROOTURL . 'privacy_policy.php';
		UserConfig::$privacyPolicyFullURL = UserConfig::$SITEROOTFULLURL . 'privacy_policy.php';

		if (array_key_exists('HTTP_HOST', $_SERVER)) {
			$host = $_SERVER['HTTP_HOST'];
		} else {
			$host = php_uname('n');
			if (php_sapi_name() !== 'cli') {
				error_log("[Startup API config] Warning: Can't determine site's host name, using $host");
			}
		}

		UserConfig::$SITEROOTFULLURL = 'http://' . $host . UserConfig::$SITEROOTURL;
		UserConfig::$USERSROOTFULLURL = 'http://' . $host . substr(UserConfig::$ROOTPATH, $docrootlength);

		UserConfig::$supportEmailXMailer = 'Startup API (PHP/' . phpversion() . ')';
		if (is_null(UserConfig::$appName)) {
			UserConfig::$supportEmailXMailer = UserConfig::$appName . ' using ' . UserConfig::$appName;
		}

		UserConfig::$header = dirname(__FILE__) . '/header.php';
		UserConfig::$footer = dirname(__FILE__) . '/footer.php';

		UserConfig::$admin_header = dirname(__FILE__) . '/header.php';
		UserConfig::$admin_footer = dirname(__FILE__) . '/footer.php';

		// Built in activities

		/**
		 * Activity ID for login using username and password
		 */
		define('USERBASE_ACTIVITY_LOGIN_UPASS', 1000);
		/**
		 * Activity ID for login using Facebpok
		 */
		define('USERBASE_ACTIVITY_LOGIN_FB', 1001);
		/**
		 * Activity ID for login using Google Friend Connect (deprecated)
		 *
		 * @deprecated
		 */
		define('USERBASE_ACTIVITY_LOGIN_GFC', 1002);

		/**
		 * Activity ID for adding username and password in user settings
		 */
		define('USERBASE_ACTIVITY_ADDED_UPASS', 1003);
		/**
		 * Activity ID for adding Facebook account in user settings
		 */
		define('USERBASE_ACTIVITY_ADDED_FB', 1004);
		/**
		 * Activity ID for adding Google Friend Connect account in user settings (deprecated)
		 *
		 * @deprecated
		 */
		define('USERBASE_ACTIVITY_ADDED_GFC', 1005);

		/**
		 * Activity ID for removing Facebook account in user settings
		 */
		define('USERBASE_ACTIVITY_REMOVED_FB', 1006);
		/**
		 * Activity ID for removing Google Friend Connect account in user settings (deprecated)
		 *
		 * @deprecated
		 */
		define('USERBASE_ACTIVITY_REMOVED_GFC', 1007);

		/**
		 * Activity ID for logging out of the application
		 */
		define('USERBASE_ACTIVITY_LOGOUT', 1008);

		/**
		 * Activity ID for registering using username and password
		 */
		define('USERBASE_ACTIVITY_REGISTER_UPASS', 1009);
		/**
		 * Activity ID for registering using Facebook account
		 */
		define('USERBASE_ACTIVITY_REGISTER_FB', 1010);
		/**
		 * Activity ID for registering using Google Friend Connect account (deprecated)
		 *
		 * @deprecated
		 */
		define('USERBASE_ACTIVITY_REGISTER_GFC', 1011);

		/**
		 * Activity ID for updating user information in user settings
		 */
		define('USERBASE_ACTIVITY_UPDATEUSERINFO', 1012);

		/**
		 * Activity ID for changing password
		 */
		define('USERBASE_ACTIVITY_UPDATEPASS', 1013);
		/**
		 * Activity ID for resetting forgotten password
		 */
		define('USERBASE_ACTIVITY_RESETPASS', 1014);

		/**
		 * Activity ID for returning back within a day
		 */
		define('USERBASE_ACTIVITY_RETURN_DAILY', 1015);
		/**
		 * Activity ID for returning back within a week
		 */
		define('USERBASE_ACTIVITY_RETURN_WEEKLY', 1016);
		/**
		 * Activity ID for returning back within a month
		 */
		define('USERBASE_ACTIVITY_RETURN_MONTHLY', 1017);

		/**
		 * Activity ID for logging in using email address
		 */
		define('USERBASE_ACTIVITY_LOGIN_EMAIL', 1018);
		/**
		 * Activity ID for registering using email address
		 */
		define('USERBASE_ACTIVITY_REGISTER_EMAIL', 1019);

		// Array of activities in the system.
		// Key must be integer (best if specified using a constant).
		// The values are an array with label and "points" value of activity.
		UserConfig::$activities = array(
			USERBASE_ACTIVITY_LOGIN_UPASS => array('Logged in using username and password', 1),
			USERBASE_ACTIVITY_LOGIN_FB => array('Logged in using Facebook', 1),
			USERBASE_ACTIVITY_LOGIN_GFC => array('Logged in using Google Friend Connect', 1),
			USERBASE_ACTIVITY_ADDED_UPASS => array('Added username and password', 1),
			USERBASE_ACTIVITY_ADDED_FB => array('Added Facebook credential', 1),
			USERBASE_ACTIVITY_ADDED_GFC => array('Added Google Friend Connect credential', 1),
			USERBASE_ACTIVITY_REMOVED_FB => array('Removed Facebook Connect', 0),
			USERBASE_ACTIVITY_REMOVED_GFC => array('Removed Google Friend Connect credential', 0),
			USERBASE_ACTIVITY_LOGOUT => array('Logged out', 0),
			USERBASE_ACTIVITY_REGISTER_UPASS => array('Registered using a form', 1),
			USERBASE_ACTIVITY_REGISTER_FB => array('Registered using Facebook', 1),
			USERBASE_ACTIVITY_REGISTER_GFC => array('Registered using Google Friend Connect', 1),
			USERBASE_ACTIVITY_UPDATEUSERINFO => array('Updated user info', 0),
			USERBASE_ACTIVITY_UPDATEPASS => array('Updated their password', 0),
			USERBASE_ACTIVITY_RESETPASS => array('Reset forgotten password', 0),
			USERBASE_ACTIVITY_RETURN_DAILY => array('Returned to the site within a day', 3),
			USERBASE_ACTIVITY_RETURN_WEEKLY => array('Returned to the site within a week', 2),
			USERBASE_ACTIVITY_RETURN_MONTHLY => array('Returned to the site within a month', 1),
			USERBASE_ACTIVITY_LOGIN_EMAIL => array('Logged in using email link', 1),
			USERBASE_ACTIVITY_REGISTER_EMAIL => array('Registered using email', 1)
		);

		UserConfig::$cohort_providers[] = new GenerationCohorts(GenerationCohorts::MONTH);
		UserConfig::$cohort_providers[] = new GenerationCohorts(GenerationCohorts::WEEK);
		UserConfig::$cohort_providers[] = new GenerationCohorts(GenerationCohorts::YEAR);
		UserConfig::$cohort_providers[] = new RegMethodCohorts();
	}

	/**
	 * Old way of keeping track of modules.
	 *
	 * Couldn't reuse it, but keeping it here because it might be still populated in user configs
	 * Use UserConfig::$all_modules array instead of needed
	 *
	 * @var array
	 *
	 * @deprecated
	 */
	public static $modules = array();

}

UserConfig::init();

// Gamification badges initializations
$welcome_badge = new Badge(1, 'basic', 'welcome', 'Welcome!', 'We hope you enjoy the jorney with us.', 'Visiting once is already an achievement!');
$welcome_badge->registerActivityTrigger(array(
	USERBASE_ACTIVITY_REGISTER_UPASS,
	USERBASE_ACTIVITY_REGISTER_FB,
	USERBASE_ACTIVITY_REGISTER_GFC,
	USERBASE_ACTIVITY_REGISTER_EMAIL
		), 1);

$welcome_back_badge = new Badge(2, 'basic', 'welcome_back', 'Welcome Back!', 'Welcome back! Great to see you again.', "We'll be glad seing you again!", array(
			'You logged in again. Come back 10 times to unlock Level 2',
			'You logged in 10 times. Come back 50 times to unlock Level 3',
			'You logged in 50 times. Come back 100 times to unlock Level 4',
			'You logged in 100 times! Keep coming back!'
		));

$welcome_back_badge->registerActivityTrigger(array(
	USERBASE_ACTIVITY_RETURN_DAILY,
	USERBASE_ACTIVITY_RETURN_MONTHLY,
	USERBASE_ACTIVITY_RETURN_WEEKLY
		), 1, 1);
$welcome_back_badge->registerActivityTrigger(array(
	USERBASE_ACTIVITY_RETURN_DAILY,
	USERBASE_ACTIVITY_RETURN_MONTHLY,
	USERBASE_ACTIVITY_RETURN_WEEKLY
		), 10, 2);
$welcome_back_badge->registerActivityTrigger(array(
	USERBASE_ACTIVITY_RETURN_DAILY,
	USERBASE_ACTIVITY_RETURN_MONTHLY,
	USERBASE_ACTIVITY_RETURN_WEEKLY
		), 50, 3);
$welcome_back_badge->registerActivityTrigger(array(
	USERBASE_ACTIVITY_RETURN_DAILY,
	USERBASE_ACTIVITY_RETURN_MONTHLY,
	USERBASE_ACTIVITY_RETURN_WEEKLY
		), 100, 4);
