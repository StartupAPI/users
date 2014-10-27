<?php

require_once(__DIR__ . '/global.php');

if (!UserConfig::$enableGamification) {
	header("HTTP/1.0 404 Not Found");
	exit;
}

$user = User::require_login();

$template_info = StartupAPI::getTemplateInfo();

// setting section value
$template_info['PAGE']['SECTION'] = 'badges';

$available_badges = Badge::getAvailableBadges();
$user_badges = $user->getBadges();

foreach ($available_badges as $badge) {
	$badge_info = array(
		'size' => UserConfig::$badgeListingSize,
	);

	if (array_key_exists($badge->getID(), $user_badges)) {
		$badge_level = $user_badges[$badge->getID()][1];



		$badge_info['slug'] = $badge->getSlug();

		$badge_info['url'] = $badge->getImageURL(UserConfig::$badgeListingSize, $badge_level);

		$badge_info['title'] = $badge->getTitle();
	} else {
		$badge_info['placeholder_url'] = $badge->getPlaceholderImageURL(UserConfig::$badgeListingSize);
		$badge_info['hint'] = $badge->getHint();
	}

	$template_info['badges'][] = $badge_info;
}

StartupAPI::$template->display('badges.html.twig', $template_info);