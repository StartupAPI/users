<?
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

?><h1>Users (<?=User::getTotalUsers()?>)<? if (UserConfig::$enableInvitations) { ?> | <a href="invitations.php">Invitations</a><? } ?></h1>
<div style="background: white; padding: 1em">
<h2>Registered Users</h2>
<? $dailyregs = User::getDailyRegistrations();

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

	var daily = [<?
		$first = true;

		foreach ($dailyregs as $day)
		{
			if (!$first) {
				?>,
				<?
			}
			else
			{
				$first = false;
			}
			$total += $day['regs'];

	?>		[new Date('<?=$day['regdate']?>'), <?=$day['regs']?>, <?=$total?>]<?
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
<tr><th>ID</th><th>Reg</th><th>Username</th><th>Name</th><th>Email</th><th>Actions</th></tr>
<?
$perpage = 20;
$pagenumber = 0;

if (array_key_exists('page', $_GET)) {
	$pagenumber = $_GET['page'];
}

$users = User::getUsers($pagenumber, $perpage);
?>
<tr><td colspan="6">
<?
if (count($users) == $perpage) {
	?><a style="float: right" href="?page=<?=$pagenumber+1?>">next &gt;&gt;&gt;</a><?
}
else
{
	?><span style="color: silver; float: right">next &gt;&gt;&gt;</span><?
}

if ($pagenumber > 0) {
	?><a style="float: left" href="?page=<?=$pagenumber-1?>">&lt;&lt;&lt;prev</a><?
}
else
{
	?><span style="color: silver; float: left">&lt;&lt;&lt;prev</span><?
}
?>
<span style="float: left; margin-left: 2em">Page <?=$pagenum+1?></span>

</td></tr>
<?
$now = time();

foreach ($users as $user)
{
	$regtime = $user->getRegTime();
	$ago = intval(floor(($now - $regtime)/86400));

	?><tr><td><?=$user->getID()?></td><td align="right"><?=date('M j, h:iA', $regtime)?> (<? if ($ago <= 5) {?><span style="color: #00<?=sprintf('%02s', dechex((4 - $ago) * 150 / 4 + 50))?>00; font-weight: bold"><?}?><?=$ago?> day<?=$ago > 1 ? 's' : '' ?> ago<? if ($ago <= 5) {?></span><?}?>)</td><td><?=$user->getUsername()?></td><td><?=$user->getName()?></td><td><?=$user->getEmail()?></td><td><form action="" method="POST"><input type="submit" value="impersonate"/><input type="hidden" name="impersonate" value="<?=$user->getID()?>"/></form></td></tr><?
}

?>
<tr><td colspan="6">
<?
if (count($users) == $perpage) {
	?><a style="float: right" href="?page=<?=$pagenumber+1?>">next &gt;&gt;&gt;</a><?
}
else
{
	?><span style="color: silver; float: right">next &gt;&gt;&gt;</span><?
}

if ($pagenumber > 0) {
	?><a style="float: left" href="?page=<?=$pagenumber-1?>">&lt;&lt;&lt;prev</a><?
}
else
{
	?><span style="color: silver; float: left">&lt;&lt;&lt;prev</span><?
}
?>
<span style="float: left; margin-left: 2em">Page <?=$pagenum+1?></span>

</td></tr>
</table>

</div><?
require_once(UserConfig::$footer);
