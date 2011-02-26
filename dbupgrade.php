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
