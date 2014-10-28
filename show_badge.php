<?php
require_once(__DIR__ . '/global.php');

require_once(__DIR__ . '/classes/User.php');

if (!UserConfig::$enableGamification || !array_key_exists('name', $_GET)) {
	header("HTTP/1.0 404 Not Found");
	exit;
}

$user = User::require_login();

$template_info = StartupAPI::getTemplateInfo();

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

// setting section value
$template_info['PAGE']['SECTION'] = 'badges';

$template_info['slug'] = $badge->getSlug() . ($badge_level > 1 ? '_' . $badge_level : '');
$template_info['url'] = $badge->getImageURL(UserConfig::$badgeLargeSize, $badge_level);
$template_info['title'] = $badge->getTitle();
$template_info['description'] = $badge->getDescription();
$template_info['call_to_action'] = $badge->getCallToAction($badge_level);

StartupAPI::$template->display('show_badge.html.twig', $template_info);
