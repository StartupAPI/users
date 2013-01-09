<?php
require_once(dirname(__FILE__) . '/global.php');

require_once(dirname(__FILE__) . '/classes/User.php');

if (!UserConfig::$enableGamification || !array_key_exists('name', $_GET)) {
	header("HTTP/1.0 404 Not Found");
	exit;
}

$user = User::require_login();

$user_badges = $user->getBadges();

$user_has_this_badge = false;

foreach ($user_badges as $badge_pairs) {
	$badge = $badge_pairs[0];
	$badge_level = $badge_pairs[1];

	if ($badge->getSlug() == $_GET['name']) {
		$user_has_this_badge = true;
		break;
	}
}

if (!$user_has_this_badge) {
	header("HTTP/1.0 404 Not Found");
	exit;
}

$SECTION = 'badges';

require_once(dirname(__FILE__) . '/sidebar_header.php');

$slug = $badge->getSlug() . ($badge_level > 1 ? '_' . $badge_level : '');
?>
<img class="startupapi-badge-large" src="<?php echo $badge->getImageURL(UserConfig::$badgeLargeSize, $badge_level) ?>" title="<?php echo $badge->getTitle() ?>" width="<?php echo UserConfig::$badgeLargeSize ?>" height="<?php echo UserConfig::$badgeLargeSize ?>"/>

<h2><?php echo $badge->getTitle(); ?></h2>
<p><?php echo $badge->getDescription(); ?></p>
<p class="startupapi-badge-call-to-action"><?php echo $badge->getCallToAction($badge_level); ?></p>

<?php
require_once(dirname(__FILE__) . '/sidebar_footer.php');
