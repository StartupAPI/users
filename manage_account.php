<?php
require_once(dirname(__FILE__).'/global.php');

require_once(dirname(__FILE__).'/User.php');

if (!UserConfig::$useAccounts) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

$user = User::require_login();

$accounts = Account::getUserAccounts($user);

$manageable_accounts = array();
foreach ($accounts as $account) {
	if ($account->getUserRole() == Account::ROLE_ADMIN) {
		$manageable_accounts[] = $account;
	}
}

$managed_account = null;
if (array_key_exists('account', $_GET)) {
	foreach ($manageable_accounts as $account) {
		if ($account->getID() == $_GET['account']) {
			$managed_account = $account;
			break;	
		}
	}
}

if (is_null($managed_account)) {
	header('Location: '.UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

require_once(UserConfig::$header);
?>
<h2>Account Info (<?php echo $managed_account->getName() ?>)</h2>
<div id="plan">
<p>Subscription plan: <b><?php echo $managed_account->getPlan()->getName() ?></b>
</div>

<?php
if (!$managed_account->getPlan()->isIndividual() && $managed_account->getUserRole() == Account::ROLE_ADMIN) {
?>
<div id="members">
<h2>Account Members</h2>
<ul>
<?php

$members = $managed_account->getUsers();

foreach ($members as $member) {
	?><li><?php echo $member->getName(); ?></li><?php
}
?>
</ul>
</div>

<?php
}
if (count($manageable_accounts) > 1) {
?>
<h2>Other accounts</h2>
<p>Click on account name to open it:</p>
<ul>
<?php
	foreach ($manageable_accounts as $account)
	{
		if ($account->isTheSameAs($managed_account)) {
			?><li><b><?php echo UserTools::escape($account->getName())?></b></li><?php
		} else {
			?><li><a href="<?php echo UserConfig::$USERSROOTURL ?>/manage_account.php?account=<?php echo $account->getID()?>"><?php echo UserTools::escape($account->getName())?></a></li><?php
		}
	}

?>
</ul>
<?php
}

require_once(UserConfig::$footer);
