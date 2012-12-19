<?php
require_once(dirname(__FILE__) . '/global.php');

require_once(dirname(__FILE__) . '/User.php');

if (!UserConfig::$useAccounts) {
	header('Location: ' . UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

$user = User::require_login();

$account = Account::getCurrentAccount($user);
if ($account->getUserRole($user) !== Account::ROLE_ADMIN) {
	header('Location: ' . UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

$SECTION = 'manage_account';
require_once(dirname(__FILE__) . '/sidebar_header.php');
?>
<h2>Account: <?php echo $account->getName() ?></h2>

<?php if (UserConfig::$useSubscriptions) { ?>
	<div id="plan">
		<p>Subscription plan: <b><?php echo $account->getPlan()->name ?></b> -
			<a href="<?php echo UserConfig::$USERSROOTURL ?>/subscription_details.php">[ details ]</a>
			<a href="<?php echo UserConfig::$USERSROOTURL ?>/plans.php">[ change ]</a>
	</div>
<?php } ?>

<div id="members">
	<h3>Members</h3>
	<ul>
		<?php
		$users_and_roles = $account->getUsers();

		foreach ($users_and_roles as $user_and_role) {
			$member = $user_and_role[0];
			$role = $user_and_role[1];
			?><li>
				<?php echo $member->getName(); ?>
				<?php if ($role == Account::ROLE_ADMIN) { ?><span class="label label-important">admin</span><?php } ?>
			</li>
			<?php
		}
		?>
	</ul>
</div>
<?php
require_once(dirname(__FILE__) . '/sidebar_footer.php');
