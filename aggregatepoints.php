<?php
/**
 * Maintenance script to aggregate activity value points for all users.
 *
 * When activity is recorded, only an entry for activity is written into database,
 * activity value points which are configured for the app are not recorded and
 * have to be aggregated separately.
 *
 * Also if you change activity point values, totals must be recalculated,
 * just run this script.
 */
require_once(__DIR__.'/global.php');

try
{
	User::aggregateActivityPoints();
} catch (Exception $e){
	error_log(var_export($e, true));
}
