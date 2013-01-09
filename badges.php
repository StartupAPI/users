<?php
require_once(__DIR__ . '/global.php');

if (!UserConfig::$enableGamification) {
	header("HTTP/1.0 404 Not Found");
	exit;
}

$user = User::require_login();

$SECTION = 'badges';

require_once(__DIR__ . '/sidebar_header.php');
$available_badges = Badge::getAvailableBadges();

if (count($available_badges) > 0) {
	?>
	<div>
		<h2>My Badges</h2>
		<?php
		$user_badges = $user->getBadges();

		foreach ($available_badges as $badge) {

			if (array_key_exists($badge->getID(), $user_badges)) {
				$badge_level = $user_badges[$badge->getID()][1];
				?>
				<a href="<?php echo UserConfig::$USERSROOTURL . '/show_badge.php?name=' . $badge->getSlug() ?>"><img class="startupapi-badge" src="<?php echo $badge->getImageURL(UserConfig::$badgeListingSize, $badge_level) ?>" title="<?php echo $badge->getTitle() ?>" width="<?php echo UserConfig::$badgeListingSize ?>" height="<?php echo UserConfig::$badgeListingSize ?>"/></a>
				<?php
			} else {
				?>
				<img class="startupapi-badge" src="<?php echo $badge->getPlaceholderImageURL(UserConfig::$badgeListingSize) ?>" title="Hint: <?php echo $badge->getHint() ?>" width="<?php echo UserConfig::$badgeListingSize ?>" height="<?php echo UserConfig::$badgeListingSize ?>"/>
				<?php
			}
		}
		?>
	</div>
	<?php
}
require_once(__DIR__ . '/sidebar_footer.php');
