<?php
namespace StartupAPI;

/**
 * @package StartupAPI
 */
UserConfig::init();

// Gamification badges initializations
$welcome_badge = new Badge(1, 'basic', 'welcome', 'Welcome!', 'We hope you enjoy the jorney with us.', 'Visiting once is already an achievement!');
$welcome_badge->registerActivityTrigger(array(
	USERBASE_ACTIVITY_REGISTER_UPASS,
	USERBASE_ACTIVITY_REGISTER_FB,
	USERBASE_ACTIVITY_REGISTER_GFC,
	USERBASE_ACTIVITY_REGISTER_EMAIL
		), 1);

$welcome_back_badge = new Badge(2, 'basic', 'welcome_back', 'Welcome Back!', 'Welcome back! Great to see you again.', "We'll be glad seing you again!", array(
			'You logged in again. Come back 10 times to unlock Level 2',
			'You logged in 10 times. Come back 50 times to unlock Level 3',
			'You logged in 50 times. Come back 100 times to unlock Level 4',
			'You logged in 100 times! Keep coming back!'
		));

$welcome_back_badge->registerActivityTrigger(array(
	USERBASE_ACTIVITY_RETURN_DAILY,
	USERBASE_ACTIVITY_RETURN_MONTHLY,
	USERBASE_ACTIVITY_RETURN_WEEKLY
		), 1, 1);
$welcome_back_badge->registerActivityTrigger(array(
	USERBASE_ACTIVITY_RETURN_DAILY,
	USERBASE_ACTIVITY_RETURN_MONTHLY,
	USERBASE_ACTIVITY_RETURN_WEEKLY
		), 10, 2);
$welcome_back_badge->registerActivityTrigger(array(
	USERBASE_ACTIVITY_RETURN_DAILY,
	USERBASE_ACTIVITY_RETURN_MONTHLY,
	USERBASE_ACTIVITY_RETURN_WEEKLY
		), 50, 3);
$welcome_back_badge->registerActivityTrigger(array(
	USERBASE_ACTIVITY_RETURN_DAILY,
	USERBASE_ACTIVITY_RETURN_MONTHLY,
	USERBASE_ACTIVITY_RETURN_WEEKLY
		), 100, 4);
