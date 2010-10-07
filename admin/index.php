<?php
require_once(dirname(dirname(__FILE__)).'/config.php');

require_once(dirname(dirname(__FILE__)).'/User.php');

if (!session_start())
{
	throw new Exception("Can't start session");
}

if (array_key_exists('impersonate', $_POST)) {
	$user = User::getUser($_POST['impersonate']);
	if ($user !== null) {
		$user->setSession(false); // always impersonate only for the browser session
		header('Location: '.UserConfig::$DEFAULTLOGINRETURN);
	}
	else
	{
		header('Location: #msg=cantimpersonate');
	}
}

require_once(UserConfig::$header);

?><h1>Users (<?php echo User::getTotalUsers()?>)<?php if (UserConfig::$enableInvitations) { ?> | <a href="invitations.php">Invitations</a><?php } ?></h1>
<div style="background: white; padding: 1em">
<h2>Active Users | <a href="registrations.php">Registered Users</a> | <a href="bymodule.php">Registrations By Module</a></h2>
<?php
$daily_active_users = User::getDailyActiveUsers();
$daily_activity = User::getDailyActivityPoints();

$dates = array();
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

$total = 0;
?>
<script type='text/javascript' src='http://www.google.com/jsapi'></script>
<script type='text/javascript'>
google.load('visualization', '1', {'packages':['annotatedtimeline']});
google.setOnLoadCallback(function() {
	var data = new google.visualization.DataTable();
	data.addColumn('date', 'Date');
	data.addColumn('number', 'Active Users');
	data.addColumn('number', 'Total Points');

	var daily = [<?php
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
			}

	?>		[new Date('<?php echo $date?>'), <?php echo $record['users']?>, <?php echo $record['points']?>]<?php
		}
	?>
	];

	data.addRows(daily);

	var chart = new google.visualization.AnnotatedTimeLine(document.getElementById('chart_div'));
	chart.draw(data, {
		displayAnnotations: true,
		scaleColumns: [0, 1],
		scaleType: 'allmaximized'
	});
});
</script>
<div id='chart_div' style='width: 100%; height: 240px; margin-bottom: 1em'></div>

<table cellpadding="5" cellspacing="0" border="1" width="100%">
<tr><th>Time</th><th>Activity</th><th>Points</th><th>User</th></tr>
<?php
$perpage = 20;
$pagenumber = 0;

if (array_key_exists('page', $_GET)) {
	$pagenumber = $_GET['page'];
}

$activities = User::getActivity(array_key_exists('all', $_REQUEST), $pagenumber, $perpage);
?>
<tr><td colspan="4">
<?php
if (count($activities) == $perpage) {
	?><a style="float: right" href="?page=<?php echo $pagenumber+1?>">next &gt;&gt;&gt;</a><?php
}
else
{
	?><span style="color: silver; float: right">next &gt;&gt;&gt;</span><?php
}

if ($pagenumber > 0) {
	?><a style="float: left" href="?page=<?php echo $pagenumber-1?>">&lt;&lt;&lt;prev</a><?php
}
else
{
	?><span style="color: silver; float: left">&lt;&lt;&lt;prev</span><?php
}
?>
<span style="float: left; margin-left: 2em">Page <?php echo $pagenumber+1?></span>

</td></tr>
<?php
$now = time();

foreach ($activities as $activity)
{
	$time = $activity['time'];
	$ago = intval(floor(($now - $time)/86400));

	$tz = date_default_timezone_get();

	$user = User::getUser($activity['user_id']);

	?><tr valign="top"><td align="right"><?php echo date('M j, h:iA', $time)?> (<?php if ($ago <= 5) {?><span style="color: #00<?php echo sprintf('%02s', dechex((4 - $ago) * 150 / 4 + 50))?>00; font-weight: bold"><?php }?><?php echo $ago?> day<?php echo $ago > 1 ? 's' : '' ?> ago<?php if ($ago <= 5) {?></span><?php }?>)</td>
	<td><?php $act = UserConfig::$activities[$activity['activity_id']];
	echo $act[0] ?></td>
	<td><?php echo $act[1] ?></td>
	<td><?php echo $user->getName();?></td>
</td></tr><?php
}

?>
<tr><td colspan="6">
<?php
if (count($activities) == $perpage) {
	?><a style="float: right" href="?page=<?php echo $pagenumber+1?>">next &gt;&gt;&gt;</a><?php
}
else
{
	?><span style="color: silver; float: right">next &gt;&gt;&gt;</span><?php
}

if ($pagenumber > 0) {
	?><a style="float: left" href="?page=<?php echo $pagenumber-1?>">&lt;&lt;&lt;prev</a><?php
}
else
{
	?><span style="color: silver; float: left">&lt;&lt;&lt;prev</span><?php
}
?>
<span style="float: left; margin-left: 2em">Page <?php echo $pagenumber+1?></span>

</td></tr>
</table>

</div><?php
require_once(UserConfig::$footer);
