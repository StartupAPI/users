<?
#require_once('users/modules/facebook/index.php');
#UserConfig::$modules[] = new FacebookAuthenticationModule('...................', '......................');

#require_once('users/modules/google/index.php');
#UserConfig::$modules[] = new GoogleAuthenticationModule('....................');

require_once('users/modules/usernamepass/index.php');
UserConfig::$modules[] = new UsernamePasswordAuthenticationModule();

UserConfig::$SESSION_SECRET= '..................................................';

UserConfig::setDB(new mysqli( 'localhost', 'user1', 'mypass', 'mydb'));

UserConfig::$header = dirname(__FILE__).'/header.php';
UserConfig::$footer = dirname(__FILE__).'/footer.php';

