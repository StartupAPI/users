<?php
namespace StartupAPI;

require_once(__DIR__ . '/admin.php');

$ADMIN_SECTION = 'badges';
require_once(__DIR__ . '/header.php');
?>
<div class="span9">
	<h2>Badges</h2>

	<?php
	$adminBadgeSize = 100;
	$available_badges = Badge::getAvailableBadges();

	foreach ($available_badges as $badge) {
		?>
		<div class="pull-left" style="text-align: center">
			<a href="<?php echo UserConfig::$USERSROOTURL?>/admin/badge.php?id=<?php echo $badge->getID() ?>"><img style="margin: 0.3em" src="<?php echo $badge->getImageURL($adminBadgeSize, 1) ?>" title="<?php echo $badge->getTitle() ?>" width="<?php echo $adminBadgeSize ?>" height="<?php echo $adminBadgeSize ?>"></a>
			<?php
			$counts = $badge->getUserCounts();

			$max_level = count($counts) > 0 ? max(array_keys($counts)) : 0;

			for ($level = 1; $level <= $max_level; $level++) {
				?>
				<div>Lvl. <?php echo $level; ?>: <b><?php echo array_key_exists($level, $counts) ? $counts[$level] : 0 ?></b></div>
				<?php
			}
			?>
		</div>
		<?php
	}
	?>
</div>
<?php
require_once(__DIR__ . '/footer.php');
