<?php
/**
 * You must fill it in with some random string
 * this protects some of your user's data when sent over the network
 * and must be different from other sites
 */
UserConfig::$SESSION_SECRET= '...some.random.characters.go.here...';

/**
 * Database connectivity 
 */
UserConfig::$mysql_host = 'localhost';
UserConfig::$mysql_db = '...database...';
UserConfig::$mysql_user = '...username...';
UserConfig::$mysql_password = '...password...';

/**
 * User IDs of admins for this instance (to be able to access dashboard at /users/admin/)
 */
UserConfig::$admins = array(  ); // usually first user has ID of 1

/**
 * Set these to point at your header and footer or leave them commented out to use default ones
 */
#UserConfig::$header = dirname(__FILE__).'/header.php';
#UserConfig::$footer = dirname(__FILE__).'/footer.php';

/**
 * Username and password registration configuration
 * just have these lines or comment them out if you don't want regular form registration
 */
require_once(dirname(__FILE__).'/users/modules/usernamepass/index.php');
new UsernamePasswordAuthenticationModule();

/**
 * Facebook Connect configuration
 * Register your app here: http://www.facebook.com/developers/createapp.php
 * Click "Edit settings" -> "Web Site" and enter your site's URL
 * And then uncomment two lines below and copy API Key and App Secret
 */
#require_once(dirname(__FILE__).'/users/modules/facebook/index.php');
#new FacebookAuthenticationModule('...api.key.goes.here...', '...api.secret.goes.here...');

/**
 * Twitter Authentication configuration
 * Register your app here: https://dev.twitter.com/apps/new
 * And then uncomment two lines below and copy API Key and App Secret
 */
#require_once(dirname(__FILE__).'/users/modules/twitter/index.php');
#UserConfig::$modules[] = new TwitterAuthenticationModule('...api.key.goes.here...', '...api.secret.goes.here...');

/**
 * Google Friend Connect configuration
 * Register your app here: http://www.google.com/friendconnect/admin/site/setup
 * And then uncomment two lines below and copy the site ID from the URL
 */
#require_once(dirname(__FILE__).'/users/modules/google/index.php');
#new GoogleAuthenticationModule('...site.id.goes.here...');

/**
 * Google OAuth Authentication configuration
 * Register your app here: https://www.google.com/accounts/ManageDomains
 * Add URL for your site, verify it using one of the methods provided
 * And then uncomment lines below and copy API Key and App Secret
 * Optional 3rd parameter is an array of API scopes you need authorization for.
 * 	See up-to-date list of scopes here: http://code.google.com/apis/gdata/faq.html#AuthScopes
 *	(Google Contacts API scope is required and is included by default)
 */
#require_once(dirname(__FILE__).'/users/modules/google_oauth/index.php');
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
#require_once(dirname(__FILE__).'/users/modules/meetup/index.php');
#new MeetupAuthenticationModule('...OAuth.key.goes.here...', '...OAuth.secret.goes.here...');

