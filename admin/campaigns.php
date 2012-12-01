<?php
require_once(dirname(__FILE__) . '/admin.php');

$ADMIN_SECTION = 'campaigns';
require_once(dirname(__FILE__) . '/header.php');

$days = 0;
if (array_key_exists('days', $_GET)) {
	$days = intval($_GET['days']);
}
if ($days <= 0 || $days > 36500) {
	$days = 30;
}

// @todo fix the case when there are no campaigns returned
$campaigns = User::getCampaigns($days);

$tables = array(
	'cmp_name' => 'Campaigns',
	'cmp_source' => 'Campaign sources',
	'cmp_medium' => 'Campaign Medium',
	'cmp_keywords' => 'Campaign Keywords',
	'cmp_content' => 'Campaign content'
);
?>
<div class="span9">
	Campaigns that attracted the most registered users in the <b>last <?php echo $days ?> days</b>
</div>
<?php
foreach ($tables as $slug => $header) {
	?>
	<div class="span9">
		<h3><?php echo $header ?></h3>
		<table class="table">
			<?php
			$sources = $campaigns[$slug];
			uasort($sources, function($a, $b) {
						return count($b) - count($a);
					});

			foreach ($sources as $source => $users) {
				?>
				<tr>
					<td><b title="<?php echo UserTools::escape($source) ?>"><?php echo UserTools::escape(substr($source, 0, 40)) ?><?php if (strlen($source) > 40) { ?>...<?php } ?></b></td>
					<td><span class="badge"><?php echo count($users) ?> users</span></td>
					<td>
		<?php foreach ($users as $user) { ?>
							<a style="margin-right: 0.5em" href="<?php echo UserConfig::$USERSROOTURL ?>/admin/user.php?id=<?php echo $user->getID(); ?>">
								<i class="icon-user"></i> <?php echo UserTools::escape($user->getName()) ?>
							</a>
				<?php } ?>
					</td>
				</tr>
	<?php } ?>
		</table>
	</div>
	<?php
}
require_once(dirname(__FILE__) . '/footer.php');