<?
require_once('default_config.php');

require_once('modules/facebook/index.php');
UserConfig::$modules[] = new FacebookAuthenticationModule('...................', '......................');

require_once('modules/google/index.php');
UserConfig::$modules[] = new GoogleAuthenticationModule('....................');

require_once('modules/usernamepass/index.php');
UserConfig::$modules[] = new UsernamePasswordAuthenticationModule();

UserConfig::$SESSION_SECRET= '..................................................';

UserConfig::setDB(new mysqli( 'localhost', 'user1', 'mypass', 'mydb'));

UserConfig::$header = dirname(dirname(__FILE__)).'/header.php';
UserConfig::$footer = dirname(dirname(__FILE__)).'/footer.php';

