<?php
require_once(dirname(__FILE__) . '/admin.php');

if (!array_key_exists('id', $_GET) || is_null($badge = Badge::getByID($_GET['id']))) {
	header("HTTP/1.0 404 Not Found");
	exit;
}

$ADMIN_SECTION = 'badges';
$BREADCRUMB_EXTRA = $badge->getTitle();
require_once(dirname(__FILE__) . '/header.php');

$largeBadgeSize = 300;
$smallBadgeSize = 57;

function showLevelUsers($level) {

}
?>
<div class="span9">
	<img class="pull-right" style="margin-left: 1em" src="<?php echo $badge->getImageURL($largeBadgeSize, 1) ?>" title="<?php echo $badge->getTitle() ?>" width="<?php echo $largeBadgeSize ?>" height="<?php echo $largeBadgeSize ?>"/>

	<h2><?php echo $badge->getTitle(); ?></h2>
	<p><?php echo $badge->getDescription(); ?></p>
	<?php
	$calls_to_ation = $badge->getCallsToAction();

	$max_level_existing_badges = max(array_keys($badge->getUserCounts()));
	$max_level_calls_to_action = count($calls_to_ation);

	$max_level = max($max_level_calls_to_action, $max_level_existing_badges);

	for ($level = 1; $level <= $max_level; $level++) {
		?>
		<div>
			<h4>Level <?php echo $level ?></h4>
			<?php if (array_key_exists($level - 1, $calls_to_ation)) { ?>
				<p><?php echo $calls_to_ation[$level - 1] ?></p>
			<?php } ?>
			<?php
			$badge_users = $badge->getBadgeUsers($level);

			if (count($badge_users) == 0) {
				?>
				<p style="font-style: italic">No users got this badge yet</p>
				<?php
			} else {
				foreach ($badge_users as $user) {
					?>
					<a href="<?php UserConfig::$USERSROOTURL ?>/admin/user.php?id=<?php echo $user->getID() ?>">
						<li class="icon-user"></li> <?php echo UserTools::escape($user->getName()) ?>
					</a>
					<?php
				}
			}
			?>
		</div>
		<?php
	}
	?>
</div>
<?php
require_once(dirname(__FILE__) . '/footer.php');