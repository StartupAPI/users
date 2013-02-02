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
$BREADCRUMB_EXTRA = $plan->name;
require_once(__DIR__ . '/header.php');
?>
<div class="span9">
	<h2><?php echo UserTools::escape($plan->name); ?></h2>
	<p>
		<?php if (isset($plan->capabilities['individual']) && $plan->capabilities['individual']) { ?>
			<span class="badge">individual</span>
		<?php } ?>
	</p>
	<p>
		<?php echo UserTools::escape($plan->description); ?><br/>
		<?php if ($plan->details_url) { ?><i>Details page: <a target="_blank" href="<?php echo UserTools::escape($plan->details_url); ?>"><?php echo UserTools::escape($plan->details_url); ?></a></i><?php } ?>
	</p>
	<?php
	if (UserConfig::$useSubscriptions) {
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
						<b><?php echo $schedule->name ?></b><?php if ($schedule->is_default) { ?> (default)<?php } ?>
						<p><b>$<?php echo $schedule->charge_amount ?></b>
							every <b><?php echo $schedule->charge_period ?></b> days</p>
						<p><?php echo $schedule->description ?></p>
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