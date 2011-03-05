<?php
/*
 * Facebook Connect configuration
 * Register your app here: http://www.facebook.com/developers/createapp.php
 * And then uncomment two lines below and copy API Key and App Secret
 */
#require_once(dirname(__FILE__).'/users/modules/facebook/index.php');
#UserConfig::$modules[] = new FacebookAuthenticationModule('...api.key.goes.here...', '...api.secret.goes.here...');

/*
 * Google Friend Connect configuration
 * Register your app here: http://www.google.com/friendconnect/admin/site/setup
 * And then uncomment two lines below and copy the site ID from the URL
 */
#require_once(dirname(__FILE__).'/users/modules/google/index.php');
#UserConfig::$modules[] = new GoogleAuthenticationModule('...site.id.goes.here...');

/*
 * Username and password registration configuration
 * just have these lines or comment them out if you don't want regular form registration
 */
require_once(dirname(__FILE__).'/users/modules/usernamepass/index.php');
UserConfig::$modules[] = new UsernamePasswordAuthenticationModule();

/*
 * You must fill it in with some random string
 * this protects some of your user's data when sent over the network
 * and must be different from other sites
 */
UserConfig::$SESSION_SECRET= '...some.random.characters.go.here...';

UserConfig::$admins = array(  ); // IDs of admins for this instance

/*
 * Database connectivity string
 */
UserConfig::setDB(new mysqli( 'localhost', '...username...', '...password...', '...database...'));

/*
 * Set these to point at your header and footer or leave them commented out to use default ones
 */
#UserConfig::$header = dirname(__FILE__).'/header.php';
#UserConfig::$footer = dirname(__FILE__).'/footer.php';

