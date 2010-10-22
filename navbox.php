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
	<? if (!is_null($current_user))
	{
		if (count($accounts) > 1)
		{
			?><select id="account-picker" name="account" onchange="document.location.href='/users/change_account.php?return=/dashboard/&account='+this.value"><?

			foreach ($accounts as $account)
			{
				?><option value="<?=$account->getID()?>"<? if ($current_account->isTheSameAs($account)) { echo ' selected'; } ?>><?=escape($account->getName())?></option><?
			}
		?></select>
		<?
		}
		?>
		<span id="profile"><a href="/p/<?=escape($current_account->getID()) ?>/" title="<?=escape($current_account->getName()) ?>'s public profile">Public profile</a></span> |
		<span id="username"><a href="/users/edit.php" title="<?=escape($current_user->getName())?>'s user information"><?=escape($current_user->getName()) ?></a></span> |
		<span id="logout"><a href="/users/logout.php">logout</a></span>
	<?
	}
	else
	{
	?>
		<span id="signup"><a href="/users/register.php">Sign Up Now!</a></span> |
		<span id="login"><a href="/users/login.php">log in</a></span>
	<?
	}
	?>
</div>
<?php
}

_USERBASE_render_navbox();
