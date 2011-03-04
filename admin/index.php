<?php
$ADMIN_SECTION = 'dashboard';
require_once(dirname(__FILE__).'/header.php');

$total_users = User::getTotalUsers();
$active_users = User::getActiveUsers();

$data = '';
$legend = '';
$legend_colors = '';
$labels = '';

$regs = User::getRecentRegistrationsByModule();

$totalregs = 0;
foreach (UserConfig::$modules as $module) {
	$totalregs += $regs[$module->getID()];
}

$firstmodule = true;
foreach (UserConfig::$modules as $module) {
	if (array_key_exists($module->getID(), $regs)) {
		$data .= (!$firstmodule ? '|' : '').$regs[$module->getID()];
		$legend .= (!$firstmodule ? '|' : '').$module->getTitle();
		$legend_colors .= (!$firstmodule ? '|' : '').$module->getLegendColor();
		$labels .= (!$firstmodule ? '|' : '').sprintf('%.1f', $regs[$module->getID()] * 100 / $totalregs).'%';
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
	<td><?php echo sprintf('%.1f', $active_users * 100 / $total_users) ?>% (<?php echo $active_users ?>)</td>
	<td><?php echo $total_users?></td>
	<td><img src="http://chart.apis.google.com/chart?chp=0.3&chma=|0,30&chxs=<?php echo urlencode($data) ?>&chxt=x&chs=400x200&cht=p3&chco=<?php echo urlencode($legend_colors) ?>&chd=s:GMS&chdl=<?php echo urlencode($legend) ?>&chdlp=b&chl=<?php echo urlencode($labels) ?>" alt="registrations by module"/></td>
</tr>
<tr id="metric_notes">
	<td>* Users active within last 30 days<br/>(only measuring activity after one day since registration)</td>
	<td>** Vanity metric - only shows the amount of marketing effort,<br/>use "Active users" instead</td>
	<td>*** Recent registrations breakdown by module<br/>Useful for optimizing registration forms</td>
</tr>
</table>

<?php
require_once(dirname(__FILE__).'/footer.php');
