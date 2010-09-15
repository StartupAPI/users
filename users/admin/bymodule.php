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
<h2><a href="./">Registered Users</a> | By module</h2>

<script type='text/javascript' src='http://www.google.com/jsapi'></script>
<script type='text/javascript'>
google.load('visualization', '1', {'packages':['annotatedtimeline']});
google.setOnLoadCallback(function() {
	var data = new google.visualization.DataTable();
	data.addColumn('date', 'Date');
	<?php foreach (UserConfig::$modules as $module) { ?>
		data.addColumn('number', '<?php echo $module->getID()?>');
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
			foreach (UserConfig::$modules as $module) {
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

	var chart = new google.visualization.AnnotatedTimeLine(document.getElementById('chart_div'));
	chart.draw(data, {
		displayAnnotations: true
	});
});
</script>
<div id='chart_div' style='width: 100%; height: 240px; margin-bottom: 1em'></div>


</div><?php
require_once(UserConfig::$footer);
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
<h2>Registered Users</h2>
<?php $dailyregs = User::getDailyRegistrations();

$total = 0;
?>
<script type='text/javascript' src='http://www.google.com/jsapi'></script>
<script type='text/javascript'>
google.load('visualization', '1', {'packages':['annotatedtimeline']});
google.setOnLoadCallback(function() {
	var data = new google.visualization.DataTable();
	data.addColumn('date', 'Date');
	data.addColumn('number', 'Registrations');
	data.addColumn('number', 'Total');

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

	?>		[new Date('<?php echo $day['regdate']?>'), <?php echo $day['regs']?>, <?php echo $total?>]<?php
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


</div><?php
require_once(UserConfig::$footer);
