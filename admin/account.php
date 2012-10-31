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

if (array_key_exists('add_user', $_POST) && is_numeric($_POST['add_user'])) {
	$user = User::getUser($_POST['add_user']);

	if (!is_null($user)) {
		$account->addUser($user);

		header("Location: #message=added");
		exit;
	}

	header("Location: #message=nosuchuser");
	exit;
}

$plan = $account->getPlan();

foreach (array_keys($_POST) as $key) {
	if (strpos($key, 'delete_user_') === 0) {
		$userid = substr($key, strlen('delete_user_'));

		$user = User::getUser($userid);
		if (!is_null($user)) {

			$account->removeUser($user);

			header("Location: #message=deleted");
			exit;
		}

		header("Location: #message=nosuchuser");
		exit;
	}
}

$ADMIN_SECTION = 'accounts';
$BREADCRUMB_EXTRA = $account->getName();
require_once(dirname(__FILE__) . '/header.php');
?>
<div class="span9">
	<form class="form-horizontal" style="margin: 0" action="" method="POST">

		<h2><?php echo UserTools::escape($account->getName()); ?></h2>
		<p>
			<b>Plan</b>:
			<a href="<?php echo UserConfig::$USERSROOTURL ?>/admin/plan.php?id=<?php echo UserTools::escape($plan->getID()); ?>">
				<?php echo UserTools::escape($plan->getName()); ?></a>

			<?php if ($plan->isIndividual()) { ?>
				<span class="label">individual</span>
			<?php } ?>
		</p>

		<h3>Members</h3>
		<table class="table">
			<?php
			foreach ($account->getUsers() as $user_and_role) {
				$user = $user_and_role[0];
				$role = $user_and_role[1];
				?>
				<tr>
					<td>
						<a href="<?php echo UserConfig::$USERSROOTURL ?>/admin/user.php?id=<?php echo UserTools::escape($user->getID()) ?>">
							<?php echo UserTools::escape($user->getName()) ?>
						</a>
					</td>
					<td>
						<?php if ($role == Account::ROLE_ADMIN) { ?>
							<span class="label label-important">admin</span>
						<?php } else { ?>
							<span class="label">user</span>
						<?php } ?>
					</td>
					<td>
						<?php if ($role == Account::ROLE_ADMIN) { ?>
						<span class="btn btn-mini disabled" title="Can't remove account administrator">remove</span>
						<?php } else { ?>
							<input type="submit" class="btn btn-mini" name="delete_user_<?php echo UserTools::escape($user->getID()) ?>" value="remove"/>
						<?php } ?>
					</td>
				</tr>
			<?php } ?>
		</table>
		<?php UserTools::renderCSRFNonce(); ?>
	</form>

	<?php if (!$plan->isIndividual()) { ?>
		<div class="well">
			<form class="form-horizontal" style="margin: 0" action="" method="POST">

				<label class="control-label">
					Add user (enter user ID):
				</label>
				<div class="controls">
					<input type="text" class="input-mini" name="add_user"/>
					<input class="btn btn-primary" type="submit" value="Add user"/>
					<?php UserTools::renderCSRFNonce(); ?>
				</div>
			</form>
		</div>
	<?php } ?>
</div>

<?php
require_once(dirname(__FILE__) . '/footer.php');