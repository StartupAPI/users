<?php
namespace StartupAPI;

/**
 * A full list of settings available in the system, must be in sync with
 * all public properties in UserConfig class from default_config.php file
 */
$config_variables = array(
	array(
		'name' => 'DB connectivity',
		'id' => 'database',
		'groups' => array(
			array(
				'settings' => array(
					new Setting(Setting::TYPE_STRING,'mysql_host', 'MySQL host'),
					new Setting(Setting::TYPE_INT,'mysql_port', 'MySQL port'),
					new Setting(Setting::TYPE_PATH,'mysql_socket', 'MySQL socket'),
					new Setting(Setting::TYPE_STRING,'mysql_db', 'MySQL database name'),
					new Setting(
						Setting::TYPE_STRING,
						'mysql_user',
						'MySQL database user. See access permissions requirements at https://github.com/StartupAPI/users/wiki/Installation#create-mysql-database'
					),
					new Setting(Setting::TYPE_SECRET,'mysql_password', 'MySQL password')
				)
			)
		)
	),
	array(
		'name' => 'Admin UI and access',
		'id' => 'adminui',
		'groups' => array(
			array(
				'settings' => array(
					new Setting(
						Setting::TYPE_USER_ID_ARRAY,
						'admins',
						'Array of integer IDs for site administrators (who have access to admin UI)'
					)
				)
			)
		)
	),
	array(
		'name' => 'Debugging',
		'id' => 'debugging',
		'groups' => array(
			array(
				'settings' => array(
					new Setting(
						Setting::TYPE_BOOLEAN,
						'DEBUG',
						'Debug messages in log files',
						array('true_string' => 'enabled', 'false_string' => 'disabled')
					),
					new Setting(
						Setting::TYPE_BOOLEAN,
						'DEVMODE',
						'Enable / disable developer tools in the UI',
						array('true_string' => 'enabled', 'false_string' => 'disabled')
					)
				)
			)
		)
	),
	array(
		'name' => 'Look and feel',
		'id' => 'lookandfeel',
		'groups' => array(
			array(
				'settings' => array(
					new Setting(
						Setting::TYPE_STRING,
						'appName',
						'Application name'
					),
					new Setting(
						Setting::TYPE_URL,
						'appIconURL',
						'Application icon URL (20x20)'
					),
					new Setting(
						Setting::TYPE_PATH,
						'maillist',
						'File system path to maillist management widget file to be included on profile management page.'
					),
					new Setting(
						Setting::TYPE_PATH,
						'admin_footer',
						'File system path to admin UI footer HTML file.'
					),
					new Setting(
						Setting::TYPE_URL,
						'bootstrapCSS',
						'If specified, StartupAPI::head() will include this Twitter Bootstrap CSS instead of default one'
					),
					new Setting(
						Setting::TYPE_STRING,
						'bootstrapTheme',
						'If specified, StartupAPI::head() will include this Bootswatch Theme instead of default one'
					),
					new Setting(
						Setting::TYPE_STRING_ARRAY,
						'availableBootstrapThemes',
						'Bootstrap themes available'
					),
					new Setting(
						Setting::TYPE_URL,
						'bootstrapAdminCSS',
						'If specified, Admin UI will include this Twitter Bootstrap CSS instead of default one'
					),
					new Setting(
						'array',
						'twig_options',
						'Associative array of Twig environment options'
					)
				),
			),
			array(
				'description' => 'Power strip menu options',
				'settings' => array(
					new Setting(
						Setting::TYPE_BOOLEAN,
						'powerStripShowNavbar',
						'Put power strip in a navbar (disable if you have your own navbar going)'
					),
					new Setting(
						Setting::TYPE_BOOLEAN,
						'powerStripInvertedNavbar',
						'Invert power strip styles'
					),
					new Setting(
						Setting::TYPE_BOOLEAN,
						'powerStripNavPills',
						'Show power strip as Bootstrap nav pills instead of navbar'
					),
					new Setting(
						Setting::TYPE_BOOLEAN,
						'powerStripPullRight',
						'Align power strip to the right'
					)
				)
			),
			array(
				'description' => 'Themes',
				'settings' => array(
					new Setting(
						Setting::TYPE_STRING_ARRAY,
						'available_themes',
						'Array of available theme slugs'
					),
					new Setting(
						Setting::TYPE_STRING,
						'theme',
						'Theme slug for current theme'
					)
				)
			)
		)
	),
	array(
		'name' => 'Paths and URLs',
		'id' => 'pathsandurls',
		'groups' => array(
			array(
				'description' => 'These paths are set automatically, so you might not want to modify them',
				'settings' => array(
					new Setting(
						Setting::TYPE_PATH,
						'ROOTPATH',
						'Root path of the project on the file system'
					),
					new Setting(
						Setting::TYPE_URL,
						'USERSROOTURL',
						'Root URL of Startup API code (relative, e.g. /myapp/users/)'
					),
					new Setting(
						Setting::TYPE_URL,
						'USERSROOTFULLURL',
						'Root URL of Startup API code (full, e.g. http://example.com/myapp/users/)'
					),
					new Setting(
						Setting::TYPE_URL,
						'SITEROOTURL',
						'Root URL of the application using Startup API (relative, e.g. /myapp/)'
					),
					new Setting(
						Setting::TYPE_URL,
						'SITEROOTFULLURL',
						'Root URL of the application using Startup API (full, e.g. http://example.com/myapp/)'
					)
				)
			),
			array(
				'description' => 'Return URLs can be modified if you want to change where users return after each action',
				'settings' => array(
					new Setting(
						Setting::TYPE_URL,
						'DEFAULTLOGINRETURN',
						'Default location URL to return to upon login'
					),
					new Setting(
						Setting::TYPE_URL,
						'DEFAULTLOGOUTRETURN',
						'Default location URL to return to upon logout'
					),
					new Setting(
						Setting::TYPE_URL,
						'DEFAULTREGISTERRETURN',
						'Default location URL to return to upon registration'
					),
					new Setting(
						Setting::TYPE_URL,
						'DEFAULTUPDATEPASSWORDRETURN',
						'Default location URL to return to upon password reset (for username/password auth)'
					),
					new Setting(
						Setting::TYPE_URL,
						'DEFAULT_EMAIL_VERIFIED_RETURN',
						'Default location URL to return to upon successful email verification'
					)
				)
			)
		)
	),
	array(
		'name' => 'Startup API functionality switches',
		'id' => 'startupapifunctionality',
		'groups' => array(
			array(
				'settings' => array(
					array(
						'description' => 'Registration of new users',
						'type' => 'boolean',
						'name' => 'enableRegistration',
						'options' => array('true_string' => 'enabled', 'false_string' => 'disabled')
					),
					array(
						'description' => 'Disabled registration message, e.g. "Registration is disabled." (default) or "Coming soon"',
						'type' => 'string',
						'name' => 'registrationDisabledMessage'
					),
					array(
						'description' => 'Invitation from administrator required for registration',
						'type' => 'boolean',
						'name' => 'adminInvitationOnly',
						'options' => array('true_string' => 'enabled', 'false_string' => 'disabled')
					),
					array(
						'description' => 'User-to-user invitations',
						'type' => 'boolean',
						'name' => 'enableUserInvitations',
						'options' => array('true_string' => 'enabled', 'false_string' => 'disabled')
					),
					array(
						'description' => 'Message to be displayed on registration page if person came without an invitation',
						'type' => 'string',
						'name' => 'invitationRequiredMessage'
					),
					array(
						'description' => 'Invitation menu/section title',
						'type' => 'string',
						'name' => 'userInvitationSectionTitle'
					)
				)
			),
			array(
				'description' => 'You can set URLs of Terms of service and Privacy policy documents and track TOS version for each user by defining current TOS version',
				'settings' => array(
					array(
						'description' => 'URL of Terms of Service Document',
						'type' => 'url',
						'name' => 'termsOfServiceURL'
					),
					array(
						'description' => 'Absolute URL of Terms of Service Document (used in emails and such)',
						'type' => 'url',
						'name' => 'termsOfServiceFullURL'
					),
					array(
						'description' => 'URL of Privacy Policy Document',
						'type' => 'url',
						'name' => 'privacyPolicyURL'
					),
					array(
						'description' => 'Absolute URL of Privacy Policy Document (used in emails and such)',
						'type' => 'url',
						'name' => 'privacyPolicyFullURL'
					),
					array(
						'description' => 'Version of the Terms Of Service Document users consent to when signing up, increment it when you change TOS document contents',
						'type' => 'int',
						'name' => 'currentTOSVersion'
					),
				)
			)
		)
	),
	array(
		'name' => 'System email settings',
		'id' => 'systememail',
		'groups' => array(
			array(
				'settings' => array(
					array(
						'description' => "Name and email to send invitations from (e.g. 'User Support <support@example.com>')",
						'type' => 'string',
						'name' => 'supportEmailFrom'
					),
					array(
						'description' => 'Reply-To email address for return emails',
						'type' => 'string',
						'name' => 'supportEmailReplyTo'
					),
					array(
						'description' => 'Email agent header (X-Mailer)',
						'type' => 'string',
						'name' => 'supportEmailXMailer'
					),
					array(
						'description' => 'Password recovery email subject line',
						'type' => 'string',
						'name' => 'passwordRecoveryEmailSubject'
					),
					array(
						'description' => 'Email verification message subject line',
						'type' => 'string',
						'name' => 'emailVerificationSubject'
					),
					array(
						'description' => 'Require users to verify their email addresses before they can log in.',
						'type' => 'boolean',
						'name' => 'requireVerifiedEmail'
					),
					array(
						'description' => 'Amount of days email verification code is valid for',
						'type' => 'days',
						'name' => 'emailVerificationCodeExpiresInDays'
					)
				)
			)
		)
	),
	array(
		'name' => 'Sessions and cookies',
		'id' => 'sessionsandcookies',
		'groups' => array(
			array(
				'settings' => array(
					array(
						'description' => 'Session secret - must be unique for each installation',
						'type' => 'secret',
						'name' => 'SESSION_SECRET'
					),
					array(
						'description' => "Allow the users's login to be remembered",
						'type' => 'boolean',
						'name' => 'allowRememberMe'
					),
					array(
						'description' => 'Automatically remember user beyond their browser session when they register',
						'type' => 'boolean',
						'name' => 'rememberUserOnRegistration'
					),
					array(
						'description' => 'Time in seconds for long sessions - defaults to 10 years, can be set to relatively short, e.g. 2 weeks if needed',
						'type' => 'seconds',
						'name' => 'rememberMeTime'
					),
					array(
						'description' => 'Checks "remember me" box on registration and login forms',
						'type' => 'boolean',
						'name' => 'rememberMeDefault'
					),
				)
			),
			array(
				'description' => 'You can modify cookie keys to avoid conflicts with other cookies you have',
				'settings' => array(array(
						'description' => 'Cookie name for csrf nonce storage',
						'type' => 'cookie-key',
						'name' => 'csrf_nonce_key'
					),
					array(
						'description' => 'Cookie name for User ID, indicates that user is logged in',
						'type' => 'cookie-key',
						'name' => 'session_userid_key'
					),
					array(
						'description' => 'Cookie name for the URL to return to for redirect-based actions like login, registration and etc.',
						'type' => 'cookie-key',
						'name' => 'session_return_key'
					),
					array(
						'description' => 'Cookie name for User ID of the user being impersonated',
						'type' => 'cookie-key',
						'name' => 'impersonation_userid_key'
					),
					array(
						'description' => 'Facebook session storage cookie name prefix',
						'type' => 'cookie-key',
						'name' => 'facebook_storage_key_prefix'
					),
					array(
						'description' => 'Cookie name for OAuth User ID during the OAuth workflow',
						'type' => 'cookie-key',
						'name' => 'oauth_user_id_key'
					),
					array(
						'description' => "Cookie name for storing referrer between anonymous user's arrival and their registration",
						'type' => 'cookie-key',
						'name' => 'entry_referer_key'
					),
					array(
						'description' => "Cookie name for storing campaign object between anonymous user's arrival and their registration",
						'type' => 'cookie-key',
						'name' => 'entry_cmp_key'
					),
					array(
						'description' => 'Cookie name for last login cookie',
						'type' => 'cookie-key',
						'name' => 'last_login_key'
					)
				)
			)
		)
	),
	array(
		'name' => 'Activity tracking and analytics',
		'id' => 'activity',
		'groups' => array(
			array(
				'settings' => array(
					array(
						'description' => 'An array of activity entries',
						'type' => 'array',
						'name' => 'activities'
					),
					array(
						'description' => 'Only consider users active if they had activities with non-zero value points assigned',
						'type' => 'boolean',
						'name' => 'adminActiveOnlyWithPoints'
					),
					array(
						'description' => 'An array of cohort providers (CohortProvider objects) for cohort analysis',
						'type' => 'CohortProvider[]',
						'name' => 'cohort_providers'
					),
					array(
						'description' => 'Number of minutes for considering a user as returning user, 30 minutes by default',
						'type' => 'minutes',
						'name' => 'last_login_session_length'
					),
					array(
						'description' => 'Array of arrays of URL parameters to be used for campaign tracking.',
						'type' => 'array',
						'name' => 'campaign_variables'
					),
					array(
						'description' => 'Array of match => replacement pairs for rewriting referrers in referrer report in admin UI.',
						'type' => 'array',
						'name' => 'refererRegexes'
					),
					array(
						'description' => 'An array of user IDs to exclude from activity listing in admin UI. Try not to use it unless absolutely necessary - transparency is very important for operations.',
						'type' => 'user-id[]',
						'name' => 'dont_display_activity_for'
					)
				)
			)
		)
	),
	array(
		'name' => 'Gamification',
		'id' => 'gamification',
		'groups' => array(
			array(
				'settings' => array(
					array(
						'description' => 'Enables gamification features',
						'type' => 'boolean',
						'name' => 'enableGamification',
						'options' => array('true_string' => 'enabled', 'false_string' => 'disabled')
					),
					array(
						'description' => 'Size of badge images on the badge listing pages',
						'type' => 'int',
						'name' => 'badgeListingSize'
					),
					array(
						'description' => 'Size of the badge image on badge page',
						'type' => 'int',
						'name' => 'badgeLargeSize'
					)
				)
			)
		)
	),
	array(
		'name' => 'Accounts',
		'id' => 'accounts',
		'groups' => array(
			array(
				'settings' => array(
					array(
						'description' => 'Destination URL used when account is switched (current page by default, if null)',
						'type' => 'url',
						'name' => 'accountSwitchDestination'
					)
				)
			)
		)
	),
	array(
		'name' => 'OAuth client configuration',
		'id' => 'oauthclient',
		'groups' => array(
			array(
				'settings' => array(
					array(
						'description' => 'OAuth application name, not sent if not defined (default) - apps use registered name most of the time anyway',
						'type' => 'string',
						'name' => 'OAuthAppName'
					)
				)
			)
		)
	),
	array(
		'name' => 'Hooks',
		'id' => 'hooks',
		'groups' => array(
			array(
				'settings' => array(
					array(
						'description' => 'Hook for rendering invitation action UI in admin interface',
						'type' => 'callable',
						'name' => 'onRenderUserInvitationAction',
						'options' => array(
							'arguments' => array('invitation')
						)
					),
					array(
						'description' => 'Hook for rendering invitation followup action UI in admin interface',
						'type' => 'callable',
						'name' => 'onRenderUserInvitationFollowUpAction',
						'options' => array(
							'arguments' => array('invitation')
						)
					),
					array(
						'description' => 'Formatter for password recovery email',
						'type' => 'callable',
						'name' => 'onRenderTemporaryPasswordEmail',
						'options' => array(
							'arguments' => array('baseurl', 'username', 'temppass')
						)
					),
					array(
						'description' => 'Formatter for user invitation message placeholder',
						'type' => 'callable',
						'name' => 'onRenderUserInvitationMessagePlaceholder',
						'options' => array(
							'arguments' => array('user')
						)
					),
					array(
						'description' => 'Formatter for email verification message',
						'type' => 'callable',
						'name' => 'onRenderVerificationCodeEmail',
						'options' => array(
							'arguments' => array('verification_link', 'verification_code')
						)
					),
					array(
						'description' => 'Formatter for user-to-user invitation email message',
						'type' => 'callable',
						'name' => 'onRenderInvitationEmailMessage',
						'options' => array(
							'arguments' => array('invitation')
						)
					),
					array(
						'description' => 'Formatter for user-to-user invitation email subject',
						'type' => 'callable',
						'name' => 'onRenderInvitationEmailSubject',
						'options' => array(
							'arguments' => array('invitation')
						)
					),
					array(
						'description' => 'Handler to be called when new user is created, newly created user object is passed in',
						'type' => 'callable',
						'name' => 'onCreate',
						'options' => array(
							'arguments' => array('user')
						)
					),
					array(
						'description' => 'Hook for rendering extra links on power strip',
						'type' => 'callable',
						'name' => 'onLoginStripLinks',
						'options' => array(
							'arguments' => array('current_user', 'current_account')
						)
					),
					array(
						'description' => 'Hook for rendering Terms of Service and Privacy Policy verbiage on signup forms',
						'type' => 'callable',
						'name' => 'onRenderTOSLinks',
					)
				)
			),
		)
	),
	array(
		'name' => 'Subscriptions',
		'id' => 'subscription',
		'groups' => array(
			array(
				'settings' => array(
					array(
						'description' => 'Enables subscription plans and payments management',
						'type' => 'boolean',
						'name' => 'useSubscriptions'
					),
					array(
						'description' => 'Free plan slug which is set to the user when they register without payment',
						'type' => 'string',
						'name' => 'plan_free'
					),
					array(
						'description' => 'Plan configuration array with plan slugs as keys and config parameters as details',
						'type' => 'array',
						'name' => 'PLANS'
					),
					array(
						'description' => 'The slug of the plan that gets assigned to the user by default',
						'type' => 'string',
						'name' => 'default_plan_slug'
					),
					array(
						'description' => 'The slug of the payment schedule that gets assigned to the user by default',
						'type' => 'string',
						'name' => 'default_schedule_slug'
					)
				)
			)
		)
	),
);
