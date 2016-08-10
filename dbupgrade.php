<?php
/*
 * Copy this script to the folder above and populate $versions array with your migrations
 * For more info see: http://www.dbupgrade.org/Main_Page#Migrations_($versions_array)
 *
 * Note: this script should be versioned in your code repository so it always reflects current code's
 *       requirements for the database structure.
*/
require_once(__DIR__.'/global.php');
require_once(__DIR__.'/dbupgrade/lib.php');

$versions = array();
/* -------------------------------------------------------------------------------------------------------
 * VERSION _
 * ... add version description here ...
*/
/*
$versions[_]['up'][] = "";
$versions[_]['down'][]	= "";
*/

/* -------------------------------------------------------------------------------------------------------
 * VERSION 36
 * corrected type
*/
$versions[36]['up'][] = "ALTER TABLE `u_user_badges` MODIFY COLUMN `time` DATETIME NULL
	COMMENT 'Time when user got the badge'";
$versions[36]['up'][] = "ALTER TABLE `u_users` MODIFY COLUMN `email_verification_code_time` DATETIME NULL DEFAULT NULL
	COMMENT 'Email verification code generation time'";
$versions[36]['up'][]	= 'ALTER TABLE u_users DROP COLUMN last_accessed';

$versions[36]['down'][] = "ALTER TABLE `u_user_badges` MODIFY COLUMN `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  COMMENT 'Time when user got the badge'";
$versions[36]['down'][] = "ALTER TABLE `u_users` MODIFY COLUMN `email_verification_code_time` TIMESTAMP NULL  DEFAULT NULL
	COMMENT 'Email verification code generation time'";
$versions[36]['down'][]	= 'ALTER TABLE u_users ADD last_accessed DATETIME';

/* -------------------------------------------------------------------------------------------------------
 * VERSION 35
 * Adding application settings for a user (free-form JSON)
*/
$versions[35]['up'][] = "ALTER TABLE `u_user_preferences`
	ADD `app_settings_json` BLOB NULL DEFAULT NULL COMMENT  'Application settings'";
$versions[35]['down'][] = "ALTER TABLE `u_user_preferences`
	DROP `app_settings_json`";


/* -------------------------------------------------------------------------------------------------------
 * VERSION 34
 * Setting plans for invitations
*/
$versions[34]['up'][] = "ALTER TABLE u_invitation
  ADD COLUMN `plan_slug` varchar(256) DEFAULT NULL COMMENT 'Invitation subscription plan'";
$versions[34]['down'][] = "ALTER TABLE u_invitation
  DROP COLUMN plan_slug";

// Use boilerplate above to Add new migrations on top, right below this line.

/* -------------------------------------------------------------------------------------------------------
 * VERSION 33
 * Payment Plans and Engines
*/
$versions[33]['up'][] = "ALTER TABLE u_accounts
  ADD COLUMN next_engine_slug varchar(256) DEFAULT NULL AFTER `engine_slug`";
$versions[33]['down'][] = "ALTER TABLE u_accounts
  DROP COLUMN next_engine_slug";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 32
 * Making password and salt optional (for accounts that only have 3rd party auth, for example)
*/
$versions[32]['up'][] = "ALTER TABLE `u_users`
CHANGE `pass` `pass` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT 'Password digest',
CHANGE `salt` `salt` VARCHAR(13) CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT 'Salt'";

$versions[32]['down'][] = "ALTER TABLE `u_users`
CHANGE `pass` `pass` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Password digest',
CHANGE `salt` `salt` VARCHAR(13) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Salt'";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 31
 * Adding account to user invitations
*/
$versions[31]['up'][] = "ALTER TABLE `u_invitation`
	ADD COLUMN account_id INT(10) UNSIGNED DEFAULT NULL COMMENT 'Invitation account ID',
	ADD CONSTRAINT invitation_account_id FOREIGN KEY (account_id)
			REFERENCES `u_accounts` (id) ON UPDATE CASCADE ON DELETE CASCADE";
$versions[31]['down'][]	= "ALTER TABLE `u_invitation`
	DROP account_id, DROP FOREIGN KEY invitation_account_id";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 30
 * Changing ambigous user column name to reflect that it's an ID
*/
$versions[30]['up'][] = "ALTER TABLE  `u_invitation` CHANGE  `user`  `user_id` BIGINT( 10 ) UNSIGNED NULL DEFAULT NULL COMMENT  'User ID'";
$versions[30]['down'][]	= "ALTER TABLE  `u_invitation` CHANGE  `user_id`  `user` BIGINT( 10 ) UNSIGNED NULL DEFAULT NULL COMMENT  'User name'";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 29
 * Updated activity table to use InnoDB engine
*/
$versions[29]['up'][] = "ALTER TABLE `u_activity` ENGINE = INNODB";
$versions[29]['down'][]	= "ALTER TABLE `u_activity` ENGINE = MyISAM";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 28
 * Added OAuth connectivity data from oauth-php and linking table
*/
$versions[28]['up'][] = "CREATE TABLE `u_oauth2_clients` (
	oauth2_client_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'OAuth2 user ID',
	module_slug VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Module slug/id',
	identity VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL
		COMMENT 'String uniquely identifying user on the oauth server',
	userinfo TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL
		COMMENT 'Serialized user information to be used for rendering',
	access_token VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT 'OAuth2 access token',
	access_token_expires DATETIME NULL COMMENT 'OAuth2 access token expiration time',
	refresh_token VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT 'OAuth2 refresh token',
	PRIMARY KEY (oauth2_client_id),
	UNIQUE unique_idenity (module_slug, identity)
) ENGINE = INNODB DEFAULT CHARSET=utf8
	COMMENT = 'OAuth2 clients with their identities and access/refresh tokens';
";

$versions[28]['up'][] = "CREATE TABLE `u_user_oauth2_identity` (
	user_id INT(10) UNSIGNED NOT NULL COMMENT  'Startup API user id',
	oauth2_client_id INT(10) UNSIGNED NOT NULL COMMENT 'OAuth2 client ID',
	PRIMARY KEY (user_id, oauth2_client_id),
	CONSTRAINT oauth2_identity_user_id FOREIGN KEY (user_id)
		REFERENCES `u_users` (id)
		ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT oauth2_identity_oauth2_client_id FOREIGN KEY (oauth2_client_id)
		REFERENCES `u_oauth2_clients` (oauth2_client_id)
		ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = INNODB DEFAULT CHARSET=utf8
	COMMENT = 'Table that links Startup API users and oauth2 users and their access tokens';
";

$versions[28]['down'][] = 'DROP TABLE u_user_oauth2_identity';
$versions[28]['down'][] = 'DROP TABLE u_oauth2_clients';


/* -------------------------------------------------------------------------------------------------------
 * VERSION 27
 * Renaming Manual Payment engine
*/
$versions[27]['up'][] = "RENAME TABLE `u_transaction_details_PaymentEngine_Manual`
	TO `u_transaction_details_manual`";
$versions[27]['up'][] = "UPDATE `u_accounts`
	SET engine_slug = 'manual'
	WHERE engine_slug = 'PaymentEngine_Manual'";

$versions[27]['down'][] = "UPDATE `u_accounts`
	SET engine_slug = 'PaymentEngine_Manual'
	WHERE engine_slug = 'manual'";
$versions[27]['down'][] = "RENAME TABLE `u_transaction_details_manual`
	TO `u_transaction_details_PaymentEngine_Manual`";


/* -------------------------------------------------------------------------------------------------------
 * VERSION 26
 * Removing deprecated Google Friend Connect module
*/
$versions[26]['up'][] = "DROP TABLE `u_googlefriendconnect`";
$versions[26]['down'][] = "CREATE TABLE `u_googlefriendconnect` (
  `user_id` int(10) unsigned NOT NULL COMMENT 'User ID',
  `google_id` varchar(255) NOT NULL COMMENT 'Google Friend Connect ID',
  `userpic` text NOT NULL COMMENT 'Google Friend Connect User picture',
  PRIMARY KEY (`user_id`,`google_id`),
  CONSTRAINT `gfc_user` FOREIGN KEY (`user_id`) REFERENCES `u_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 25
 * Adding transaciton details table for PaymentEngine_Manual
*/
$versions[25]['up'][] = "CREATE TABLE `u_transaction_details_PaymentEngine_Manual` (
  `transaction_id` int(10) UNSIGNED NOT NULL,
  `operator_id` int(10) UNSIGNED NOT NULL,
  `funds_source` varchar(256) DEFAULT NULL,
  `comment` varchar(256) DEFAULT NULL,
  CONSTRAINT `transaction_id_fk1` FOREIGN KEY (`transaction_id`)
			REFERENCES `u_transaction_log` (`transaction_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
$versions[25]['down'][] = "DROP TABLE `u_transaction_details_PaymentEngine_Manual`";


/* -------------------------------------------------------------------------------------------------------
 * VERSION 24
 * Payment Plans and Engines
*/
$versions[24]['up'][] = "ALTER TABLE u_accounts
  CHANGE COLUMN plan plan_slug varchar(256) DEFAULT NULL,
  ADD COLUMN next_plan_slug varchar(256) DEFAULT NULL,
  ADD COLUMN schedule_slug varchar(256) DEFAULT NULL,
  ADD COLUMN next_schedule_slug varchar(256) DEFAULT NULL,
  ADD COLUMN engine_slug varchar(256) DEFAULT NULL,
  ADD COLUMN active tinyint(1) DEFAULT '1',
  ADD COLUMN next_charge datetime DEFAULT NULL";

/*
 * Now, if upgrading from plan numeric IDs to slugs, convert ID to slug using
 * 'id' attribute to plan key in UserConfig::$PLANS as slug
*/
$plan_slugs = array_keys(UserConfig::$PLANS);
foreach ($plan_slugs as $slug) {
	if (array_key_exists('id', UserConfig::$PLANS[$slug])) {
		$versions[24]['up'][] = sprintf("UPDATE u_accounts SET plan_slug = '%s'".
			" WHERE plan_slug = '%d'", $slug, UserConfig::$PLANS[$slug]['id']);
	}
}
$versions[24]['up'][] = "CREATE TABLE u_transaction_log (
  `transaction_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date_time` datetime NOT NULL,
  `account_id` int(10) UNSIGNED NOT NULL,
  `engine_slug` varchar(256) DEFAULT NULL,
  `amount` float DEFAULT NULL,
  `message` text,
  PRIMARY KEY (`transaction_id`),
  KEY `acctid_dt` (`account_id`,`date_time`),
  CONSTRAINT `transaction_acct_id` FOREIGN KEY (`account_id`)
		REFERENCES `u_accounts` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
$versions[24]['up'][] = "CREATE TABLE u_account_charge (
  account_id int(10) UNSIGNED NOT NULL,
  date_time datetime NOT NULL,
  amount float DEFAULT NULL,
  UNIQUE KEY acct_id_datetime (account_id,date_time),
  KEY account_idx (account_id),
  CONSTRAINT `charge_acct_id` FOREIGN KEY (`account_id`)
		REFERENCES `u_accounts` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

/*
 * Now, if upgrading from plan numeric IDs to slugs, convert ID to slug using
 * 'id' attribute to plan key in UserConfig::$PLANS as slug
*/
$plan_slugs = array_keys(UserConfig::$PLANS);
foreach ($plan_slugs as $slug) {
	if (array_key_exists('id', UserConfig::$PLANS[$slug])) {
		$versions[24]['down'][] = sprintf("UPDATE u_accounts SET plan_slug = '%d'".
			" WHERE plan_slug = '%s'", UserConfig::$PLANS[$slug]['id'], $slug);
	}
}
$versions[24]['down'][] = "ALTER TABLE u_accounts
  CHANGE COLUMN plan_slug plan tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Payment plan ID',
  DROP COLUMN next_plan_slug, DROP COLUMN schedule_slug, DROP COLUMN next_schedule_slug,
  DROP COLUMN engine_slug, DROP COLUMN active, DROP COLUMN next_charge";
$versions[24]['down'][] = "DROP TABLE u_account_charge";
$versions[24]['down'][] = "DROP TABLE u_transaction_log";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 23
 * Tracking terms of service
*/
$versions[23]['up'][] = "ALTER TABLE `u_users`
	ADD tos_version INT NULL
	COMMENT  'Version of Terms Of Service User consented to when signed up'
	AFTER regmodule";
$versions[23]['down'][]	= "ALTER TABLE `u_users`
	DROP tos_version";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 22
 * Adding email invitations for users
*/
$versions[22]['up'][] = "ALTER TABLE `u_invitation`
	ADD is_admin_invite TINYINT NOT NULL DEFAULT '1'
		COMMENT  'Whatever it is an invitation sent using admin UI or not'
		AFTER  `issuedby`,
	ADD sent_to_email VARCHAR(255) NULL DEFAULT NULL
		COMMENT 'Email address we sent invitation to'
		AFTER is_admin_invite,
	ADD sent_to_name TEXT
		CHARACTER SET utf8 COLLATE utf8_general_ci
		COMMENT 'Name of the person who got invited'
		AFTER sent_to_email,
	CHANGE sentto sent_to_note
		TEXT CHARACTER SET utf8 COLLATE utf8_general_ci
		NULL DEFAULT NULL
		COMMENT 'Note about who this invitation was sent to'
";
$versions[22]['down'][]	= "ALTER TABLE `u_invitation`
	DROP is_admin_invite,
	DROP sent_to_email,
	DROP sent_to_name,
	CHANGE sent_to_note sentto
		TEXT CHARACTER SET utf8 COLLATE utf8_general_ci
		NULL DEFAULT NULL
		COMMENT 'Note about who this invitation was sent to'
";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 21
 * Adding missing primary key on account user
*/
$versions[21]['up'][] = 'ALTER TABLE  `u_account_users` ADD PRIMARY KEY (  `account_id` ,  `user_id` )';
$versions[21]['up'][] = 'ALTER TABLE  `u_account_users` DROP KEY user_account';
$versions[21]['down'][]	= 'ALTER TABLE  `u_account_users` ADD KEY `user_account` (`account_id`)';
$versions[21]['down'][]	= 'ALTER TABLE  `u_account_users` DROP PRIMARY KEY';

/* -------------------------------------------------------------------------------------------------------
 * VERSION 20
 * Adding badge timestamp
*/
$versions[20]['up'][] = "ALTER TABLE `u_user_badges`
	ADD time TIMESTAMP NOT NULL COMMENT 'Time when user got the badge'";
$versions[20]['down'][]	= "ALTER TABLE `u_user_badges` DROP time";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 19
 * Invitation issuer is optional
*/
$versions[19]['up'][] = "ALTER TABLE `u_invitation`
CHANGE `issuedby` `issuedby` bigint(10) unsigned DEFAULT NULL COMMENT 'User who issued the invitation'";
$versions[19]['down'][] = "ALTER TABLE `u_invitation`
CHANGE `issuedby` `issuedby` bigint(10) unsigned NOT NULL DEFAULT '1' COMMENT 'User who issued the invitation'";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 18
 * Gamification badges
*/
$versions[18]['up'][] = "CREATE TABLE IF NOT EXISTS `u_user_badges` (
	`user_id` INT(10) UNSIGNED NOT NULL,
	`badge_id` INT(4) NOT NULL,
	`badge_level` INT(4) NOT NULL DEFAULT 1,

	PRIMARY KEY (user_id, badge_id, badge_level),

	CONSTRAINT badge_user
		FOREIGN KEY (user_id)
		REFERENCES u_users (id)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='User badges'";
$versions[18]['down'][]	= "DROP TABLE `u_user_badges`";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 17
 * Adding email verification field
*/
$versions[17]['up'][] = "ALTER TABLE `u_users`
		ADD email_verified TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Is email address verified or not' AFTER email,
		ADD email_verification_code VARCHAR(10) NULL DEFAULT NULL COMMENT 'One time code used to verify users email address' AFTER `email_verified`,
		ADD email_verification_code_time timestamp NULL DEFAULT NULL COMMENT 'Email verification code generation time' AFTER email_verification_code
";
$versions[17]['down'][] = "ALTER TABLE  `u_users` DROP email_verified, DROP email_verification_code, DROP email_verification_code_time";


/* -------------------------------------------------------------------------------------------------------
 * VERSION 16
 * Adding login link code
*/
$versions[16]['up'][] = "ALTER TABLE  `u_users` ADD  `loginlinkcode` VARCHAR( 10 ) NULL DEFAULT NULL COMMENT  'One time code used to login' AFTER  `temppass`";
$versions[16]['down'][] = "ALTER TABLE  `u_users` DROP  `loginlinkcode`";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 15
 * Daily stats cache table
*/

$versions[15]['up'][] = "CREATE TABLE u_admin_daily_stats_cache (
day DATE NOT NULL COMMENT 'Date for which calculations are stored',
active_users INT(10) NOT NULL COMMENT 'Number of active users calculated for this day',
PRIMARY KEY (day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$versions[15]['down'][] = "DROP TABLE u_admin_daily_stats_cache";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 14
 * Adding status field for a user to be able to disable access
*/

$versions[14]['up'][] = "ALTER TABLE `u_users` ADD `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Status of the user (enabled/disabled)' AFTER `id`";

$versions[14]['down'][] = "ALTER TABLE `u_users` DROP `status`";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 13
 * Converting to utf8
*/
$versions[13]['up'][] = "ALTER TABLE `u_users`
CHANGE `regmodule` `regmodule` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Registration module ID',
CHANGE `name` `name` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE `username` `username` VARCHAR(25) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `email` `email` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `pass` `pass` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Password digest',
CHANGE `salt` `salt` VARCHAR(13) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Salt',
CHANGE `temppass` `temppass` VARCHAR(13) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'Temporary password used for password recovery',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `u_accounts`
CHANGE  `name`  `name` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `u_account_features`
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `u_account_users`
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `u_activity`
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `u_cmp`
CHANGE `name` `name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Campaign Name',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `u_cmp_content`
CHANGE `content` `content` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Campaign content (dor A/B testing of different ads)',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `u_cmp_keywords`
CHANGE `keywords` `keywords` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Comma separated list of campaign keywords',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `u_cmp_medium`
CHANGE `medium` `medium` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Campaign Medium (cpc, banners, email, twitter & atc',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `u_cmp_source`
CHANGE `source` `source` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Campaign Source (google, newsletter5, widget1, embedplayer2)',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `u_googlefriendconnect`
CHANGE `google_id` `google_id` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Google Friend Connect ID',
CHANGE `userpic` `userpic` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Google Friend Connect User picture',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `u_invitation`
CHANGE `code` `code` CHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Code',
CHANGE `sentto` `sentto` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'Note about who this invitation was sent to',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `u_user_features`
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `u_user_oauth_identity`
CHANGE `module` `module` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Module id',
CHANGE `identity` `identity` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'String uniquely identifying user on the oauth server',
CHANGE `userinfo` `userinfo` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'Serialized user information to be used for rendering',
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$versions[13]['up'][] = "ALTER TABLE `u_user_preferences`
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";


$versions[13]['down'][] = "ALTER TABLE `u_user_preferences`
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `u_user_oauth_identity`
CHANGE `module` `module` VARCHAR(64) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Module id',
CHANGE `identity` `identity` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'String uniquely identifying user on the oauth server',
CHANGE `userinfo` `userinfo` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'Serialized user information to be used for rendering',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `u_user_features`
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `u_invitation`
CHANGE `code` `code` CHAR(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Code',
CHANGE `sentto` `sentto` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT 'Note about who this invitation was sent to',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `u_googlefriendconnect`
CHANGE `google_id` `google_id` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Google Friend Connect ID',
CHANGE `userpic` `userpic` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Google Friend Connect User picture',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `u_cmp_source`
CHANGE `source` `source` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Campaign Source (google, newsletter5, widget1, embedplayer2)',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `u_cmp_medium`
CHANGE `medium` `medium` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Campaign Medium (cpc, banners, email, twitter & atc',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `u_cmp_keywords`
CHANGE `keywords` `keywords` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Comma separated list of campaign keywords',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `u_cmp_content`
CHANGE `content` `content` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Campaign content (dor A/B testing of different ads)',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `u_cmp`
CHANGE `name` `name` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Campaign Name',
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `u_activity`
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `u_account_users`
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `u_account_features`
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `u_accounts`
CHANGE `name` `name` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci";
$versions[13]['down'][] = "ALTER TABLE `u_users`
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
$versions[12]['up'][] = "DROP TABLE u_oauth_consumer_token";
$versions[12]['up'][] = "CREATE TABLE u_oauth_consumer_token (
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
		REFERENCES u_oauth_consumer_registry (ocr_id)
		ON UPDATE CASCADE ON DELETE CASCADE,

	CONSTRAINT oct_oauth_user_id FOREIGN KEY (oct_usa_id_ref)
		REFERENCES u_user_oauth_identity (oauth_user_id)
		ON UPDATE CASCADE ON DELETE CASCADE
) engine=InnoDB default charset=utf8";

$versions[12]['down'][] = "DROP TABLE u_oauth_consumer_token";
$versions[12]['down'][] = "CREATE TABLE u_oauth_consumer_token (
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
		REFERENCES u_oauth_consumer_registry (ocr_id)
		ON UPDATE CASCADE ON DELETE CASCADE,

	CONSTRAINT oct_oauth_user_id FOREIGN KEY (oct_usa_id_ref)
		REFERENCES u_user_oauth_identity (oauth_user_id)
		ON UPDATE CASCADE ON DELETE CASCADE
) engine=InnoDB default charset=utf8";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 11
 * Tracking registration module (issue 18)
*/
$versions[11]['up'][] = "ALTER TABLE `u_users` ADD `regmodule` VARCHAR( 64 ) NOT NULL COMMENT 'Registration module ID' AFTER `regtime`";
$versions[11]['up'][] = "UPDATE `u_users` SET regmodule = 'google'";
$versions[11]['up'][] = "UPDATE `u_users` SET regmodule = 'userpass' WHERE pass IS NOT NULL AND pass <> ''";
$versions[11]['up'][] = "UPDATE `u_users` SET regmodule = 'facebook' WHERE fb_id IS NOT NULL";
$versions[11]['down'][]	= "ALTER TABLE `u_users` DROP `regmodule`";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 10
 * Storing user data as well
*/
$versions[10]['up'][] = "ALTER TABLE `u_user_oauth_identity` ADD `userinfo` TEXT NULL COMMENT  'Serialized user information to be used for rendering'";
$versions[10]['up'][] = "ALTER TABLE `u_user_oauth_identity` ADD `module` VARCHAR( 64 ) NOT NULL COMMENT 'Module id' AFTER `oauth_user_id`";

$versions[10]['down'][] = "ALTER TABLE `u_user_oauth_identity` DROP `module`";
$versions[10]['down'][]	= "ALTER TABLE `u_user_oauth_identity` DROP `userinfo`";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 9
 * Added OAuth connectivity data from oauth-php and linking table
*/
$versions[9]['up'][] = "CREATE TABLE `u_user_oauth_identity` (
	oauth_user_id INT(11) NOT NULL AUTO_INCREMENT COMMENT 'oauth-php user id',
	user_id INT(10) UNSIGNED DEFAULT NULL COMMENT  'Startup API user id',
	identity TEXT DEFAULT NULL COMMENT 'String uniquely identifying user on the oauth server',
	PRIMARY KEY (oauth_user_id),
	CONSTRAINT oauth_identity_user_id FOREIGN KEY (user_id)
		REFERENCES `u_users` (id)
		ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = INNODB COMMENT =  'Table that links Startup API users and oauth-php users and their consumer tokens';
";
$versions[9]['up'][] = "CREATE TABLE u_oauth_log (
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
		REFERENCES u_user_oauth_identity (oauth_user_id)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$versions[9]['up'][] = "CREATE TABLE u_oauth_consumer_registry (
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
		REFERENCES u_user_oauth_identity (oauth_user_id)
		ON UPDATE CASCADE ON DELETE CASCADE
) engine=InnoDB default charset=utf8";

$versions[9]['up'][] = "CREATE TABLE u_oauth_consumer_token (
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
		REFERENCES u_oauth_consumer_registry (ocr_id)
		ON UPDATE CASCADE ON DELETE CASCADE,

	CONSTRAINT oct_oauth_user_id FOREIGN KEY (oct_usa_id_ref)
		REFERENCES u_user_oauth_identity (oauth_user_id)
		ON UPDATE CASCADE ON DELETE CASCADE
) engine=InnoDB default charset=utf8";

$versions[9]['down'][]	= "DROP TABLE u_oauth_consumer_token";
$versions[9]['down'][]	= "DROP TABLE u_oauth_consumer_registry";
$versions[9]['down'][]	= "DROP TABLE u_oauth_log";
$versions[9]['down'][]	= "DROP TABLE u_user_oauth_identity";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 8
 * More fields for campaign tracking
*/
$versions[8]['up'][]	= "CREATE TABLE u_cmp_source (
id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Campaign source ID',
source VARCHAR(255) UNIQUE NOT NULL COMMENT 'Campaign Source (google, newsletter5, widget1, embedplayer2)',
PRIMARY KEY (id)
) ENGINE = INNODB COMMENT = 'Campaign source'";
$versions[8]['up'][]	= "CREATE TABLE u_cmp_medium (
id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Campaign medium ID',
medium VARCHAR(255) UNIQUE NOT NULL COMMENT 'Campaign Medium (cpc, banners, email, twitter & atc',
PRIMARY KEY (id)
) ENGINE = INNODB COMMENT = 'Campaign medium'";
$versions[8]['up'][]	= "CREATE TABLE u_cmp_keywords (
id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Campaign keyword combination ID',
keywords VARCHAR(255) UNIQUE NOT NULL COMMENT 'Comma separated list of campaign keywords',
PRIMARY KEY (id)
) ENGINE = INNODB COMMENT = 'Campaign keywords'";
$versions[8]['up'][]	= "CREATE TABLE u_cmp_content (
id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Campaign content ID',
content VARCHAR(255) UNIQUE NOT NULL COMMENT 'Campaign content (dor A/B testing of different ads)',
PRIMARY KEY (id)
) ENGINE = INNODB COMMENT = 'Campaign content'";
$versions[8]['up'][]	= "CREATE TABLE u_cmp (
id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Campaign ID',
name VARCHAR(255) UNIQUE NOT NULL COMMENT 'Campaign Name',
PRIMARY KEY (id)
) ENGINE = INNODB COMMENT = 'Campaigns'";
$versions[8]['up'][]	= "ALTER TABLE u_users
	ADD reg_cmp_source_id INT(10) UNSIGNED NULL
		COMMENT 'Campaign Source (google, newsletter5, widget1, embedplayer2)',
		ADD CONSTRAINT `registration_campaign_source` FOREIGN KEY (`reg_cmp_source_id`)
			REFERENCES `u_cmp_source` (`id`) ON UPDATE CASCADE,
	ADD reg_cmp_medium_id INT(10) UNSIGNED NULL
		COMMENT 'Campaign Medium (cpc, banners, email, twitter & atc)',
		ADD CONSTRAINT `registration_campaign_medium` FOREIGN KEY (`reg_cmp_medium_id`)
			REFERENCES `u_cmp_medium` (`id`) ON UPDATE CASCADE,
	ADD reg_cmp_keywords_id INT(10) UNSIGNED NULL
		COMMENT 'Campaign Term (paid campaign keywords)',
		ADD CONSTRAINT `registration_campaign_keywords` FOREIGN KEY (`reg_cmp_keywords_id`)
			REFERENCES `u_cmp_keywords` (`id`) ON UPDATE CASCADE,
	ADD reg_cmp_content_id INT(10) UNSIGNED NULL
		COMMENT 'Campaign Content (for differentiating ads)',
		ADD CONSTRAINT `registration_campaign_content` FOREIGN KEY (`reg_cmp_content_id`)
			REFERENCES `u_cmp_content` (`id`) ON UPDATE CASCADE,
	ADD reg_cmp_name_id INT(10) UNSIGNED NULL
		COMMENT 'Campaign Name',
		ADD CONSTRAINT `registration_campaign_name` FOREIGN KEY (`reg_cmp_name_id`)
			REFERENCES `u_cmp` (`id`) ON UPDATE CASCADE";

$versions[8]['down'][]	= "ALTER TABLE u_users
	DROP reg_cmp_source_id, DROP FOREIGN KEY registration_campaign_source,
	DROP reg_cmp_medium_id, DROP FOREIGN KEY registration_campaign_medium,
	DROP reg_cmp_keywords_id, DROP FOREIGN KEY registration_campaign_keywords,
	DROP reg_cmp_content_id, DROP FOREIGN KEY registration_campaign_content,
	DROP reg_cmp_name_id, DROP FOREIGN KEY registration_campaign_name";
$versions[8]['down'][]	= "DROP TABLE u_cmp";
$versions[8]['down'][]	= "DROP TABLE u_cmp_content";
$versions[8]['down'][]	= "DROP TABLE u_cmp_keywords";
$versions[8]['down'][]	= "DROP TABLE u_cmp_medium";
$versions[8]['down'][]	= "DROP TABLE u_cmp_source";
/* -------------------------------------------------------------------------------------------------------
 * VERSION 7
 * Should be null for registrations that don't have referals
*/
$versions[7]['up'][]	= "ALTER TABLE u_users CHANGE `referer` `referer` BLOB NULL COMMENT 'Page user came from when registered'";
$versions[7]['up'][]    = "UPDATE u_users SET referer = NULL WHERE referer = ''";

$versions[7]['down'][]	= "UPDATE u_users SET referer = '' WHERE referer IS NULL";
$versions[7]['down'][]	= "ALTER TABLE u_users CHANGE `referer` `referer` BLOB NOT NULL COMMENT  'Page user came from when registered'";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 6
 * Adding referal tracking
*/
$versions[6]['up'][]	= "ALTER TABLE u_users ADD  `referer` BLOB NOT NULL COMMENT  'Page user came from when registered'";
$versions[6]['down'][]	= "ALTER TABLE u_users DROP  `referer`";

/* -------------------------------------------------------------------------------------------------------
 * VERSION 5
 * Reducing the size of the email address
*/
$versions[5]['up'][]	= "ALTER TABLE u_users CHANGE  `email`  `email` VARCHAR( 255 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL";
// not downgrading it as it seems to cause troubles with some versions of MySQL

/* -------------------------------------------------------------------------------------------------------
 * VERSION 4
 * Adding feature tracking
*/
$versions[4]['up'][]	= "CREATE TABLE u_account_features (
`account_id` INT( 10 ) UNSIGNED NOT NULL COMMENT  'User ID',
`feature_id` INT( 2 ) UNSIGNED NOT NULL COMMENT  'Feature ID',
PRIMARY KEY (  `account_id` ,  `feature_id` )
) ENGINE = INNODB COMMENT = 'Keeps feature list for all users'";
$versions[4]['up'][]	= "CREATE TABLE u_user_features (
`user_id` INT( 10 ) UNSIGNED NOT NULL COMMENT  'User ID',
`feature_id` INT( 2 ) UNSIGNED NOT NULL COMMENT  'Feature ID',
PRIMARY KEY (  `user_id` ,  `feature_id` )
) ENGINE = INNODB COMMENT = 'Keeps feature list for all users'";
$versions[4]['down'][]	= 'DROP TABLE u_user_features';
$versions[4]['down'][]	= 'DROP TABLE u_account_features';

/* -------------------------------------------------------------------------------------------------------
 * VERSION 3
 * Adding user points counter
*/
$versions[3]['up'][]	= 'ALTER TABLE u_users ADD points INT(10) UNSIGNED NOT NULL DEFAULT 0';
$versions[3]['down'][]	= 'ALTER TABLE u_users DROP COLUMN points';

/* -------------------------------------------------------------------------------------------------------
 * VERSION 2
 * Adding a field to indicate last time current user was retrieved
*/
$versions[2]['up'][]	= 'ALTER TABLE u_users ADD last_accessed TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP';
$versions[2]['down'][]	= 'ALTER TABLE u_users DROP COLUMN last_accessed';

/* -------------------------------------------------------------------------------------------------------
 * VERSION 1
 * initial setup, mimicking tables.sql
*/
$versions[1]['up'][] = "CREATE TABLE `u_users` (
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
$versions[1]['down'][]  = "DROP TABLE `u_users`";

$versions[1]['up'][] = "CREATE TABLE `u_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` text,
  `plan` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Payment plan ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
$versions[1]['down'][]  = "DROP TABLE `u_accounts`";

$versions[1]['up'][] = "CREATE TABLE `u_account_users` (
  `account_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `role` tinyint(4) unsigned NOT NULL DEFAULT '0',
  KEY `user_account` (`account_id`),
  KEY `account_user` (`user_id`),
  CONSTRAINT `account_user` FOREIGN KEY (`user_id`)
	REFERENCES `u_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `u_account_users_ibfk_1` FOREIGN KEY (`account_id`)
	REFERENCES `u_accounts` (`id`),
  CONSTRAINT `u_account_users_ibfk_2` FOREIGN KEY (`user_id`)
	REFERENCES `u_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
$versions[1]['down'][] = "DROP TABLE `u_account_users`";

$versions[1]['up'][] = "CREATE TABLE `u_activity` (
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Time of activity',
  `user_id` int(10) unsigned NOT NULL COMMENT 'User ID',
  `activity_id` int(2) unsigned NOT NULL COMMENT 'Activity ID',
  KEY `time` (`time`),
  KEY `user_id` (`user_id`),
  KEY `activity_id` (`activity_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Stores user activities'";
$versions[1]['down'][] = "DROP TABLE `u_activity`";

$versions[1]['up'][] = "CREATE TABLE `u_googlefriendconnect` (
  `user_id` int(10) unsigned NOT NULL COMMENT 'User ID',
  `google_id` varchar(255) NOT NULL COMMENT 'Google Friend Connect ID',
  `userpic` text NOT NULL COMMENT 'Google Friend Connect User picture',
  PRIMARY KEY (`user_id`,`google_id`),
  CONSTRAINT `gfc_user` FOREIGN KEY (`user_id`)
	REFERENCES `u_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
$versions[1]['down'][] = "DROP TABLE `u_googlefriendconnect`";

$versions[1]['up'][] = "CREATE TABLE `u_invitation` (
  `code` char(10) NOT NULL COMMENT 'Code',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When invitation was created',
  `issuedby` bigint(10) unsigned NOT NULL DEFAULT '1' COMMENT 'User who issued the invitation',
  `sentto` text COMMENT 'Note about who this invitation was sent to',
  `user` bigint(10) unsigned DEFAULT NULL COMMENT 'User name',
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
$versions[1]['down'][] = "DROP TABLE `u_invitation`";

$versions[1]['up'][] = "CREATE TABLE `u_user_preferences` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `current_account_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `preference_current_account` (`current_account_id`),
  CONSTRAINT `preference_user` FOREIGN KEY (`user_id`)
	REFERENCES `u_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`)
	REFERENCES `u_users` (`id`),
  CONSTRAINT `user_preferences_ibfk_2` FOREIGN KEY (`current_account_id`)
	REFERENCES `u_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
$versions[1]['down'][] = "DROP TABLE `u_user_preferences`";


// creating DBUpgrade object with your database credentials and $versions defined above
// using 'UserBase' namespace to make sure we don't conflict with parent project's dbupgrade
$dbupgrade = new DBUpgrade(UserConfig::getDB(),	$versions, array(
	'namespace' => 'UserBase',
	'prefix' => 'u_'
));

if (!isset($dbupgrade_interactive) || $dbupgrade_interactive) {
	require_once(__DIR__.'/dbupgrade/client.php');
}
