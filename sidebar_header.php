<?php
if (!isset($SECTION)) {
	$SECTION = null;
}

require_once(UserConfig::$header);
?>
<div class="container-fluid" style="margin-top: 1em">
	<div class="row-fluid">
		<div class="span3">
			<div class="well sidebar-nav startupapi-sidebar">
				<ul class="nav nav-list">
					<li<?php if ($SECTION == 'profile_info') { ?> class="active"<?php } ?>>
						<a href="<?php echo UserConfig::$USERSROOTURL ?>/edit.php">Profile Information</a>
					</li>
					<li class="nav-header">Login</li>
					<?php
					foreach (UserConfig::$authentication_modules as $module) {
						?>
						<li<?php if ($SECTION == 'login_' . $module->getID()) { ?> class="active"<?php } ?>>
							<a href="<?php echo UserConfig::$USERSROOTURL ?>/edit.php?module=<?php echo $module->getID() ?>">
								<?php echo $module->getTitle() ?>
							</a>
						</li>
						<?php
					}

					if (UserConfig::$enableUserInvitations) {
						?>
						<li class="nav-header">Connections</li>
						<li<?php if ($SECTION == 'invitations') { ?> class="active"<?php } ?>>
							<a href="<?php echo UserConfig::$USERSROOTURL ?>/invitations.php"><?php echo UserConfig::$userInvitationSectionTitle ?></a>
						</li>

						<?php
					}

					if (UserConfig::$useAccounts) {
						$current_account = $user->getCurrentAccount();

						if ($current_account->getUserRole($user) == Account::ROLE_ADMIN) {
							?>
							<li class="nav-header">My Account</li>
							<li<?php if ($SECTION == 'manage_account') { ?> class="active"<?php } ?>>
								<a href="<?php echo UserConfig::$USERSROOTURL ?>/manage_account.php">
									<?php echo UserTools::escape($current_account->getName()) ?>
								</a>
							</li>
							<?php
						}
					}

					if (UserConfig::$enableGamification) {
						?>
						<li class="nav-header">My achievements</li>
						<li<?php if ($SECTION == 'badges') { ?> class="active"<?php } ?>>
							<a href="<?php echo UserConfig::$USERSROOTURL ?>/badges.php">Badges</a>
						</li>
						<?php
					}

					if (!is_null(UserConfig::$maillist) && file_exists(UserConfig::$maillist)) {
						?>
						<li class="nav-header">Mail preferences</li>
						<li<?php if ($SECTION == 'maillist') { ?> class="active"<?php } ?>>
							<a href="<?php echo UserConfig::$USERSROOTURL ?>/maillist.php">Mail preferences</a>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
		</div>
		<div class="span9">