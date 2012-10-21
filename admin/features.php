<?php
require_once(dirname(__FILE__).'/admin.php');

$ADMIN_SECTION = 'features';
require_once(dirname(__FILE__).'/header.php');
?>
<style>
tr.disabled td{
	color: silver;
}

span.status {
	display: block;
	float: right;
	width: 1em;
	height: 1em;
	border: 1px solid black;
}

span.status.upandrunning {
	background-color: green;
}

span.status.shutdown {
	background-color: red;
}

tr.disabled span.status {
	background-color: silver;
}

.progress {
	margin: 0;
}
</style>
<div class="span9">

<?php if (count(Feature::getAll()) > 0) { ?>

<table class="table">
<tr>
<th>Enabled</th>
<th align="left">Feature</th>
<th align="left" colspan="2">Rolled-out to</th>
<th>Shutdown<br/>Priority</th>
</tr>
<?php
$total_users = User::getTotalUsers();
$total_accounts = Account::getTotalAccounts();

foreach (Feature::getAll() as $feature) {
	$all_users = $feature->isRolledOutToAllUsers();

	?>
	<tr class="<?php echo $feature->isEnabled() ? 'enabled': 'disabled'; ?><?php echo $feature->isShutDown() ? ' error' : ''; ?>"">
	<td align="center">
		<?php if ($feature->isShutDown()) { ?>
			<span class="label label-important">down</span>
		<?php } else if ($all_users) {?>
			<span class="label label-info">all users</span>
		<?php } else if ($feature->isEnabled()) {?>
			<span class="label label-success">on</span>
		<?php } else { ?>
			<span class="label">off</span>
		<?php } ?></td>

	<td><?php echo $feature->getName(); ?></td>
	<td <?php echo $all_users ? ' colspan="2"' : ''; ?>><label class="checkbox"><input type="checkbox"<?php echo $all_users ? ' checked' : ''; ?> readonly disabled/> All users</label></td>
	<?php if (!$all_users) {
		$user_count = $feature->getUserCount();
		$account_count = $feature->getAccountCount();

		$user_percent = intval($user_count * 10000 / $total_users) / 100;
		$account_percent = intval($account_count * 10000 / $total_accounts) / 100;;


		?>
		<td>
			<table border="0">
			<tr style="border: none">
				<td width="100" style="border: none">
					<div class="progress">
					  <div class="bar" style="width: <?php echo $user_percent.'%' ?>"></div>
					</div>
				</td>
				<td style="border: none">
					<span class="badge"><?php echo $user_count; ?></span> users (<?php echo $user_percent ?>%)
				</td>
			</tr>
			<?php if (UserConfig::$useAccounts) { ?>
			<tr style="border: none">
				<td style="border: none">
					<div class="progress">
					  <div class="bar" style="width: <?php echo $account_percent.'%' ?>"></div>
					</div>
				</td>

				<td style="border: none">
					<span class="badge"><?php echo $account_count; ?></span> accounts (<?php echo $account_percent ?>%)
				</td>
			</tr>
			<?php } ?>
			</table>
		</td>
	<?php
	}
	?>
	<td align="center">
	<?php echo $feature->getShutdownPriority(); ?>
	</td>
	</tr><?php
}
?>
</table>
<?php
} else {
	?><div style="text-align: center; padding: 2em">No <a href="http://startupapi.org/StartupAPI/FeatureManagement" target="_blank">features</a> defined in this application</div><?php
}

?>
</div>
<?php
require_once(dirname(__FILE__).'/footer.php');
