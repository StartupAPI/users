<?php
require_once(dirname(__FILE__).'/admin.php');

$ADMIN_SECTION = 'registrations';
require_once(dirname(__FILE__).'/header.php');

$dailyregs = User::getDailyRegistrations();

$total = 0;
?>
<script type='text/javascript' src='swfobject/swfobject/swfobject.js'></script>
<script type='text/javascript' src='http://www.google.com/jsapi'></script>
<script type='text/javascript'>
google.load('visualization', '1', {'packages':['annotatedtimeline', 'corechart']});
google.setOnLoadCallback(function() {
	var data = new google.visualization.DataTable();
	data.addColumn('date', 'Date');
	data.addColumn('number', 'Total Users');
	data.addColumn('number', 'Daily Registrations');

	var daily = [<?php
		$first = true;

		foreach ($dailyregs as $day)
		{
			if (!$first) {
				?>,
				<?php
			}
			else
			{
				$first = false;
			}
			$total += $day['regs'];

	?>		[new Date('<?php echo $day['regdate']?>'), <?php echo $total?>, <?php echo $day['regs']?>]<?php
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

<table cellpadding="5" cellspacing="0" border="1" width="100%">
<tr><th>ID</th><th>Reg</th><th>Credentials</th><th>Name</th><th>Email</th><th>Points</th></tr>
<?php
$perpage = 20;
$pagenumber = 0;

if (array_key_exists('page', $_GET)) {
	$pagenumber = $_GET['page'];
}

$search = null;
if (array_key_exists('q', $_GET)) {
	$search = trim($_GET['q']);
	if ($search == '') {
		$search = null;
	}
}

if (array_key_exists('sort', $_GET) && $_GET['sort'] == 'activity') {
	$sortby = 'activity';
} else {
	$sortby = 'registration';
}

if (is_null($search)) {
	$users = User::getUsers($pagenumber, $perpage, $sortby);
} else {
	$users = User::searchUsers($search, $pagenumber, $perpage, $sortby);
}
?>
<tr><td colspan="7" valign="middle">
<?php
if (count($users) == $perpage) {
	?><a style="float: right" href="?page=<?php echo $pagenumber+1; echo is_null($search) ? '' : '&q='.urlencode($search)?>">next &gt;&gt;&gt;</a><?php
}
else
{
	?><span style="color: silver; float: right">next &gt;&gt;&gt;</span><?php
}

if ($pagenumber > 0) {
	?><a style="float: left" href="?page=<?php echo $pagenumber-1; echo is_null($search) ? '' : '&q='.urlencode($search) ?>">&lt;&lt;&lt;prev</a><?php
}
else
{
	?><span style="color: silver; float: left">&lt;&lt;&lt;prev</span><?php
}
?>
<span style="float: left; margin: 0 2em 0 1em;">Page <?php echo $pagenumber+1?></span>
<form action="" id="search" name="search">
<input type="text" id="q" name="q"<?php echo is_null($search) ? '' : ' value="'.htmlspecialchars($search).'"'?>/><input type="submit" value="search"/><input type="button" value="clear" onclick="document.getElementById('q').value=''; document.search.submit()"/>
Sort by
<select name="sort" onchange="document.search.submit();">
<option value="registration"<?php echo $sortby == 'registration' ? ' selected="yes"' : '' ?>>Registration date</option>
<option value="activity"<?php echo $sortby == 'activity' ? ' selected="yes"' : '' ?>>User activity</option>
</select>
</form>
</td></tr>
<?php
$now = time();

foreach ($users as $user)
{
	$regtime = $user->getRegTime();
	$ago = intval(floor(($now - $regtime)/86400));

	$tz = date_default_timezone_get();

	?><tr valign="top">
	<td><a href="user.php?id=<?php $userid = $user->getID(); echo $userid; ?>"><?php echo $userid; ?></a></td>
	<td align="right"><?php echo date('M j, h:iA', $regtime)?> (<?php if ($ago <= 5) {?><span style="color: #00<?php echo sprintf('%02s', dechex((4 - $ago) * 150 / 4 + 50))?>00; font-weight: bold"><?php }?><?php echo $ago?> day<?php echo $ago > 1 ? 's' : '' ?> ago<?php if ($ago <= 5) {?></span><?php }?>)</td>
	<td><?php
	foreach (UserConfig::$authentication_modules as $module)
	{
		$creds = $module->getUserCredentials($user);

		if (!is_null($creds)) {
		?>
		<div><b><?php echo $module->getID() ?>: </b><?php echo $creds->getHTML() ?></div>
		<?php
		}
	}
	?></td>
	<td><a href="user.php?id=<?php echo $userid ?>"><?php echo UserTools::escape($user->getName())?></a></td>
	<td><?php echo UserTools::escape($user->getEmail())?></td>
	<td><?php
	$points = $user->getPoints();
	if ($points > 0) {
		?><a href="./activity.php?userid=<?php echo $userid ?>"><?php echo $points ?></a><?php
	}
	?>
	</td>
</tr><?php
}

?>
<tr><td colspan="7">
<?php
if (count($users) == $perpage) {
	?><a style="float: right" href="?page=<?php echo $pagenumber+1; echo is_null($search) ? '' : '&q='.urlencode($search)?>">next &gt;&gt;&gt;</a><?php
}
else
{
	?><span style="color: silver; float: right">next &gt;&gt;&gt;</span><?php
}

if ($pagenumber > 0) {
	?><a style="float: left" href="?page=<?php echo $pagenumber-1; echo is_null($search) ? '' : '&q='.urlencode($search)?>">&lt;&lt;&lt;prev</a><?php
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
