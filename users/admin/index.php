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
	chart.draw(data, {displayAnnotations: true});
});
</script>
<div id='chart_div' style='width: 100%; height: 240px; margin-bottom: 1em'></div>

<table cellpadding="5" cellspacing="0" border="1" width="100%">
<tr><th>ID</th><th>Reg</th><th>Credentials</th><th>Name</th><th>Email</th><th>Actions</th></tr>
<?php
$perpage = 20;
$pagenumber = 0;

if (array_key_exists('page', $_GET)) {
	$pagenumber = $_GET['page'];
}

$users = User::getUsers($pagenumber, $perpage);
?>
<tr><td colspan="6">
<?php
if (count($users) == $perpage) {
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
<span style="float: left; margin-left: 2em">Page <?php echo $pagenum+1?></span>

</td></tr>
<?php
$now = time();

foreach ($users as $user)
{
	$regtime = $user->getRegTime();
	$ago = intval(floor(($now - $regtime)/86400));

	$tz = date_default_timezone_get();

	?><tr valign="top"><td><?php echo $user->getID()?></td><td align="right"><?php echo date('M j, h:iA', $regtime)?> (<?php if ($ago <= 5) {?><span style="color: #00<?php echo sprintf('%02s', dechex((4 - $ago) * 150 / 4 + 50))?>00; font-weight: bold"><?php }?><?php echo $ago?> day<?php echo $ago > 1 ? 's' : '' ?> ago<?php if ($ago <= 5) {?></span><?php }?>)</td><td><?php

	foreach (UserConfig::$modules as $module)
	{
		$creds = $module->getUserCredentials($user);

		if (!is_null($creds)) {
		?>
		<div><b><?php echo $module->getID() ?>: </b><?php echo $creds ?></div>
		<?
		}
	}
	?></td><td><?php echo $user->getName()?></td><td><?php echo $user->getEmail()?></td><td><form action="" method="POST"><input type="submit" value="impersonate"/><input type="hidden" name="impersonate" value="<?php echo $user->getID()?>"/></form></td></tr><?php
}

?>
<tr><td colspan="6">
<?php
if (count($users) == $perpage) {
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
<span style="float: left; margin-left: 2em">Page <?php echo $pagenum+1?></span>

</td></tr>
</table>

</div><?php
require_once(UserConfig::$footer);
