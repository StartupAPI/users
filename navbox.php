<?php

function _USERBASE_render_navbox()
{
	$current_user = User::get();
	$current_account = null;

	$accounts = array();
	if (UserConfig::$useAccounts && !is_null($current_user)) {
		$accounts = Account::getUserAccounts($current_user);

		$current_account = Account::getCurrentAccount($current_user);
	}
	?>
<div id="userbase-navbox">
	<?php if (!is_null($current_user))
	{
		if ($current_user->isImpersonated()) {
			?><b id="userbase-navbox-impersonating"><a href="<?php echo UserConfig::$USERSROOTURL ?>/admin/stopimpersonation.php" title="Impersonated by <?php echo UserTools::escape($current_user->getImpersonator()->getName())?>">Stop Impersonation</a></b> | <?php 
		}

		if ($current_user->isAdmin()) {
			?><b id="userbase-navbox-admin"><a href="<?php echo UserConfig::$USERSROOTURL ?>/admin/">Admin</a></b> | <?php 
		}

		if (count($accounts) > 1)
		{
			$destination = "'+encodeURIComponent(document.location)+'";
			if (!is_null(UserConfig::$accountSwitchDestination)) {
				$destination = UserConfig::$accountSwitchDestination;
			}

			?><select id="userbase-navbox-account-picker" name="account" onchange="document.location.href='<?php echo UserConfig::$USERSROOTURL ?>/change_account.php?return=<?php echo $destination ?>&account='+this.value"><?php

			foreach ($accounts as $account)
			{
				?><option value="<?php echo $account->getID()?>"<?php if ($current_account->isTheSameAs($account)) { echo ' selected'; } ?>><?php echo UserTools::escape($account->getName())?></option><?php
			}
		?></select>
		<?php
		}

		if (!is_null(UserConfig::$onLoginStripLinks)) {
			$links = call_user_func_array(
				UserConfig::$onLoginStripLinks,
				array($current_user, $current_account)
			);

			foreach ($links as $link) {
				?><span<?php
					if (array_key_exists('id', $link)) {
						?> id="<?php echo $link['id'] ?>"<?php
					}
				?>><a href="<?php echo $link['url'] ?>"<?php
					if (array_key_exists('title', $link)) {
						?> title="<?php echo $link['title'] ?>"<?php
					}
					if (array_key_exists('target', $link)) {
						?> target="<?php echo $link['target'] ?>"<?php
					}
				?>><?php echo $link['text'] ?></a></span> | <?php
			}
		}
		?>

		<span id="userbase-navbox-username"><a href="<?php echo UserConfig::$USERSROOTURL ?>/edit.php" title="<?php echo UserTools::escape($current_user->getName())?>'s user information"><?php echo UserTools::escape($current_user->getName()) ?></a></span> |
		<span id="userbase-navbox-logout"><a href="<?php echo UserConfig::$USERSROOTURL ?>/logout.php">logout</a></span>
		<?php
	}
	else
	{
	?>
		<span id="userbase-navbox-signup"><a href="<?php echo UserConfig::$USERSROOTURL ?>/register.php">Sign Up Now!</a></span> |
		<span id="userbase-navbox-login"><a href="<?php echo UserConfig::$USERSROOTURL ?>/login.php">log in</a></span>
	<?php
	}
	?>
</div>
<?php
}

_USERBASE_render_navbox();
