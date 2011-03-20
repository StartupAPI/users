<?php
$ADMIN_SECTION = 'activity';
require_once(dirname(__FILE__).'/header.php');

if (array_key_exists('impersonate', $_POST)) {
	$impersonated_user= User::getUser($_POST['impersonate']);
	if ($impersonated_user !== null) {
		$impersonated_user->setSession(false); // always impersonate only for the browser session
		header('Location: '.UserConfig::$DEFAULTLOGINRETURN);
	}
	else
	{
		header('Location: #msg=cantimpersonate');
	}
}

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

	$dates = User::getDailyPointsByActivity($selectedactivityid);
} else {
	if (array_key_exists('userid', $_REQUEST)) {
		$activityuser = User::getUser($_REQUEST['userid']);
	}
	$daily_activity = User::getDailyActivityPoints($activityuser);
	foreach ($daily_active_users as $record) {
		if (!array_key_exists($record['date'], $dates)) {
			$dates[$record['date']] = array();
		}

		if (!array_key_exists('users', $dates[$record['date']])) {
			$dates[$record['date']]['users'] = 0;
		}

		$dates[$record['date']]['users'] += 1;
	}
	foreach ($daily_activity as $record) {
		if (!array_key_exists($record['date'], $dates)) {
			$dates[$record['date']] = array();
		}

		if (!array_key_exists('points', $dates[$record['date']])) {
			$dates[$record['date']]['points'] = 0;
		}
		$dates[$record['date']]['points'] += $record['total'] * UserConfig::$activities[$record['activity']][1];
	}
}

$total = 0;
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
<div id='chart_div' style='width: 100%; height: 240px; margin-bottom: 1em'></div>

<form action="" name="activities">
<div>
Filter activities:
<select name="activityid" onchange="document.activities.submit();">
<option value="withpoints"<?php echo $showactivities == 'withpoints' ? ' selected="yes"' : '' ?>>-- all activities with points (default) --</option>
<option value="all"<?php echo $showactivities == 'all' ? ' selected="yes"' : '' ?>>-- all activities --</option>
<?php
function mostpoints($a, $b) {
	if (UserConfig::$activities[$a][1] > UserConfig::$activities[$b][1]) {
		return -1;
	} else if (UserConfig::$activities[$a][1] < UserConfig::$activities[$b][1]) {
		return 1;
	}

	return strcmp(UserConfig::$activities[$a][0], UserConfig::$activities[$b][0]);
}

uksort(UserConfig::$activities, 'mostpoints');

$stats = User::getActivityStatistics();

foreach (UserConfig::$activities as $id => $activity) {
	if (!array_key_exists($id, $stats)) {
		continue;
	}
?>
	<option value="<?php echo $id ?>"<?php echo $selectedactivityid == $id ? ' selected="yes"' : '' ?>><?php echo $activity[0] ?> (<?php echo $activity[1] ?> points)</option>
<?php } ?>
</select>

Users:
<?php if (is_null($activityuser)) {
	?>all<?php
} else {
	echo $activityuser->getName()?> (<a href=".">reset</a>)<?php
}
?>
</form>
</div>

<table cellpadding="5" cellspacing="0" border="1" width="100%">
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
<?php
$perpage = 20;
$pagenumber = 0;

if (array_key_exists('page', $_GET)) {
	$pagenumber = $_GET['page'];
}

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
<tr><td colspan="4">
<?php
if (count($activities) == $perpage) {
	?><a style="float: right" href="?<?php echo array_key_exists('userid', $_REQUEST) ? 'userid='.urlencode($_REQUEST['userid']).'&' : ''; echo array_key_exists('activityid', $_REQUEST) ? 'activityid='.urlencode($_REQUEST['activityid']).'&' : '' ?>page=<?php echo $pagenumber+1?>">next &gt;&gt;&gt;</a><?php
}
else
{
	?><span style="color: silver; float: right">next &gt;&gt;&gt;</span><?php
}

if ($pagenumber > 0) {
	?><a style="float: left" href="?<?php echo array_key_exists('userid', $_REQUEST) ? 'userid='.urlencode($_REQUEST['userid']).'&' : ''; echo array_key_exists('activityid', $_REQUEST) ? 'activityid='.urlencode($_REQUEST['activityid']).'&' : '' ?>page=<?php echo $pagenumber-1?>">&lt;&lt;&lt;prev</a><?php
}
else
{
	?><span style="color: silver; float: left">&lt;&lt;&lt;prev</span><?php
}
?>
<span style="float: left; margin: 0 2em">Page <?php echo $pagenumber+1?></span>
</td></tr>
<?php
$now = time();

foreach ($activities as $activity)
{
	$time = $activity['time'];
	$ago = intval(floor(($now - $time)/86400));

	$tz = date_default_timezone_get();

	$user = User::getUser($activity['user_id']);

	?><tr valign="top">
	<td align="right"><?php echo date('M j, h:iA', $time)?> (<?php if ($ago <= 5) {?><span style="color: #00<?php echo sprintf('%02s', dechex((4 - $ago) * 150 / 4 + 50))?>00; font-weight: bold"><?php }?><?php echo $ago?> day<?php echo $ago > 1 ? 's' : '' ?> ago<?php if ($ago <= 5) {?></span><?php }?>)</td>
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
		<form name="imp" action="" method="POST">
		<a href="?userid=<?php echo $user->getID()?>"><?php echo $user->getName();?></a>
		<input type="submit" value="impersonate"/><input type="hidden" name="impersonate" value="<?php echo $user->getID()?>"/>
		</form>
		</td>
	<?php
	}?>
</tr><?php
}

?>
<tr><td colspan="6">
<?php
if (count($activities) == $perpage) {
	?><a style="float: right" href="?<?php echo array_key_exists('userid', $_REQUEST) ? 'userid='.urlencode($_REQUEST['userid']).'&' : ''; echo array_key_exists('activityid', $_REQUEST) ? 'activityid='.urlencode($_REQUEST['activityid']).'&' : '' ?>page=<?php echo $pagenumber+1?>">next &gt;&gt;&gt;</a><?php
}
else
{
	?><span style="color: silver; float: right">next &gt;&gt;&gt;</span><?php
}

if ($pagenumber > 0) {
	?><a style="float: left" href="?<?php echo array_key_exists('userid', $_REQUEST) ? 'userid='.urlencode($_REQUEST['userid']).'&' : ''; echo array_key_exists('activityid', $_REQUEST) ? 'activityid='.urlencode($_REQUEST['activityid']).'&' : '' ?>page=<?php echo $pagenumber-1?>">&lt;&lt;&lt;prev</a><?php
}
else
{
	?><span style="color: silver; float: left">&lt;&lt;&lt;prev</span><?php
}
?>
<span style="float: left; margin-left: 2em">Page <?php echo $pagenumber+1?></span>

</td></tr>
</table>

<?php
require_once(dirname(__FILE__).'/footer.php');
