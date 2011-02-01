<?php
#require_once('users/modules/facebook/index.php');
#UserConfig::$modules[] = new FacebookAuthenticationModule('...................', '......................');

#require_once('users/modules/google/index.php');
#UserConfig::$modules[] = new GoogleAuthenticationModule('....................');

require_once('users/modules/usernamepass/index.php');
UserConfig::$modules[] = new UsernamePasswordAuthenticationModule();

UserConfig::$SESSION_SECRET= '..................................................';

UserConfig::$admins = array(  ); // IDs of admins for this instance

UserConfig::setDB(new mysqli( 'localhost', 'user1', 'mypass', 'mydb'));

# Set these to point at your header and footer or leave them commented out to use default ones
#UserConfig::$header = dirname(__FILE__).'/header.php';
#UserConfig::$footer = dirname(__FILE__).'/footer.php';

