<?php
require_once(__DIR__ . '/admin.php');

if (!array_key_exists('slug', $_GET)) {
	header("HTTP/1.0 400 Plan slug is not specified");
	?><h1>400 Plan slug is not specified</h1><?php
	exit;
}

$plan_slug = trim($_GET['slug']);

$plan = Plan::getPlanBySlug($plan_slug);
if (is_null($plan)) {
	header("HTTP/1.0 404 Plan Not Found");
	?><h1>404 Plan Not Found</h3><?php
	exit;
}

$ADMIN_SECTION = 'plans';
$BREADCRUMB_EXTRA = $plan->getName();
require_once(__DIR__ . '/header.php');
?>
<div class="span9">
	<h2><?php echo UserTools::escape($plan->getName()); ?></h2>
	<p>
		<?php
		$capabilities = $plan->getCapabilities();
		if (isset($capabilities['individual']) && $capabilities['individual']) {
			?>
			<span class="badge">individual</span>
			<?php
		}
		?>
	</p>
	<p>
		<?php echo UserTools::escape($plan->getDescription()); ?><br/>
		<?php if ($plan->getDetailsURL()) { ?><i>Details page: <a target="_blank" href="<?php echo UserTools::escape($plan->getDetailsURL()); ?>"><?php echo UserTools::escape($plan->getDetailsURL()); ?></a></i><?php } ?>
	</p>
	<?php
	if (UserConfig::$useSubscriptions) {
		$downgrade_to = $plan->getDowngradeToPlan();
		if (!is_null($downgrade_to)) {
			?>
			<p>
				Downgrades to:
				<a class="badge badge-info" href="plan.php?slug=<?php echo $downgrade_to->getSlug() ?>"><i class="icon-briefcase icon-white"></i> <?php echo $downgrade_to->getName() ?></a>
			</p>
			<?php
		}

		$schedule_slugs = $plan->getPaymentScheduleSlugs(); # Iterate over all schedules of this plan

		if (count($schedule_slugs) > 0) {
			?>
			<h3>Payment Schedules</h3>
			<ul>
				<?php
				foreach ($schedule_slugs as $s) {
					$schedule = $plan->getPaymentScheduleBySlug($s);
					?>
					<li>
						<b><?php echo $schedule->getName() ?></b><?php if ($schedule->isDefault()) { ?> (default)<?php } ?>
						<p><b>$<?php echo $schedule->getChargeAmount() ?></b>
							every <b><?php echo $schedule->getChargePeriod() ?></b> days</p>
						<p><?php echo $schedule->getDescription() ?></p>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
		}
	}
	?>
</div>

<?php
require_once(__DIR__ . '/footer.php');
