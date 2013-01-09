<?php
require_once(__DIR__ . '/global.php');

require_once(__DIR__ . '/classes/User.php');

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
require_once(__DIR__ . '/sidebar_header.php');
?>
<h2>Account: <?php echo $account->getName() ?></h2>

<?php if (UserConfig::$useSubscriptions) { ?>
	<div id="plan">
		<p>Subscription plan: <b><?php echo $account->getPlan()->name ?></b>
			<a class="btn btn-mini" href="<?php echo UserConfig::$USERSROOTURL ?>/subscription_details.php"><i class="icon-search"></i> Details</a>
			<a class="btn btn-mini" href="<?php echo UserConfig::$USERSROOTURL ?>/plans.php">Change</a>
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
				<?php if ($role == Account::ROLE_ADMIN) { ?><span class="badge badge-important">admin</span><?php } ?>
			</li>
			<?php
		}
		?>
	</ul>
</div>
<?php
require_once(__DIR__ . '/sidebar_footer.php');
