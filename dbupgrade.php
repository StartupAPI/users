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
$versions[_]['up'][]	= "";
$versions[_]['down'][]	= "";
*/

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
