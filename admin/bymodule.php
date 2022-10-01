<?php
require_once(__DIR__.'/admin.php');

$ADMIN_SECTION = 'bymodule';
require_once(__DIR__.'/header.php');
?>
<script type='text/javascript' src='//www.google.com/jsapi'></script>
<script type='text/javascript'>
google.load('visualization', '1', {'packages':['annotatedtimeline', 'corechart']});
google.setOnLoadCallback(function() {
	var data = new google.visualization.DataTable();
	data.addColumn('date', 'Date');
	<?php foreach (UserConfig::$authentication_modules as $module) { ?>
		data.addColumn('number', <?php echo json_encode($module->getTitle())?>);
	<?php } ?>

	var daily = [
		<?php
		$dailyregs = User::getDailyRegistrationsByModule();

		$first = true;
		foreach ($dailyregs as $regdate => $day)
		{
			if (!$first) {
				?>, <?php
			}
			else
			{
				$first = false;
			}

			?>

		[new Date('<?php echo $regdate?>'), <?php
			$firstmodule = true;
			$colors = array();
			foreach (UserConfig::$authentication_modules as $module) {
				$colors[] = '#'.$module->getLegendColor();

				if (!$firstmodule) {
					?>, <?php
				}
				else
				{
					$firstmodule = false;
				}

				if (array_key_exists($module->getID(), $day)) {
					echo $day[$module->getID()];
				} else {
					echo 0;
				}
			}
			?>]<?php
		}
	?>

	];

	data.addRows(daily);
	var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
	chart.draw(data, {
		legend: 'top',
		colors: <?php echo json_encode($colors) ?>
	});
});
</script>
<div class="span9">

<div id='chart_div' style='width: 100%; height: 240px; margin-bottom: 1em'></div>

</div>
<?php
require_once(__DIR__.'/footer.php');
