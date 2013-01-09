<?php
require_once(__DIR__.'/global.php');
require_once(__DIR__.'/classes/User.php');

/**
 * maintenance script to be run on a daily basis
 */

// this one caches the values for daily statistics;
User::getDailyActiveUsers();
