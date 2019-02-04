<?php
/**
 * You must fill it in with some random string
 * this protects some of your user's data when sent over the network
 * and must be different from other sites
 */
UserConfig::$SESSION_SECRET= '...type.some.random.characters.here...';

/**
 * Database connectivity
 */
UserConfig::$mysql_db = '...database...';
UserConfig::$mysql_user = '...username...';
UserConfig::$mysql_password = '...password...';
#UserConfig::$mysql_host = 'localhost';
#UserConfig::$mysql_port = 3306;
#UserConfig::$mysql_socket = '/tmp/mysql.sock'; // in case you are using socket to connect

/**
 * User IDs of admins for this instance (to be able to access dashboard at /users/admin/)
 */
#UserConfig::$admins[] = 1; // usually first user has ID of 1

/*
 * Name of your application to be used in UI and emails to users
 */
#UserConfig::$appName = '';

/*
 * It is usually important to obtain Terms of Service and Privacy Policy consent
 * from your users, but you should consult your lawyer before launching the app
 * and to obtain a copy of such documents.
 *
 * Uncomment following lines will enable the Terms of Service and Privacy Policy verbiage on sign up forms.
 *
 * You can also override exact verbiage by registering your own UserConfig::$onRenderTOSLinks hook
 */
// Increment this number every time you update TOS and Privacy Policy
// to help you track which user concented to which version
#UserConfig::$currentTOSVersion = 1;

// Terms of Service URLs
#UserConfig::$termsOfServiceURL = UserConfig::$SITEROOTURL . '/terms_of_service.php';
#UserConfig::$termsOfServiceFullURL = UserConfig::$SITEROOTFULLURL . '/terms_of_service.php';

// Privacy Policy URLs
#UserConfig::$privacyPolicyURL = UserConfig::$SITEROOTURL . '/privacy_policy.php';
#UserConfig::$privacyPolicyFullURL = UserConfig::$SITEROOTFULLURL . '/privacy_policy.php';

/*
 * Uncomment next line to require email address verification before users can access the site
 */
#UserConfig::$requireVerifiedEmail = true;

/**
 * StartupAPI theme
 */
#UserConfig::$theme = 'awesome'; // uncomment to enable Bootstrap3-based theme

/*
 * Uncomment next line to enable debug messages in error_log
 */
#UserConfig::$DEBUG = true;

/**
 * Enables developer tools beyond simple debugging
 * like Bootswatch theme switcher in 'awesome' theme, for example
 */
#UserConfig::$DEVMODE = TRUE;

/**
 * Username and password registration configuration
 * just have these lines or comment them out if you don't want regular form registration
 */
UserConfig::loadModule('usernamepass');
new UsernamePasswordAuthenticationModule();

/**
 * Google OAuth2 Authentication configuration
 * Google OAuth2 docs: https://developers.google.com/identity/protocols/OAuth2
 *
 * Register your app here: https://console.developers.google.com/project
 * Go to APIs & Auth -> Consent Screen and fill out app name and URL as well as other fields
 * Go to APIs & Auth -> Credentials and create new client ID, add OAuth2 callback URL
 *			https://<yourhost>/<path_to_startupapi>oauth2_callback.php?module=google
 *
 * Enable Google+ API for the project:
 *      https://console.developers.google.com/apis/library/plus.googleapis.com/
 *
 * And then uncomment lines below and copy Client ID and Client Secret
 * Optional 3rd parameter is an array of API scopes you need authorization for.
 *	See Google's explanation for scopes here: https://developers.google.com/identity/protocols/googlescopes
 * 	See up-to-date list of APIs and scopes here: https://developers.google.com/oauthplayground/
 *
 *  Google Oauth2 "https://www.googleapis.com/auth/userinfo.profile" scope is required
 *  and included by default, but you need to explicitly specify it if you add other scopes.
 *
 *  You can also add "https://www.googleapis.com/auth/userinfo.email" scope
 *  if you want to get user's email address.
 *
 * Your personal OAuth authorizations are recorded here to be used for debugging:
 * https://myaccount.google.com/permissions
 */
#UserConfig::loadModule('google');
#new GoogleAuthenticationModule(
#	'...OAuth2.client.id.goes.here...',
#	'...OAuth2.clientsecret.goes.here...',
#	array(
#		'https://www.googleapis.com/auth/userinfo.profile',
#		'https://www.googleapis.com/auth/userinfo.email',
#	)
#);

/**
 * Facebook Connect configuration
 * Register your app here: https://developers.facebook.com/apps
 * Click "Edit settings" -> "Website with Facebook Login" and enter your site's URL
 * And then uncomment two lines below and copy API Key and App Secret
 */
#UserConfig::loadModule('facebook');
#new FacebookAuthenticationModule('...api.key.goes.here...', '...api.secret.goes.here...');

/**
 * Twitter Authentication configuration
 *
 * Register your app here: https://dev.twitter.com/apps/new
 *
 * - check the box next to "Allow this application to be used to Sign in with Twitter"
 * - enter the value for "Callback URL" to the root URL of your app
 *   (actual value will be sent at runtime - setting it just enables the callbacks)
 *
 * And then uncomment two lines below and copy API Key and App Secret
 */
#UserConfig::loadModule('twitter');
#new TwitterAuthenticationModule('...api.key.goes.here...', '...api.secret.goes.here...');

/**
 * Meetup Authentication configuration
 * Register your app here: http://www.meetup.com/meetup_api/oauth_consumers/
 * Click red "Register OAuth Consumer" button on the right and enter your site's name and URL
 * And then uncomment two lines below and copy API Key and App Secret
 */
#UserConfig::loadModule('meetup');
#new MeetupAuthenticationModule('...OAuth.key.goes.here...', '...OAuth.secret.goes.here...');

/**
 * LinkedIn Authentication configuration
 * Register your app here: https://www.linkedin.com/secure/developer
 * And then uncomment two lines below and copy API Key and Secret Key
 */
#UserConfig::loadModule('linkedin');
#new LinkedInAuthenticationModule('...OAuth.key.goes.here...', '...OAuth.secret.goes.here...');

/**
 * Etsy Authentication configuration
 * Register your app here: https://www.etsy.com/developers/register
 * And then uncomment two lines below and copy API Key and App Secret
 */
#UserConfig::loadModule('etsy');
#new EtsyAuthenticationModule('...OAuth.key.goes.here...', '...OAuth.secret.goes.here...');


/* ========================================================================
 *
 * Subscriptions (experimental)
 *
 * ===================================================================== */

/**
 * Enables subscriptions which are off by default, assumes UserConfig::$useAccounts is set to true (default)
 */
#UserConfig::$useSubscriptions = true;

/**
 * Loads manual payments emodule
 *
 * This can be used for consierge subscriptions or off-band subscriptions managed by an operator
 *
 * More modules to come (e.g. PayPal, Amazon & etc)
 */
#UserConfig::loadModule('manual');
#new ManualPaymentEngine();

/**
 * Configure your subscription plans  and payment schedules in addition to a default free subscription
 */
/*
UserConfig::$PLANS['basic'] = array(
	'name' => 'Basic account',
	'description' => 'Paid access with basic functionality',
	'capabilities' => array(
		'individual' => true
	),
	'base_price' => 3,
	'base_period' => 'month',
	'details_url' => UserConfig::$SITEROOTFULLURL . 'plans/basic.php',
	'downgrade_to' => UserConfig::$default_plan_slug,
	'grace_period' => 15,
	'payment_schedules' => array(
		'monthly' => array(
			'name' => 'Monthly',
			'description' => 'Small monthly fee',
			'charge_amount' => 3,
			'charge_period' => 31,
			'is_default' => 1,
		),
		'6mo' => array(
			'name' => '6 Months',
			'description' => 'Pay every 6 months',
			'charge_amount' => 15,
			'charge_period' => 183
		),
		'yearly' => array(
			'name' => 'Yearly',
			'description' => 'Discounted annual fee',
			'charge_amount' => 25,
			'charge_period' => 365
		)
	)
);
*/
