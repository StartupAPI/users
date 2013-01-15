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

/**
 * User IDs of admins for this instance (to be able to access dashboard at /users/admin/)
 */
UserConfig::$admins = array(
#	1, // usually first user has ID of 1
);

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

/*
 * Uncomment next line to enable debug messages in error_log
 */
#UserConfig::$DEBUG = true;

/**
 * Set these to point at your header and footer or leave them commented out to use default ones
 */
#UserConfig::$header = __DIR__.'/header.php';
#UserConfig::$footer = __DIR__.'/footer.php';

/**
 * Username and password registration configuration
 * just have these lines or comment them out if you don't want regular form registration
 */
UserConfig::loadModule('usernamepass');
new UsernamePasswordAuthenticationModule();

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
 * Register your app here: https://dev.twitter.com/apps/new
 * And then uncomment two lines below and copy API Key and App Secret
 */
#UserConfig::loadModule('twitter');
#UserConfig::$modules[] = new TwitterAuthenticationModule('...api.key.goes.here...', '...api.secret.goes.here...');

/**
 * Status.Net Authentication configuration
 * Register your app with your Status.Net installation
 * And then uncomment two lines below and copy API Key, App Secret
 * as well as provider name and root URL for the site and API
 */
#UserConfig::loadModule('statusnet');
#new StatusNetAuthenticationModule('...api.key.goes.here...', '...api.secret.goes.here...', '...provider.name...', '...base.app.url...', '...base.api.url...');

# Identi.ca's simplified setup (get your keys here: http://identi.ca/settings/oauthapps)
#UserConfig::loadModule('statusnet');
#new StatusNetAuthenticationModule('...identi.ca.api.key.goes.here...', '...identi.ca.api.secret.goes.here...');

/**
 * Google OAuth Authentication configuration
 * Register your app here: https://www.google.com/accounts/ManageDomains
 * Add URL for your site, verify it using one of the methods provided
 * And then uncomment lines below and copy API Key and App Secret
 * Optional 3rd parameter is an array of API scopes you need authorization for.
 * 	See up-to-date list of scopes here: http://code.google.com/apis/gdata/faq.html#AuthScopes
 *	(Google Contacts API scope is required and is included by default)
 */
#UserConfig::loadModule('google_oauth');
#new GoogleOAuthAuthenticationModule(
#	'...OAuth.key.goes.here...',
#	'...OAuth.secret.goes.here...',
#	array(
#		'https://www.google.com/analytics/feeds/',		// Google Analytics Data API
#		'http://www.google.com/base/feeds/',			// Google Base Data API
#		'https://sites.google.com/feeds/',			// Google Sites Data API
#		'http://www.blogger.com/feeds/',			// Blogger Data API
#		'http://www.google.com/books/feeds/',			// Book Search Data API
#		'https://www.google.com/calendar/feeds/',		// Calendar Data API
#		'https://docs.google.com/feeds/',			// Documents List Data API
#		'http://finance.google.com/finance/feeds/',		// Finance Data API
#		'https://mail.google.com/mail/feed/atom/',		// Gmail Atom feed
#		'http://maps.google.com/maps/feeds/',			// Maps Data API
#		'http://picasaweb.google.com/data/',			// Picasa Web Albums Data API
#		'http://www.google.com/sidewiki/feeds/',		// Sidewiki Data API
#		'https://spreadsheets.google.com/feeds/',		// Spreadsheets Data API
#		'http://www.google.com/webmasters/tools/feeds/',	// Webmaster Tools API
#		'http://gdata.youtube.com'				// YouTube Data API
#	)
#);

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