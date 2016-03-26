<?php
require_once(__DIR__.'/users/users.php');

/**
 * Get User object or null if user is not logged in
 */
$current_user = StartupAPI::getUser();

/**
 * Get User object or redirect to login page if user is not logged in
 */
#$current_user = StartupAPI::requireLogin();

// You can work with users, but it's recommended to work with accounts instead
if (!is_null($current_user)) {
	// if user is logged in, get user's accounts
	$accounts = Account::getUserAccounts($current_user);

	// get current account user works with
	$current_account = Account::getCurrentAccount($current_user);
}
?>
<html>
<head>
	<title>Sample page</title>
	<?php StartupAPI::head() ?>
</head>
<body>
<?php StartupAPI::power_strip() ?>
<?php

if (!is_null($current_user)) {
?>
<h1>Welcome, <?php echo $current_user->getName() ?>!</h1>

<p>You successfully logged in.</p>
<?php
}
else
{
?>
<h1>Welcome!</h1>

<p><a href="<?php echo UserConfig::$USERSROOTURL ?>/login.php">Log in</a> to enjoy the magic.</p>
<?php
}
?> 
</body>
</html>
