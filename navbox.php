<?php require_once(dirname(__FILE__).'/users.php');

function _USERBASE_render_navbox()
{
	global $user;

	if (isset($user)) {
		$current_user = $user;
	}

	if (!isset($current_user)) {
		$current_user = User::get();
	}

	if (!is_null($current_user)) {
		$accounts = Account::getUserAccounts($current_user);

		$current_account = Account::getCurrentAccount($current_user);
	}
	?>
<div id="navbox">
	<?php if (!is_null($current_user))
	{
		if (count($accounts) > 1)
		{
			?><select id="account-picker" name="account" onchange="document.location.href='<?php echo UserConfig::$USERSROOTURL ?>/change_account.php?return=/dashboard/&account='+this.value"><?php

			foreach ($accounts as $account)
			{
				?><option value="<?php echo $account->getID()?>"<?php if ($current_account->isTheSameAs($account)) { echo ' selected'; } ?>><?php echo UserTools::escape($account->getName())?></option><?php
			}
		?></select>
		<?php
		}
		?>
		<!-- <span id="profile"><a href="/p/<?php echo UserTools::escape($current_account->getID()) ?>/" title="<?php echo UserTools::escape($current_account->getName()) ?>'s public profile">Public profile</a></span> | -->
		<span id="username"><a href="<?php echo UserConfig::$USERSROOTURL ?>/edit.php" title="<?php echo UserTools::escape($current_user->getName())?>'s user information"><?php echo UserTools::escape($current_user->getName()) ?></a></span> |
		<span id="logout"><a href="<?php echo UserConfig::$USERSROOTURL ?>/logout.php">logout</a></span>
	<?php
	}
	else
	{
	?>
		<span id="signup"><a href="<?php echo UserConfig::$USERSROOTURL ?>/register.php">Sign Up Now!</a></span> |
		<span id="login"><a href="<?php echo UserConfig::$USERSROOTURL ?>/login.php">log in</a></span>
	<?php
	}
	?>
</div>
<?php
}

_USERBASE_render_navbox();
