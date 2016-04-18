<?php
require_once(__DIR__.'/admin.php');

$ADMIN_SECTION = 'registrations';
require_once(__DIR__.'/header.php');

$dailyregs = User::getDailyRegistrations();

$total = 0;

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
	if (array_key_exists('date_from', $_GET) && array_key_exists('date_from', $_GET)) {
		$users = User::getUsers($pagenumber, $perpage, $sortby, $_GET['date_from'], $_GET['date_to']);
	} else if (array_key_exists('date_from', $_GET)) {
		$users = User::getUsers($pagenumber, $perpage, $sortby, $_GET['date_from']);
	} else if (array_key_exists('date_to', $_GET)) {
		$users = User::getUsers($pagenumber, $perpage, $sortby, null, $_GET['date_to']);
	} else {
		$users = User::getUsers($pagenumber, $perpage, $sortby);
	}
} else {
	$users = User::searchUsers($search, $pagenumber, $perpage, $sortby);
}

?>
<script type='text/javascript' src='swfobject/swfobject/swfobject.js'></script>
<script type='text/javascript' src='//www.google.com/jsapi'></script>
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
<div class="span9">

<div id='chart_div' style='width: 100%; height: 240px; margin-bottom: 1em'></div>

<table class="table table-bordered table-striped" width="100%">
<thead>
<tr><th>ID</th><th>Reg</th><th>Credentials</th><th>Name</th><th>Email</th><th>Points</th></tr>
<tr>
	<td colspan="6">
		<form action="" id="search" name="search" class="form-horizontal" style="margin: 0">
			<div cZlass="control-group">
				<input type="search" class="search-query input-medium" placeholder="User's name or email" id="q" name="q"<?php echo is_null($search) ? '' : ' value="' . UserTools::escape($search) . '"' ?>/>
				<input type="submit" class="btn btn-medium" value="search"/>
				<input type="button" class="btn btn-medium" value="clear" onclick="document.getElementById('q').value=''; document.search.submit()"/>

				<label class="pull-right">Sort by:
					<select name="sort" style="margin-left: 0.5em" onchange="document.search.submit();">
						<option value="registration"<?php echo $sortby == 'registration' ? ' selected="yes"' : '' ?>>Registration date</option>
						<option value="activity"<?php echo $sortby == 'activity' ? ' selected="yes"' : '' ?>>User activity</option>
					</select>
				</label>
			</div>
		</form>
	</td>
</tr>
<?php

?>
</thead>

<?php
$now = time();

foreach ($users as $user)
{
	$regtime = $user->getRegTime();
	$ago = intval(floor(($now - $regtime)/86400));

	$tz = date_default_timezone_get();

	?><tr valign="top">
	<td><a href="user.php?id=<?php $userid = $user->getID(); echo $userid; ?>"><?php echo $userid; ?></a></td>
	<td align="right">
		<?php echo date('M j Y, h:iA', $regtime) ?><br/>
		<span class="badge<?php if ($ago <= 5) {?> badge-success<?php }?>">
			<?php echo $ago?>
		</span> day<?php echo $ago != 1 ? 's' : '' ?> ago
	</td>
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
	<td><i class="icon-user"></i> <a href="user.php?id=<?php echo $userid ?>"<?php if ($user->isDisabled()) { ?> class="startupapi-user-disabled"<?php } ?>><?php echo UserTools::escape($user->getName())?></a></td>
	<td><?php echo UserTools::escape($user->getEmail())?></td>
	<td><?php
	$points = $user->getPoints();
	if ($points > 0) {
		?><a class="badge badge-info" href="./activity.php?userid=<?php echo $userid ?>"><?php echo $points ?></a><?php
	}
	?>
	</td>
</tr>
	<?php
}
?>
</table>

<ul class="pager">
<?php
if ($pagenumber > 0) {
	?><li class="previous"><a href="?page=<?php echo $pagenumber-1; echo is_null($search) ? '' : '&q='.urlencode($search); echo $sortby == 'activity' ? '&sort=activity' : ''; ?>">&larr; prev</a></li><?php
}
else
{
	?><li class="previous disabled"><a href="#">&larr; prev</a></li><?php
}
?>
<li>Page <?php echo $pagenumber+1?></li>
<?php
if (count($users) >= $perpage) {
	?><li class="next"><a href="?page=<?php echo $pagenumber+1; echo is_null($search) ? '' : '&q='.urlencode($search); echo $sortby == 'activity' ? '&sort=activity' : ''; ?>">next &rarr;</a></li><?php
}
else
{
	?><li class="next disabled"><a href="#">next &rarr;</a></li><?php
}?>
</ul>

</div>
<?php
require_once(__DIR__.'/footer.php');
