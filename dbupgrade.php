<?php
/*
 * Copy this script to the folder above and populate $versions array with your migrations
 * For more info see: http://www.dbupgrade.org/Main_Page#Migrations_($versions_array)
 *
 * Note: this script should be versioned in your code repository so it always reflects current code's
 *       requirements for the database structure.
*/
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/dbupgrade/lib.php');

$versions = array();
// Add new migrations on top, right below this line.

/* -------------------------------------------------------------------------------------------------------
 * VERSION _
 * ... add version description here ...
*/
/*
$versions[_]['up'][] = "";
$versions[_]['down'][]	= "";
*/

/* -------------------------------------------------------------------------------------------------------
 * VERSION 16
 * Adding login link code
*/
$versions[16]['up'][] = "ALTER TABLE  `".UserConfig::$mysql_prefix."users` ADD  `loginlinkcode` VARCHAR( 10 ) NULL DEFAULT NULL COMMENT  'One time code used to login' AFTER  `temppass`";
$versions[16]['down'][] = "ALTER TABLE  `".UserConfig::$mysql_prefix."users` DROP  `loginlinkcode`";


/* -------------------------------------------------------------------------------------------------------
 * VERSION 15
 * Daily stats cache table
*/

$versions[15]['up'][] = "CREATE TABLE ".UserConfig::$mysql_prefix."admin_daily_stats_cache (
day DATE NOT NULL COMMENT 'Date for which calculations are stored',
active_users INT(10) NOT NULL COMMENT 'Number of active users calculated for this day',
PRIMARY KEY (day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$versions[15]['down'][] = "DROP TABLE ".UserConfig::$mysql_prefix."admin_daily_stats_cache";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 14
 * Adding status field for a user to be able to disable access
*/

$versions[14]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."users` ADD `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Status of the user (enabled/disabled)' AFTER `id`";

$versions[14]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."users` DROP `status`";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 13
 * Converting to utf8
*/
$versions[13]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."users`
CHANGE `regmodule` `regmodule` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Registration module ID',
CHANGE `name` `name` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE `username` `username` VARCHAR(25) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `email` `email` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `pass` `pass` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Password digest',
CHANGE `salt` `salt` VARCHAR(13) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Salt',
CHANGE `temppass` `temppass` VARCHAR(13) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'Temporary password used for password recovery',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."accounts`
CHANGE  `name`  `name` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."account_features`
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."account_users`
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."activity`
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."cmp`
CHANGE `name` `name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Campaign Name',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."cmp_content`
CHANGE `content` `content` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Campaign content (dor A/B testing of different ads)',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."cmp_keywords`
CHANGE `keywords` `keywords` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Comma separated list of campaign keywords',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."cmp_medium`
CHANGE `medium` `medium` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Campaign Medium (cpc, banners, email, twitter & atc',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."cmp_source`
CHANGE `source` `source` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Campaign Source (google, newsletter5, widget1, embedplayer2)',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."googlefriendconnect`
CHANGE `google_id` `google_id` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Google Friend Connect ID',
CHANGE `userpic` `userpic` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Google Friend Connect User picture',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."invitation`
CHANGE `code` `code` CHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Code',
CHANGE `sentto` `sentto` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'Note about who this invitation was sent to',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."user_features`
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."user_oauth_identity`
CHANGE `module` `module` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Module id',
CHANGE `identity` `identity` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'String uniquely identifying user on the oauth server',
CHANGE `userinfo` `userinfo` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'Serialized user information to be used for rendering',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."user_preferences`
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";


$versions[13]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."user_preferences`
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."user_oauth_identity`
CHANGE `module` `module` VARCHAR(64) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Module id',
CHANGE `identity` `identity` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'String uniquely identifying user on the oauth server',
CHANGE `userinfo` `userinfo` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'Serialized user information to be used for rendering',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."user_features`
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."invitation`
CHANGE `code` `code` CHAR(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Code',
CHANGE `sentto` `sentto` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'Note about who this invitation was sent to',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."googlefriendconnect`
CHANGE `google_id` `google_id` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Google Friend Connect ID',
CHANGE `userpic` `userpic` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Google Friend Connect User picture',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."cmp_source`
CHANGE `source` `source` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Campaign Source (google, newsletter5, widget1, embedplayer2)',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."cmp_medium`
CHANGE `medium` `medium` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Campaign Medium (cpc, banners, email, twitter & atc',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."cmp_keywords`
CHANGE `keywords` `keywords` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Comma separated list of campaign keywords',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."cmp_content`
CHANGE `content` `content` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Campaign content (dor A/B testing of different ads)',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."cmp`
CHANGE `name` `name` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Campaign Name',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."activity`
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."account_users`
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."account_features`
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."accounts`
CHANGE `name` `name` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."users`
CHANGE `regmodule` `regmodule` VARCHAR(64) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Registration module ID',
CHANGE `name` `name` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
CHANGE `username` `username` VARCHAR(25) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
CHANGE `email` `email` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
CHANGE `pass` `pass` VARCHAR(40) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Password digest',
CHANGE `salt` `salt` VARCHAR(13) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Salt',
CHANGE `temppass` `temppass` VARCHAR(13) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'Temporary password used for password recovery',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 12
 * Dropping unique key by dropping a table - can't drop it otherwise
 * Will loose all data, unfortunately - hope nobody uses it yet
*/
$versions[12]['up'][] = "DROP TABLE ".UserConfig::$mysql_prefix."oauth_consumer_token";
$versions[12]['up'][] = "CREATE TABLE ".UserConfig::$mysql_prefix."oauth_consumer_token (
	oct_id                  INT(11) NOT NULL AUTO_INCREMENT,
	oct_ocr_id_ref          INT(11) NOT NULL,
	oct_usa_id_ref          INT(11) NOT NULL,
	oct_name                VARCHAR(64) BINARY NOT NULL DEFAULT '',
	oct_token               VARCHAR(255) BINARY NOT NULL,
	oct_token_secret        VARCHAR(255) BINARY NOT NULL,
	oct_token_type          ENUM('request','authorized','access'),
	oct_token_ttl           DATETIME NOT NULL DEFAULT '9999-12-31',
	oct_timestamp           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

	PRIMARY KEY (oct_id),
	UNIQUE KEY (oct_usa_id_ref, oct_ocr_id_ref, oct_token_type, oct_name),
	KEY (oct_token_ttl),

	CONSTRAINT oct_token_server_id
		FOREIGN KEY (oct_ocr_id_ref)
		REFERENCES ".UserConfig::$mysql_prefix."oauth_consumer_registry (ocr_id)
		ON UPDATE CASCADE ON DELETE CASCADE,

	CONSTRAINT oct_oauth_user_id FOREIGN KEY (oct_usa_id_ref)
		REFERENCES ".UserConfig::$mysql_prefix."user_oauth_identity (oauth_user_id)
		ON UPDATE CASCADE ON DELETE CASCADE
) engine=InnoDB default charset=utf8";

$versions[12]['down'][] = "DROP TABLE ".UserConfig::$mysql_prefix."oauth_consumer_token";
$versions[12]['down'][] = "CREATE TABLE ".UserConfig::$mysql_prefix."oauth_consumer_token (
	oct_id                  INT(11) NOT NULL AUTO_INCREMENT,
	oct_ocr_id_ref          INT(11) NOT NULL,
	oct_usa_id_ref          INT(11) NOT NULL,
	oct_name                VARCHAR(64) BINARY NOT NULL DEFAULT '',
	oct_token               VARCHAR(255) BINARY NOT NULL,
	oct_token_secret        VARCHAR(255) BINARY NOT NULL,
	oct_token_type          ENUM('request','authorized','access'),
	oct_token_ttl           DATETIME NOT NULL DEFAULT '9999-12-31',
	oct_timestamp           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

	PRIMARY KEY (oct_id),
	UNIQUE KEY (oct_ocr_id_ref, oct_token),
	UNIQUE KEY (oct_usa_id_ref, oct_ocr_id_ref, oct_token_type, oct_name),
	KEY (oct_token_ttl),

	CONSTRAINT oct_token_server_id
		FOREIGN KEY (oct_ocr_id_ref)
		REFERENCES ".UserConfig::$mysql_prefix."oauth_consumer_registry (ocr_id)
		ON UPDATE CASCADE ON DELETE CASCADE,

	CONSTRAINT oct_oauth_user_id FOREIGN KEY (oct_usa_id_ref)
		REFERENCES ".UserConfig::$mysql_prefix."user_oauth_identity (oauth_user_id)
		ON UPDATE CASCADE ON DELETE CASCADE
) engine=InnoDB default charset=utf8";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 11
 * Tracking registration module (issue 18)
*/
$versions[11]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."users` ADD `regmodule` VARCHAR( 64 ) NOT NULL COMMENT 'Registration module ID' AFTER `regtime`";
$versions[11]['up'][] = "UPDATE `".UserConfig::$mysql_prefix."users` SET regmodule = 'google'";
$versions[11]['up'][] = "UPDATE `".UserConfig::$mysql_prefix."users` SET regmodule = 'userpass' WHERE pass IS NOT NULL AND pass <> ''";
$versions[11]['up'][] = "UPDATE `".UserConfig::$mysql_prefix."users` SET regmodule = 'facebook' WHERE fb_id IS NOT NULL";
$versions[11]['down'][]	= "ALTER TABLE `".UserConfig::$mysql_prefix."users` DROP `regmodule`";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 10
 * Storing user data as well
*/
$versions[10]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."user_oauth_identity` ADD `userinfo` TEXT NULL COMMENT  'Serialized user information to be used for rendering'";
$versions[10]['up'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."user_oauth_identity` ADD `module` VARCHAR( 64 ) NOT NULL COMMENT 'Module id' AFTER `oauth_user_id`";

$versions[10]['down'][] = "ALTER TABLE `".UserConfig::$mysql_prefix."user_oauth_identity` DROP `module`";
$versions[10]['down'][]	= "ALTER TABLE `".UserConfig::$mysql_prefix."user_oauth_identity` DROP `userinfo`";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 9
 * Added OAuth connectivity data from oauth-php and linking table
*/
$versions[9]['up'][] = "CREATE TABLE `".UserConfig::$mysql_prefix."user_oauth_identity` (
	oauth_user_id INT(11) NOT NULL AUTO_INCREMENT COMMENT 'oauth-php user id',
	user_id INT(10) UNSIGNED DEFAULT NULL COMMENT  'UserBase user id',
	identity TEXT DEFAULT NULL COMMENT 'String uniquely identifying user on the oauth server',
	PRIMARY KEY (oauth_user_id),
	CONSTRAINT oauth_identity_user_id FOREIGN KEY (user_id)
		REFERENCES `".UserConfig::$mysql_prefix."users` (id)
		ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = INNODB COMMENT =  'Table that links UserBase users and oauth-php users and their consumer tokens';
";
$versions[9]['up'][] = "CREATE TABLE ".UserConfig::$mysql_prefix."oauth_log (
	olg_id                  INT(11) NOT NULL AUTO_INCREMENT,
	olg_osr_consumer_key    VARCHAR(64) BINARY,
	olg_ost_token           VARCHAR(64) BINARY,
	olg_ocr_consumer_key    VARCHAR(64) BINARY,
	olg_oct_token           VARCHAR(64) BINARY,
	olg_usa_id_ref          INT(11),
	olg_received            TEXT NOT NULL,
	olg_sent                TEXT NOT NULL,
	olg_base_string         TEXT NOT NULL,
	olg_notes               TEXT NOT NULL,
	olg_timestamp           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	olg_remote_ip           BIGINT NOT NULL,

	PRIMARY KEY (olg_id),
	KEY (olg_osr_consumer_key, olg_id),
	KEY (olg_ost_token, olg_id),
	KEY (olg_ocr_consumer_key, olg_id),
	KEY (olg_oct_token, olg_id),
	KEY (olg_usa_id_ref, olg_id),

	CONSTRAINT olg_oauth_user_id FOREIGN KEY (olg_usa_id_ref)
		REFERENCES ".UserConfig::$mysql_prefix."user_oauth_identity (oauth_user_id)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$versions[9]['up'][] = "CREATE TABLE ".UserConfig::$mysql_prefix."oauth_consumer_registry (
	ocr_id                  INT(11) NOT NULL AUTO_INCREMENT,
	ocr_usa_id_ref          INT(11),
	ocr_consumer_key        VARCHAR(128) BINARY NOT NULL,
	ocr_consumer_secret     VARCHAR(128) BINARY NOT NULL,
	ocr_signature_methods   VARCHAR(255) NOT NULL DEFAULT 'HMAC-SHA1,PLAINTEXT',
	ocr_server_uri          VARCHAR(255) NOT NULL,
	ocr_server_uri_host     VARCHAR(128) NOT NULL,
	ocr_server_uri_path     VARCHAR(128) BINARY NOT NULL,

	ocr_request_token_uri   VARCHAR(255) NOT NULL,
	ocr_authorize_uri       VARCHAR(255) NOT NULL,
	ocr_access_token_uri    VARCHAR(255) NOT NULL,
	ocr_timestamp           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

	PRIMARY KEY (ocr_id),
	UNIQUE KEY (ocr_consumer_key, ocr_usa_id_ref, ocr_server_uri),
	KEY (ocr_server_uri),
	KEY (ocr_server_uri_host, ocr_server_uri_path),
	KEY (ocr_usa_id_ref),

	CONSTRAINT ocr_oauth_user_id FOREIGN KEY (ocr_usa_id_ref)
		REFERENCES ".UserConfig::$mysql_prefix."user_oauth_identity (oauth_user_id)
		ON UPDATE CASCADE ON DELETE CASCADE
) engine=InnoDB default charset=utf8";

$versions[9]['up'][] = "CREATE TABLE ".UserConfig::$mysql_prefix."oauth_consumer_token (
	oct_id                  INT(11) NOT NULL AUTO_INCREMENT,
	oct_ocr_id_ref          INT(11) NOT NULL,
	oct_usa_id_ref          INT(11) NOT NULL,
	oct_name                VARCHAR(64) BINARY NOT NULL DEFAULT '',
	oct_token               VARCHAR(255) BINARY NOT NULL,
	oct_token_secret        VARCHAR(255) BINARY NOT NULL,
	oct_token_type          ENUM('request','authorized','access'),
	oct_token_ttl           DATETIME NOT NULL DEFAULT '9999-12-31',
	oct_timestamp           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

	PRIMARY KEY (oct_id),
	UNIQUE KEY (oct_ocr_id_ref, oct_token),
	UNIQUE KEY (oct_usa_id_ref, oct_ocr_id_ref, oct_token_type, oct_name),
	KEY (oct_token_ttl),

	CONSTRAINT oct_token_server_id
		FOREIGN KEY (oct_ocr_id_ref)
		REFERENCES ".UserConfig::$mysql_prefix."oauth_consumer_registry (ocr_id)
		ON UPDATE CASCADE ON DELETE CASCADE,

	CONSTRAINT oct_oauth_user_id FOREIGN KEY (oct_usa_id_ref)
		REFERENCES ".UserConfig::$mysql_prefix."user_oauth_identity (oauth_user_id)
		ON UPDATE CASCADE ON DELETE CASCADE
) engine=InnoDB default charset=utf8";

$versions[9]['down'][]	= "DROP TABLE ".UserConfig::$mysql_prefix."oauth_consumer_token";
$versions[9]['down'][]	= "DROP TABLE ".UserConfig::$mysql_prefix."oauth_consumer_registry";
$versions[9]['down'][]	= "DROP TABLE ".UserConfig::$mysql_prefix."oauth_log";
$versions[9]['down'][]	= "DROP TABLE ".UserConfig::$mysql_prefix."user_oauth_identity";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 8
 * More fields for campaign tracking
*/
$versions[8]['up'][]	= "CREATE TABLE ".UserConfig::$mysql_prefix."cmp_source (
id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Campaign source ID',
source VARCHAR(255) UNIQUE NOT NULL COMMENT 'Campaign Source (google, newsletter5, widget1, embedplayer2)',
PRIMARY KEY (id)
) ENGINE = INNODB COMMENT = 'Campaign source'";
$versions[8]['up'][]	= "CREATE TABLE ".UserConfig::$mysql_prefix."cmp_medium (
id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Campaign medium ID',
medium VARCHAR(255) UNIQUE NOT NULL COMMENT 'Campaign Medium (cpc, banners, email, twitter & atc',
PRIMARY KEY (id)
) ENGINE = INNODB COMMENT = 'Campaign medium'";
$versions[8]['up'][]	= "CREATE TABLE ".UserConfig::$mysql_prefix."cmp_keywords (
id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Campaign keyword combination ID',
keywords VARCHAR(255) UNIQUE NOT NULL COMMENT 'Comma separated list of campaign keywords',
PRIMARY KEY (id)
) ENGINE = INNODB COMMENT = 'Campaign keywords'";
$versions[8]['up'][]	= "CREATE TABLE ".UserConfig::$mysql_prefix."cmp_content (
id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Campaign content ID',
content VARCHAR(255) UNIQUE NOT NULL COMMENT 'Campaign content (dor A/B testing of different ads)',
PRIMARY KEY (id)
) ENGINE = INNODB COMMENT = 'Campaign content'";
$versions[8]['up'][]	= "CREATE TABLE ".UserConfig::$mysql_prefix."cmp (
id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Campaign ID',
name VARCHAR(255) UNIQUE NOT NULL COMMENT 'Campaign Name',
PRIMARY KEY (id)
) ENGINE = INNODB COMMENT = 'Campaigns'";
$versions[8]['up'][]	= "ALTER TABLE ".UserConfig::$mysql_prefix."users
	ADD reg_cmp_source_id INT(10) UNSIGNED NULL
		COMMENT 'Campaign Source (google, newsletter5, widget1, embedplayer2)',
		ADD CONSTRAINT `registration_campaign_source` FOREIGN KEY (`reg_cmp_source_id`)
			REFERENCES `".UserConfig::$mysql_prefix."cmp_source` (`id`) ON UPDATE CASCADE,
	ADD reg_cmp_medium_id INT(10) UNSIGNED NULL
		COMMENT 'Campaign Medium (cpc, banners, email, twitter & atc)',
		ADD CONSTRAINT `registration_campaign_medium` FOREIGN KEY (`reg_cmp_medium_id`)
			REFERENCES `".UserConfig::$mysql_prefix."cmp_medium` (`id`) ON UPDATE CASCADE,
	ADD reg_cmp_keywords_id INT(10) UNSIGNED NULL
		COMMENT 'Campaign Term (paid campaign keywords)',
		ADD CONSTRAINT `registration_campaign_keywords` FOREIGN KEY (`reg_cmp_keywords_id`)
			REFERENCES `".UserConfig::$mysql_prefix."cmp_keywords` (`id`) ON UPDATE CASCADE,
	ADD reg_cmp_content_id INT(10) UNSIGNED NULL
		COMMENT 'Campaign Content (for differentiating ads)',
		ADD CONSTRAINT `registration_campaign_content` FOREIGN KEY (`reg_cmp_content_id`)
			REFERENCES `".UserConfig::$mysql_prefix."cmp_content` (`id`) ON UPDATE CASCADE,
	ADD reg_cmp_name_id INT(10) UNSIGNED NULL
		COMMENT 'Campaign Name',
		ADD CONSTRAINT `registration_campaign_name` FOREIGN KEY (`reg_cmp_name_id`)
			REFERENCES `".UserConfig::$mysql_prefix."cmp` (`id`) ON UPDATE CASCADE";

$versions[8]['down'][]	= "ALTER TABLE ".UserConfig::$mysql_prefix."users
	DROP reg_cmp_source_id, DROP FOREIGN KEY registration_campaign_source,
	DROP reg_cmp_medium_id, DROP FOREIGN KEY registration_campaign_medium,
	DROP reg_cmp_keywords_id, DROP FOREIGN KEY registration_campaign_keywords,
	DROP reg_cmp_content_id, DROP FOREIGN KEY registration_campaign_content,
	DROP reg_cmp_name_id, DROP FOREIGN KEY registration_campaign_name";
$versions[8]['down'][]	= "DROP TABLE ".UserConfig::$mysql_prefix."cmp";
$versions[8]['down'][]	= "DROP TABLE ".UserConfig::$mysql_prefix."cmp_content";
$versions[8]['down'][]	= "DROP TABLE ".UserConfig::$mysql_prefix."cmp_keywords";
$versions[8]['down'][]	= "DROP TABLE ".UserConfig::$mysql_prefix."cmp_medium";
$versions[8]['down'][]	= "DROP TABLE ".UserConfig::$mysql_prefix."cmp_source";
/* -------------------------------------------------------------------------------------------------------
 * VERSION 7
 * Should be null for registrations that don't have referals
*/
$versions[7]['up'][]	= "ALTER TABLE ".UserConfig::$mysql_prefix."users CHANGE `referer` `referer` BLOB NULL COMMENT 'Page user came from when registered'";
$versions[7]['up'][]    = "UPDATE ".UserConfig::$mysql_prefix."users SET referer = NULL WHERE referer = ''";

$versions[7]['down'][]	= "UPDATE ".UserConfig::$mysql_prefix."users SET referer = '' WHERE referer IS NULL";
$versions[7]['down'][]	= "ALTER TABLE ".UserConfig::$mysql_prefix."users CHANGE `referer` `referer` BLOB NOT NULL COMMENT  'Page user came from when registered'";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 6
 * Adding referal tracking
*/
$versions[6]['up'][]	= "ALTER TABLE ".UserConfig::$mysql_prefix."users ADD  `referer` BLOB NOT NULL COMMENT  'Page user came from when registered'";
$versions[6]['down'][]	= "ALTER TABLE ".UserConfig::$mysql_prefix."users DROP  `referer`";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 5
 * Reducing the size of the email address
*/
$versions[5]['up'][]	= "ALTER TABLE ".UserConfig::$mysql_prefix."users CHANGE  `email`  `email` VARCHAR( 255 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL";
// not downgrading it as it seems to cause troubles with some versions of MySQL

/* -------------------------------------------------------------------------------------------------------
 * VERSION 4
 * Adding feature tracking
*/
$versions[4]['up'][]	= "CREATE TABLE ".UserConfig::$mysql_prefix."account_features (
`account_id` INT( 10 ) UNSIGNED NOT NULL COMMENT  'User ID',
`feature_id` INT( 2 ) UNSIGNED NOT NULL COMMENT  'Feature ID',
PRIMARY KEY (  `account_id` ,  `feature_id` )
) ENGINE = INNODB COMMENT = 'Keeps feature list for all users'";
$versions[4]['up'][]	= "CREATE TABLE ".UserConfig::$mysql_prefix."user_features (
`user_id` INT( 10 ) UNSIGNED NOT NULL COMMENT  'User ID',
`feature_id` INT( 2 ) UNSIGNED NOT NULL COMMENT  'Feature ID',
PRIMARY KEY (  `user_id` ,  `feature_id` )
) ENGINE = INNODB COMMENT = 'Keeps feature list for all users'";
$versions[4]['down'][]	= 'DROP TABLE '.UserConfig::$mysql_prefix.'user_features';
$versions[4]['down'][]	= 'DROP TABLE '.UserConfig::$mysql_prefix.'account_features';

/* -------------------------------------------------------------------------------------------------------
 * VERSION 3
 * Adding user points counter
*/
$versions[3]['up'][]	= 'ALTER TABLE '.UserConfig::$mysql_prefix.'users ADD points INT(10) UNSIGNED NOT NULL DEFAULT 0';
$versions[3]['down'][]	= 'ALTER TABLE '.UserConfig::$mysql_prefix.'users DROP COLUMN points';

/* -------------------------------------------------------------------------------------------------------
 * VERSION 2
 * Adding a field to indicate last time current user was retrieved
*/
$versions[2]['up'][]	= 'ALTER TABLE '.UserConfig::$mysql_prefix.'users ADD last_accessed TIMESTAMP';
$versions[2]['down'][]	= 'ALTER TABLE '.UserConfig::$mysql_prefix.'users DROP COLUMN last_accessed';

/* -------------------------------------------------------------------------------------------------------
 * VERSION 1
 * initial setup, mimicking tables.sql
*/
$versions[1]['up'][] = "CREATE TABLE `".UserConfig::$mysql_prefix."users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `regtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Time of registration',
  `name` text NOT NULL,
  `username` varchar(25) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `pass` varchar(40) NOT NULL COMMENT 'Password digest',
  `salt` varchar(13) NOT NULL COMMENT 'Salt',
  `temppass` varchar(13) DEFAULT NULL COMMENT 'Temporary password used for password recovery',
  `temppasstime` timestamp NULL DEFAULT NULL COMMENT 'Temporary password generation time',
  `requirespassreset` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Flag indicating that user must reset their password before using the site',
  `fb_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Facebook user ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `fb_id` (`fb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
$versions[1]['down'][]  = "DROP TABLE `".UserConfig::$mysql_prefix."users`";

$versions[1]['up'][] = "CREATE TABLE `".UserConfig::$mysql_prefix."accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` text,
  `plan` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Payment plan ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
$versions[1]['down'][]  = "DROP TABLE `".UserConfig::$mysql_prefix."accounts`";

$versions[1]['up'][] = "CREATE TABLE `".UserConfig::$mysql_prefix."account_users` (
  `account_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `role` tinyint(4) unsigned NOT NULL DEFAULT '0',
  KEY `user_account` (`account_id`),
  KEY `account_user` (`user_id`),
  CONSTRAINT `account_user` FOREIGN KEY (`user_id`)
	REFERENCES `".UserConfig::$mysql_prefix."users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `".UserConfig::$mysql_prefix."account_users_ibfk_1` FOREIGN KEY (`account_id`)
	REFERENCES `".UserConfig::$mysql_prefix."accounts` (`id`),
  CONSTRAINT `".UserConfig::$mysql_prefix."account_users_ibfk_2` FOREIGN KEY (`user_id`)
	REFERENCES `".UserConfig::$mysql_prefix."users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
$versions[1]['down'][] = "DROP TABLE `".UserConfig::$mysql_prefix."account_users`";

$versions[1]['up'][] = "CREATE TABLE `".UserConfig::$mysql_prefix."activity` (
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Time of activity',
  `user_id` int(10) unsigned NOT NULL COMMENT 'User ID',
  `activity_id` int(2) unsigned NOT NULL COMMENT 'Activity ID',
  KEY `time` (`time`),
  KEY `user_id` (`user_id`),
  KEY `activity_id` (`activity_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Stores user activities'";
$versions[1]['down'][] = "DROP TABLE `".UserConfig::$mysql_prefix."activity`";

$versions[1]['up'][] = "CREATE TABLE `".UserConfig::$mysql_prefix."googlefriendconnect` (
  `user_id` int(10) unsigned NOT NULL COMMENT 'User ID',
  `google_id` varchar(255) NOT NULL COMMENT 'Google Friend Connect ID',
  `userpic` text NOT NULL COMMENT 'Google Friend Connect User picture',
  PRIMARY KEY (`user_id`,`google_id`),
  CONSTRAINT `gfc_user` FOREIGN KEY (`user_id`)
	REFERENCES `".UserConfig::$mysql_prefix."users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
$versions[1]['down'][] = "DROP TABLE `".UserConfig::$mysql_prefix."googlefriendconnect`";

$versions[1]['up'][] = "CREATE TABLE `".UserConfig::$mysql_prefix."invitation` (
  `code` char(10) NOT NULL COMMENT 'Code',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When invitation was created',
  `issuedby` bigint(10) unsigned NOT NULL DEFAULT '1' COMMENT 'User who issued the invitation',
  `sentto` text COMMENT 'Note about who this invitation was sent to',
  `user` bigint(10) unsigned DEFAULT NULL COMMENT 'User name',
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
$versions[1]['down'][] = "DROP TABLE `".UserConfig::$mysql_prefix."invitation`";

$versions[1]['up'][] = "CREATE TABLE `".UserConfig::$mysql_prefix."user_preferences` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `current_account_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `preference_current_account` (`current_account_id`),
  CONSTRAINT `preference_user` FOREIGN KEY (`user_id`)
	REFERENCES `".UserConfig::$mysql_prefix."users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`)
	REFERENCES `".UserConfig::$mysql_prefix."users` (`id`),
  CONSTRAINT `user_preferences_ibfk_2` FOREIGN KEY (`current_account_id`)
	REFERENCES `".UserConfig::$mysql_prefix."accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
$versions[1]['down'][] = "DROP TABLE `".UserConfig::$mysql_prefix."user_preferences`";


// creating DBUpgrade object with your database credentials and $versions defined above
// using 'UserBase' namespace to make sure we don't conflict with parent project's dbupgrade
$dbupgrade = new DBUpgrade(UserConfig::getDB(),	$versions, 'UserBase');

require_once(dirname(__FILE__).'/dbupgrade/client.php');
