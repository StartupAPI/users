<?php

/**
 * A full list of settings available in the system, must be in sync with
 * all public properties in UserConfig class from default_config.php file
 */
$config_variables = array(
	array(
		'name' => 'DB connectivity',
		'groups' => array(
			array(
				'id' => 'db',
				'settings' => array(
					array(
						'description' => 'MySQL host',
						'type' => 'string',
						'name' => 'mysql_host'
					),
					array(
						'description' => 'MySQL port',
						'type' => 'int',
						'name' => 'mysql_port'
					),
					array(
						'description' => 'MySQL socket',
						'type' => 'path',
						'name' => 'mysql_socket'
					),
					array(
						'description' => 'MySQL database name',
						'type' => 'string',
						'name' => 'mysql_db'
					),
					array(
						'description' => 'MySQL database user. See access permissions requirements at http://StartupAPI.org/StartupAPI/DB_privileges',
						'type' => 'string',
						'name' => 'mysql_user'
					),
					array(
						'description' => 'MySQL password',
						'type' => 'secret',
						'name' => 'mysql_password'
					),
					array(
						'description' => "MySQL table prefix for all StartupAPI tables ('u_' by default)",
						'type' => 'string',
						'name' => 'mysql_prefix'
					)
				)
			)
		)
	),
	array(
		'name' => 'Admin UI and access',
		'groups' => array(
			array(
				'settings' => array(
					array(
						'description' => 'Array of integer IDs for site administrators (who have access to adin UI)',
						'type' => 'user-id[]',
						'name' => 'admins'
					)
				)
			)
		)
	),
	array(
		'name' => 'Debugging',
		'groups' => array(
			array(
				'settings' => array(
					array(
						'description' => 'Debug messages in log files',
						'type' => 'boolean',
						'name' => 'DEBUG',
						'options' => array('true_string' => 'eabledn', 'false_string' => 'disabled')
					)
				)
			)
		)
	),
	array(
		'name' => 'Headers, footers and look and feel',
		'groups' => array(
			array(
				'settings' => array(
					array(
						'description' => 'Application name',
						'type' => 'string',
						'name' => 'appName'
					),
					array(
						'description' => 'File system path to header HTML file.',
						'type' => 'path',
						'name' => 'header'
					),
					array(
						'description' => 'File system path to header HTML file.',
						'type' => 'path',
						'name' => 'footer'
					),
					array(
						'description' => 'File system path to maillist management widget file to be included on profile management page.',
						'type' => 'path',
						'name' => 'maillist'
					),
					array(
						'description' => 'File system path to admin UI header HTML file.',
						'type' => 'path',
						'name' => 'admin_header'
					),
					array(
						'description' => 'File system path to admin UI footer HTML file.',
						'type' => 'path',
						'name' => 'admin_footer'
					),
					array(
						'description' => 'Array of available theme slugs',
						'type' => 'string[]',
						'name' => 'available_themes'
					),
					array(
						'description' => 'Theme slug for current theme',
						'type' => 'string',
						'name' => 'theme'
					),
					array(
						'description' => 'If specified, StartupAPI::head() will include this Twitter Bootstrap CSS instead of default one',
						'type' => 'url',
						'name' => 'bootstrapCSS'
					),
					array(
						'description' => 'Associative array of Twig environment options',
						'type' => 'array',
						'name' => 'twig_options'
					)
				)
			)
		)
	),
	array(
		'name' => 'Paths and URLs',
		'groups' => array(
			array(
				'description' => 'These paths are set automatically, so you might not want to modify them',
				'settings' => array(
					array(
						'description' => 'Root path of the project on the file system',
						'type' => 'path',
						'name' => 'ROOTPATH'
					),
					array(
						'description' => 'Root URL of Startup API code (relative, e.g. /myapp/users/)',
						'type' => 'url',
						'name' => 'USERSROOTURL'
					),
					array(
						'description' => 'Root URL of Startup API code (full, e.g. http://example.com/myapp/users/)',
						'type' => 'url',
						'name' => 'USERSROOTFULLURL'
					),
					array(
						'description' => 'Root URL of the application using Startup API (relative, e.g. /myapp/)',
						'type' => 'url',
						'name' => 'SITEROOTURL'
					),
					array(
						'description' => 'Root URL of the application using Startup API (full, e.g. http://example.com/myapp/)',
						'type' => 'url',
						'name' => 'SITEROOTFULLURL'
					),
				)
			),
			array(
				'description' => 'Return URLs can be modified if you want to change where users return after each action',
				'settings' => array(
					array(
						'description' => 'Default location URL to return to upon login',
						'type' => 'url',
						'name' => 'DEFAULTLOGINRETURN'
					),
					array(
						'description' => 'Default location URL to return to upon logout',
						'type' => 'url',
						'name' => 'DEFAULTLOGOUTRETURN'
					),
					array(
						'description' => 'Default location URL to return to upon registration',
						'type' => 'url',
						'name' => 'DEFAULTREGISTERRETURN'
					),
					array(
						'description' => 'Default location URL to return to upon password reset (for username/password auth)',
						'type' => 'url',
						'name' => 'DEFAULTUPDATEPASSWORDRETURN'
					),
					array(
						'description' => 'Default location URL to return to upon successful email verification',
						'type' => 'url',
						'name' => 'DEFAULT_EMAIL_VERIFIED_RETURN'
					),
				)
			)
		)
	),
	array(
		'name' => 'Startup API functionality switches',
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
						'description' => 'Require invitations by administrators',
						'type' => 'boolean',
						'name' => 'enableInvitations',
						'options' => array('true_string' => 'enabled', 'false_string' => 'disabled')
					),
					array(
						'description' => 'Message to be displayed on registration page if person came without an invitation',
						'type' => 'string',
						'name' => 'invitationRequiredMessage'
					),
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
		'name' => 'System emails settings',
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
		'groups' => array(
			array(
				'settings' => array(
					array(
						'description' => 'Hook for rendering invitation action UI in admin interface',
						'type' => 'callable',
						'name' => 'onRenderUserInvitationAction',
						'options' => array(
							'arguments' => array('invitation_code')
						)
					),
					array(
						'description' => 'Hook for rendering invitation followup action UI in admin interface',
						'type' => 'callable',
						'name' => 'onRenderUserInvitationFollowUpAction',
						'options' => array(
							'arguments' => array('invitation_code')
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
						'description' => 'Formatter for email verification message',
						'type' => 'callable',
						'name' => 'onRenderVerificationCodeEmail',
						'options' => array(
							'arguments' => array('verification_link', 'verification_code')
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
