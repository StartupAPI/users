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

table, td, th, tr {
	border: 1px solid silver;
}
</style>

<?php if (count(Feature::getAll()) > 0) { ?>

<table border="1" width="100%" cellpadding="5" cellspacing="0">
<tr>
<th align="left">Feature Name</th>
<th align="left" colspan="2">Rolled-out to</th>
<th>Enabled</th>
<th>Shutdown<br/>Priority</th>
</tr>
<?php
$total_users = User::getTotalUsers();
$total_accounts = Account::getTotalAccounts();

foreach (Feature::getAll() as $feature) {
	?>
	<tr class="<?php echo $feature->isEnabled() ? 'enabled': 'disabled'; ?>">
	<td><?php echo $feature->getName(); ?></td>
	<td <?php echo $feature->isRolledOutToAllUsers() ? ' colspan="2"' : ''; ?>><input type="checkbox"<?php echo $feature->isRolledOutToAllUsers() ? ' checked' : ''; ?> readonly disabled/> All users</td>
	<?php if (!$feature->isRolledOutToAllUsers()) {
		$user_count = $feature->getUserCount();
		$account_count = $feature->getAccountCount();

		?><td><?php echo $user_count; ?> users (<?php echo intval($user_count * 10000 / $total_users) / 100; ?>%)
		<?php if (UserConfig::$useAccounts) {?>
		<br/><?php echo $user_count; ?> accounts (<?php echo intval($account_count * 10000 / $total_accounts) / 100; ?>%)
		<?php } ?>
		</td><?php
	}
	?>
	<td align="center"><?php echo $feature->isEnabled() ? 'Yes': 'No'; ?></td>
	<td align="center">
	<?php echo $feature->getShutdownPriority(); ?>
	<span class="status <?php echo $feature->isShutDown() ? 'shutdown' : 'upandrunning'; ?>"></span>
	</td>
	</tr><?php
}
?>
</table>
<?php
} else {
	?><div style="text-align: center; padding: 2em">No <a href="http://startupapi.org/StartupAPI/FeatureManagement" target="_blank">features</a> defined in this application</div><?php
}

require_once(dirname(__FILE__).'/footer.php');
