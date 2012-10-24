<?php
require_once(dirname(__FILE__).'/admin.php');

$ADMIN_SECTION = 'activity';

$daily_active_users = User::getDailyActiveUsers();

$selectedactivityid = null;
$selectedactivity = null;
$activityuser = null;
$dates = array();

$showactivities = null;
if (array_key_exists('activityid', $_REQUEST) && $_REQUEST['activityid'] == 'all') {
	$showactivities = 'all';
}

if (!array_key_exists('activityid', $_REQUEST) || $_REQUEST['activityid'] == 'withpoints') {
	$showactivities = 'withpoints';
}

if (array_key_exists('activityid', $_REQUEST) && is_numeric($_REQUEST['activityid'])) {
	$selectedactivityid = $_REQUEST['activityid'];
	$selectedactivity = UserConfig::$activities[$selectedactivityid];

	$BREADCRUMB_EXTRA = $selectedactivity[0];

	$dates = User::getDailyPointsByActivity($selectedactivityid);
} else {
	if (array_key_exists('userid', $_REQUEST)) {
		$activityuser = User::getUser($_REQUEST['userid']);

		$BREADCRUMB_EXTRA = $activityuser->getName();
	}
	$daily_activity = User::getDailyActivityPoints($activityuser);

	foreach ($daily_active_users as $date => $active_users) {

		$dates[$date]['users'] = $active_users;
	}
	foreach ($daily_activity as $record) {
		if (!array_key_exists($record['date'], $dates)) {
			$dates[$record['date']] = array();
		}

		if (!array_key_exists('users', $dates[$record['date']])) {
			$dates[$record['date']]['users'] = 0;
		}

		if (!array_key_exists('points', $dates[$record['date']])) {
			$dates[$record['date']]['points'] = 0;
		}
		$dates[$record['date']]['points'] += $record['total'] * UserConfig::$activities[$record['activity']][1];
	}
}

$total = 0;

require_once(dirname(__FILE__).'/header.php');
?>
<script type='text/javascript' src='swfobject/swfobject/swfobject.js'></script>
<script type='text/javascript' src='http://www.google.com/jsapi'></script>
<script type='text/javascript'>
google.load('visualization', '1', {'packages':['annotatedtimeline', 'corechart']});
google.setOnLoadCallback(function() {
	var data = new google.visualization.DataTable();
	data.addColumn('date', 'Date');
	<?php
	if (is_null($selectedactivity)) {
		?>
		data.addColumn('number', 'Total Points');
		<?php
		if (is_null($activityuser)) {
		?>
			data.addColumn('number', 'Active Users');
		<?php
		}
	} else {
	?>
		data.addColumn('number', 'Number of activities');
	<?php
	}
	?>

	var daily = [
<?php
		$first = true;

		foreach ($dates as $date => $record)
		{
			if (!$first) {
				?>,
				<?php
			}
			else
			{
				$first = false;
			}?>
	[new Date('<?php echo $date?>'),<?php
			if (is_null($selectedactivity)) {
				if (array_key_exists('points', $record) && $record['points'] > 0) {
					echo $record['points'];
				} else {
					echo 0;
				}

				echo ',';

				if (is_null($activityuser)) {
					echo $record['users'];
				}
			} else {
				echo $record ? $record : 0;
			}
			?>]
			<?php
		}
	?>
	];

	data.addRows(daily);
	if (swfobject.hasFlashPlayerVersion("5")) {
		var chart = new google.visualization.AnnotatedTimeLine(document.getElementById('chart_div'));
		chart.draw(data, {
			displayAnnotations: true,
			scaleColumns: [0, 1],
			scaleType: 'allmaximized'
		});
	} else {
		var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
		chart.draw(data, {
			legend: 'top'
		});
	}
});
</script>
<div class="span9">
<form action="" name="activities">
<div>
Filter activities:
<select name="activityid" onchange="document.activities.submit();">
<option value="withpoints"<?php echo $showactivities == 'withpoints' ? ' selected="yes"' : '' ?>>-- all activities with points (default) --</option>
<option value="all"<?php echo $showactivities == 'all' ? ' selected="yes"' : '' ?>>-- all activities --</option>
<?php
uksort(UserConfig::$activities, function($a, $b) {
	if (UserConfig::$activities[$a][1] > UserConfig::$activities[$b][1]) {
		return -1;
	} else if (UserConfig::$activities[$a][1] < UserConfig::$activities[$b][1]) {
		return 1;
	}

	return strcmp(UserConfig::$activities[$a][0], UserConfig::$activities[$b][0]);
});

$stats = User::getActivityStatistics();

foreach (UserConfig::$activities as $id => $activity) {
	if (!array_key_exists($id, $stats)) {
		continue;
	}
?>
	<option value="<?php echo $id ?>"<?php echo $selectedactivityid == $id ? ' selected="yes"' : '' ?>><?php echo $activity[0] ?> (<?php echo $activity[1] ?> points)</option>
<?php } ?>
</select>

<?php if (!is_null($activityuser)) {
	?> for
	<a href="user.php?id=<?php echo $activityuser->getID()?>"><i class="icon-user"></i> <?php echo UserTools::escape($activityuser->getName())?></a>
	<a href="activity.php" class="btn btn-mini">×</a>
<?php
}

$perpage = 20;
$pagenumber = 0;

if (array_key_exists('page', $_GET)) {
	$pagenumber = $_GET['page'];
}

?>
</form>
</div>
<?php
// TODO get activities only for specific activity

if (!is_null($selectedactivity)) {
	$activities = User::getUsersByActivity($selectedactivityid, $pagenumber, $perpage);
} else if (is_null($activityuser)) {
	$activities = User::getUsersActivity($showactivities == 'all', $pagenumber, $perpage);
}
else
{
	$activities = $activityuser->getActivity($showactivities == 'all', $pagenumber, $perpage);
}
?>

<div id='chart_div' style='width: 100%; height: 240px; margin-bottom: 1em'></div>

<ul class="pager">
	<li class="previous <?php if ($pagenumber <= 0 ) {?> disabled<?php } ?>">
		<?php if ($pagenumber > 0) {?>
			<a href="?<?php echo array_key_exists('userid', $_REQUEST) ? 'userid='.urlencode($_REQUEST['userid']).'&' : ''; echo array_key_exists('activityid', $_REQUEST) ? 'activityid='.urlencode($_REQUEST['activityid']).'&' : '' ?>page=<?php echo $pagenumber-1?>">&larr; prev</a>
		<?php } else {?>
			<a href="#">&larr; prev</a>
		<?php } ?>
	</li>

	<li>Page <?php echo $pagenumber+1?></li>

	<li class="next <?php if (count($activities) < $perpage ) {?> disabled<?php } ?>">
		<?php if (count($activities) >= $perpage ) {?>
		<a href="?<?php echo array_key_exists('userid', $_REQUEST) ? 'userid='.urlencode($_REQUEST['userid']).'&' : ''; echo array_key_exists('activityid', $_REQUEST) ? 'activityid='.urlencode($_REQUEST['activityid']).'&' : '' ?>page=<?php echo $pagenumber+1?>">next &gt;&gt;&gt;</a>
		<?php } else {?>
			<a href="#">next &rarr;</a>
		<?php } ?>
	</li>
</ul>

<table class="table table-striped table-bordered" width="100%">
<thead>
<tr><th>Time</th>
<?php
if (is_null($selectedactivity)) {
?>
<th>Activity</th><th>Points</th>
<?php
}

if (is_null($activityuser)) {
?>
<th>User</th>
<?php
}?>
</tr>
</thead>

<tbody>
<?php
$now = time();

foreach ($activities as $activity)
{
	$time = $activity['time'];
	$ago = intval(floor(($now - $time)/86400));

	$tz = date_default_timezone_get();

	$user = User::getUser($activity['user_id']);

	?><tr valign="top">
	<td align="right"><?php echo date('M j, h:iA', $time)?> <span class="pull-right" style="width: 8em"><span class="badge<?php if ($ago <= 5) {?> badge-success<?php }?>"><?php echo $ago?></span> day<?php echo $ago != 1 ? 's' : '' ?> ago</span></td>
	<?php
	if (is_null($selectedactivity)) {
	?>
	<td><a href="?activityid=<?php echo $activity['activity_id']?>"><?php $act = UserConfig::$activities[$activity['activity_id']]; echo $act[0] ?></a></td>
	<td><?php echo $act[1] ?></td>
	<?php
	}

	if (is_null($activityuser)) {
	?>
		<td>

			<a href="user.php?id=<?php echo $user->getID()?>"><i class="icon-user"></i> <?php echo UserTools::escape($user->getName());?></a>
			<a class="btn btn-mini pull-right" href="activity.php?userid=<?php echo $user->getID()?>"><i class="icon-signal"></i> user activity</a>
		</td>
	<?php
	}?>
</tr><?php
}

?>
</tbody>
</table>

<ul class="pager">
	<li class="previous <?php if ($pagenumber <= 0 ) {?> disabled<?php } ?>">
		<?php if ($pagenumber > 0) {?>
			<a href="?<?php echo array_key_exists('userid', $_REQUEST) ? 'userid='.urlencode($_REQUEST['userid']).'&' : ''; echo array_key_exists('activityid', $_REQUEST) ? 'activityid='.urlencode($_REQUEST['activityid']).'&' : '' ?>page=<?php echo $pagenumber-1?>">&larr; prev</a>
		<?php } else {?>
			<a href="#">&larr; prev</a>
		<?php } ?>
	</li>

	<li>Page <?php echo $pagenumber+1?></li>

	<li class="next <?php if (count($activities) < $perpage ) {?> disabled<?php } ?>">
		<?php if (count($activities) >= $perpage ) {?>
		<a href="?<?php echo array_key_exists('userid', $_REQUEST) ? 'userid='.urlencode($_REQUEST['userid']).'&' : ''; echo array_key_exists('activityid', $_REQUEST) ? 'activityid='.urlencode($_REQUEST['activityid']).'&' : '' ?>page=<?php echo $pagenumber+1?>">next &gt;&gt;&gt;</a>
		<?php } else {?>
			<a href="#">next &rarr;</a>
		<?php } ?>
	</li>
</ul>

</div>
<?php
require_once(dirname(__FILE__).'/footer.php');
