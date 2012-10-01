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
require_once(dirname(__FILE__).'/global.php');

/**
 * Aggregates activity points for users, can be ran as cron job
 * on a daily basis or more often if needed.
 *
 * @package StartupAPI
 * @subpackage Analytics
 *
 * @throws DBException
 */
function aggregatePoints() {
	$db = UserConfig::getDB();

	if ($db->query('CREATE TEMPORARY TABLE activity_points (
	     activity_id int(2) UNSIGNED NOT NULL,
	     points int(4) UNSIGNED NOT NULL)') === TRUE)
	{
		$query = 'INSERT INTO activity_points VALUES';
		$pairs = array();
		foreach (UserConfig::$activities as $id => $activity) {
			$pairs[] = "($id, ".$activity[1].')';
		}
		$query.=' '.implode(', ', $pairs);

		if ($db->query($query) === TRUE)
		{
			if ($db->query('CREATE TEMPORARY TABLE user_activity_points
					SELECT u.id AS user_id, SUM(p.points) AS points
					FROM '.UserConfig::$mysql_prefix.'users u
					INNER JOIN '.UserConfig::$mysql_prefix.'activity a ON u.id = a.user_id
					INNER JOIN activity_points p ON a.activity_id = p.activity_id
					GROUP BY u.id'))
			{
				if ($stmt = $db->prepare('UPDATE '.UserConfig::$mysql_prefix.'users u
						INNER JOIN user_activity_points up ON u.id = up.user_id
						SET u.points = up.points'))
				{
					if (!$stmt->execute())
					{
						throw new DBExecuteStmtException($db, $stmt);
					}

					$stmt->close();
				} else {
					throw new DBException($db);
				}

			} else {
				throw new DBException($db);
			}
		} else {
			throw new DBException($db);
		}
	}
	else
	{
		throw new DBException($db);
	}
}

try
{
	aggregatePoints();
} catch (Exception $e){
	error_log(var_export($e, true));
}
