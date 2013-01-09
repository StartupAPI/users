<?php
require_once(__DIR__ . '/admin.php');

$ADMIN_SECTION = 'sources';
require_once(__DIR__ . '/header.php');

$days = 30;

$sources = User::getReferers($days);
uasort($sources, function($a, $b) {
			return count($b) - count($a);
		});
?>
<div class="span9">
	<p>Sources that attracted the most registered users in the <b>last <?php echo $days ?> days</b>.</p>
	<table class="table">
		<?php foreach ($sources as $source => $users) { ?>
			<tr>
				<td><a href="<?php echo UserTools::escape($source) ?>" target="_blank"><?php echo UserTools::escape(substr($source, 0, 40)) ?><?php if (strlen($source) > 40) { ?>...<?php } ?></a></td>
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
require_once(__DIR__ . '/footer.php');