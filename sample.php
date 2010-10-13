<?php
require_once(dirname(__FILE__).'/users/users.php');

// get user if logged in or require user to login
$user = User::get();
#$user = User::require_login();

// You can work with users, but it's recommended to work with accounts instead
if (!is_null($user)) {
	// if user is logged in, get user's accounts
	$accounts = Account::getUserAccounts($user);

	// get current account user works with
	$current_account = Account::getCurrentAccount($user);
}
?>
<html>
<head><title>UserBase sample page</title></head>
<body>
<?php

if (!is_null($user)) {
?>
<h1>Welcome, <?php echo $user->getName() ?>!</h1>

<p>You successfully logged into UserBase.</p>
<?php
}
else
{
?>
<h1>Welcome!</h1>

<p><a href="<?php echo UserConfig::$USERSROOTURL ?>/login.php">Log in</a> to enjoy the magic of UserBase.</p>
<?php
}
?> 
</body>
</html>
