<?php
require_once(dirname(__FILE__).'/admin.php');

$ADMIN_SECTION = 'dashboard';
require_once(dirname(__FILE__).'/header.php');

$total_users = User::getTotalUsers();
$active_users = User::getActiveUsers();
$daily_active_users = User::getDailyActiveUsers(60);

$daily_active_users_vals = array_values($daily_active_users);
$max_daily_active_users = max($daily_active_users_vals);
$min_daily_active_users = 0; //min($daily_active_users_vals);

$daily_active_users_ranked = array();
for ($i = 0; $i < count($daily_active_users_vals); $i++) {
	$daily_active_users_ranked[] = ($daily_active_users_vals[$i] - $min_daily_active_users)
		/ ($max_daily_active_users - $min_daily_active_users) * 100;
}

$data = '';
$legend = '';
$legend_colors = '';
$labels = '';

$regs = User::getRecentRegistrationsByModule();

$totalregs = 0;
foreach (UserConfig::$authentication_modules as $module) {
	$module_id = $module->getID();

	if (array_key_exists($module_id, $regs)) {
		$totalregs += $regs[$module_id];
	}
}

$firstmodule = true;
foreach (UserConfig::$authentication_modules as $module) {
	$module_id = $module->getID();

	if (!array_key_exists($module_id, $regs) || $regs[$module_id] == 0) {
		continue;
	}

	if (array_key_exists($module->getID(), $regs)) {
		$data .= (!$firstmodule ? ',' : '').sprintf('%.1f', $regs[$module->getID()] * 100 / $totalregs);
		$legend .= (!$firstmodule ? '|' : '').$module->getTitle().' ('.sprintf('%.1f', $regs[$module->getID()] * 100 / $totalregs).'%)';
		$legend_colors .= (!$firstmodule ? '|' : '').$module->getLegendColor();
		$labels .= (!$firstmodule ? '|' : '').$module->getTitle();
	} else {
		echo 0;
	}

	if ($firstmodule) {
		$firstmodule = false;
	}
}
?>
<style>
#metrics_dashboard th {
	font-size: x-large;
	font-weight: normal;
}

#metrics_dashboard tr {
	text-align: center;
}

#metric_values td {
	font-size: xx-large;
	font-weight: bold;
}
#metric_notes td {
	font-size: x-small;
}
</style>

<table id="metrics_dashboard" cellpadding="10" border="0" style="margin: 0 auto;">
<tr>
	<th><a href="activity.php">Active</a> users</th>
	<th>Total <a href="registrations.php">registered users</a></th>
	<th>Registrations <a href="bymodule.php">by module</a></th>
</tr>
<tr id="metric_values">
	<td>
	<img src="https://chart.googleapis.com/chart?cht=ls&chxt=y&chxr=0,<?php echo $min_daily_active_users ?>,<?php echo $max_daily_active_users ?>&chs=250x100&chd=t:<?php echo implode(',',array_values($daily_active_users_ranked))?>" width="250" height="100" vspace="10">
	<br/>
	<?php echo sprintf('%.1f', $active_users * 100 / $total_users) ?>% (<?php echo $active_users ?>)
	</td>
	<td><?php echo $total_users?></td>
	<td><?php if ($firstmodule) { ?><span style="color: silver">none</span><?php } else { ?><img src="http://chart.apis.google.com/chart?chp=0.3&chma=|0,30&chxt=x&chxs=0,676767,12.5&chs=400x200&cht=p3&chco=<?php echo ($legend_colors) ?>&chd=<?php echo ('t:'.$data) ?>&chdl=<?php echo urlencode($legend) ?>&chdlp=b&chl=<?php echo urlencode($labels) ?>" alt="registrations by module"/><?php } ?></td>
</tr>
<tr id="metric_notes">
	<td>* Users active within last 30 days<br/>(only measuring activity after one day since registration)</td>
	<td>** Vanity metric - only shows the amount of marketing effort,<br/>use "Active users" instead</td>
	<td>*** Recent registrations breakdown by module<br/>Useful for optimizing registration forms</td>
</tr>
</table>

<?php
require_once(dirname(__FILE__).'/footer.php');
