<?php
require_once(dirname(__FILE__).'/global.php');

require_once(dirname(__FILE__).'/User.php');

if (!UserConfig::$useAccounts) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

$user = User::require_login();

$account = Account::getCurrentAccount($user);
if ($account->getUserRole($user) !== Account::ROLE_ADMIN) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

require_once(UserConfig::$header);
?>
<h2>Account Info (<?php echo $account->getName() ?>)</h2>
<div id="plan">
<p>Subscription plan: <b><?php echo $account->getPlan()->name ?></b> -
<a href="<?php echo UserConfig::$USERSROOTURL ?>/subscription_details.php">[ details ]</a>
<a href="<?php echo UserConfig::$USERSROOTURL ?>/plans.php">[ change ]</a>
</div>
<div id="members">
<h2>Account Members</h2>
<ul>
<?php

$users_and_roles = $account->getUsers();

foreach ($users_and_roles as $user_and_role) {
	$member = $user_and_role[0];
	$role = $user_and_role[1];

	?><li>
		<?php echo $member->getName(); ?>
		<?php if ($role == Account::ROLE_ADMIN) { ?>(<span class="badge badge-important">admin</span>)<?php } ?>
	</li><?php
}
?>
</ul>
</div>
<?php

require_once(UserConfig::$footer);
