<?php
require_once(__DIR__ . '/admin.php');

$ADMIN_SECTION = 'accounts';
require_once(__DIR__ . '/header.php');

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

if (is_null($search)) {
	$accounts = Account::getAccounts($pagenumber, $perpage);
} else {
	$accounts = Account::searchAccounts($search, $pagenumber, $perpage);
}
?>
<div class="span9">
	<h1>Accounts</h1>
	<table class="table table-bordered table-striped" width="100%">
		<thead>
			<tr>
				<th>ID</th>
				<th>Account Name</th>
				<th>Plan</th>
				<?php if (UserConfig::$useSubscriptions) { ?>
					<th>Payment Schedule</th>
					<th>Payment method</th>
					<th>Next Charge</th>
				<?php } ?>
				<th>Status</th>
			</tr>
			<tr>
				<td colspan="6">
					<form action="" id="search" name="search" class="form-horizontal" style="margin: 0">
						<a href="new_account.php" class="btn btn-success"><i class="icon-plus icon-white"></i> New Account</a>
						<div class="pull-right">
							<input type="search" class="search-query input-medium" placeholder="Account name" id="q" name="q"<?php echo is_null($search) ? '' : ' value="' . UserTools::escape($search) . '"' ?>/>
							<input type="submit" class="btn btn-medium" value="search"/>
							<a href="accounts.php" class="btn">clear</a>
						</div>
					</form>
				</td>
			</tr>
		</thead>
		<tbody>
			<?php
			$now = time();

			foreach ($accounts as $account) {
				// $next_charge_days = intval(floor(($account->getNextCharge() - $now)/86400));

				$tz = date_default_timezone_get();

				$account_id = $account->getID();
				?>
				<tr valign="top">
					<td><?php echo $account_id ?></td>
					<td><a href="account.php?id=<?php echo $account_id ?>"><?php echo UserTools::escape($account->getName()) ?></a></td>
					<td>
						<?php
						$plan = $account->getPlan();
						if (!is_null($plan)) {
							?><a class="badge badge-info" href="plan.php?slug=<?php echo $plan->slug ?>"><i class="icon-briefcase icon-white"></i> <?php echo $plan->name ?></a><?php
						}
						?>
					</td>
					<?php if (UserConfig::$useSubscriptions) { ?>
						<td>
							<?php
							$schedule = $account->getSchedule();
							if (!is_null($schedule)) {
								echo $schedule->name;
							}
							?>
						</td>
						<td>
							<?php
							$engine = $account->getPaymentEngine();
							if (!is_null($schedule) && !is_null($engine)) {
								echo $engine->getTitle();
							}
							?>
						</td>
						<td align="right">
							<?php echo $account->getNextCharge() ?>
						</td>
					<?php } ?>
					<td>
						<?php
						if ($account->isActive()) {
							?>
							<span class="label label-success">active</span>
							<?php
						} else {
							?>
							<span class="label label-important">inactive</span>
							<?php
						}
						?>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>

	<ul class="pager">
		<?php
		if ($pagenumber > 0) {
			?><li class="previous"><a href="?page=<?php
		echo $pagenumber - 1;
		echo is_null($search) ? '' : '&q=' . urlencode($search);
		echo $sortby == 'activity' ? '&sort=activity' : '';
			?>">&larr; prev</a></li><?php
							} else {
			?><li class="previous disabled"><a href="#">&larr; prev</a></li><?php
		}
		?>
		<li>Page <?php echo $pagenumber + 1 ?></li>
		<?php
		if (count($accounts) >= $perpage) {
			?><li class="next"><a href="?page=<?php
		echo $pagenumber + 1;
		echo is_null($search) ? '' : '&q=' . urlencode($search);
		echo $sortby == 'activity' ? '&sort=activity' : '';
			?>">next &rarr;</a></li><?php
						} else {
			?><li class="next disabled"><a href="#">next &rarr;</a></li><?php }
		?>
	</ul>

</div>
<?php
require_once(__DIR__ . '/footer.php');