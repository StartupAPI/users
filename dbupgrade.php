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
 * VERSION 2
 * Adding a field to indicate last time current user was retrieved
*/
$versions[2]['up'][]	= 'ALTER TABLE '.UserConfig::$mysql_prefix.'users ADD last_accessed TIMESTAMP';
$versions[2]['down'][]	= 'ALTER TABLE '.UserConfig::$mysql_prefix.'users DROP COLUMN last_accessed';

/* -------------------------------------------------------------------------------------------------------
 * VERSION 1
 * initial version must be loaded from tables.sql
*/

// creating DBUpgrade object with your database credentials and $versions defined above
// using 'UserBase' namespace to make sure we don't conflict with parent project's dbupgrade
$dbupgrade = new DBUpgrade(UserConfig::getDB(),	$versions, 'UserBase');

require_once(dirname(__FILE__).'/dbupgrade/client.php');
