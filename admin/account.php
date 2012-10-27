<?php
require_once(dirname(__FILE__) . '/admin.php');

if (!array_key_exists('id', $_GET) || !$_GET['id']) {
	header("HTTP/1.0 400 Account ID is not specified");
	?><h1>400 Account ID is not specified</h1><?php
	exit;
}

$account_id = intval(trim($_GET['id']));

$account = Account::getByID($account_id);
if (is_null($account)) {
	header("HTTP/1.0 404 Account Not Found");
	?><h1>404 Account Not Found</h3><?php
	exit;
}

$ADMIN_SECTION = 'accounts';
$BREADCRUMB_EXTRA = $account->getName();
require_once(dirname(__FILE__) . '/header.php');

$plan = $account->getPlan();
?>
<div class="span9">
	<h2><?php echo UserTools::escape($account->getName()); ?></h2>
	<p>
		<b>Plan</b>:
		<a href="<?php echo UserConfig::$USERSROOTURL ?>/admin/plan.php?id=<?php echo UserTools::escape($plan->getID()); ?>">
			<?php echo UserTools::escape($plan->getName()); ?></a>

		<?php if ($plan->isIndividual()) { ?>
			<span class="label">individual</span>
		<?php } ?>
	</p>

	<?php if (!$plan->isIndividual()) { ?>
		<h3>Members</h3>
		<ul>
			<?php
			foreach ($account->getUsers() as $user_and_role) {
				$user = $user_and_role[0];
				$role = $user_and_role[1];
				?>
				<li>
					<a href="<?php echo UserConfig::$USERSROOTURL ?>/admin/user.php?id=<?php echo UserTools::escape($user->getID()) ?>">
						<?php echo UserTools::escape($user->getName()) ?>
					</a>
					<?php if ($role == Account::ROLE_ADMIN) { ?>
					<spam class="label label-important">admin</span>
					<?php } else { ?>
						<spam class="label label-info">user</span>
						<?php } ?>
						</li>
					<?php } ?>
					</ul>
				<?php } ?>
				</div>

				<?php
				require_once(dirname(__FILE__) . '/footer.php');