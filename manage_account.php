<?php
require_once(dirname(__FILE__).'/config.php');

require_once(dirname(__FILE__).'/User.php');

if (!UserConfig::$useAccounts) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

$user = User::require_login();

$account = Account::getCurrentAccount($user);

if($account->getUserRole() != Account::ROLE_ADMIN) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

require_once(UserConfig::$header);
?>
<h2>Account Info (<?php echo $account->getName() ?>)</h2>
<div id="plan">
<p>Subscription plan: <b><?php echo $account->getPlan()->name ?></b> - 
<a href="<?php echo UserConfig::$USERSROOTURL ?>/account_details.php">details</a>
</div>
<div id="members">
<h2>Account Members</h2>
<ul>
<?php

$members = $account->getUsers();

foreach ($members as $member) {
	?><li><?php echo $member->getName(); ?></li><?php
}
?>
</ul>
</div>
<?php

require_once(UserConfig::$footer);
