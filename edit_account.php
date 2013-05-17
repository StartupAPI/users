<?php
require_once(__DIR__ . '/global.php');

UserTools::preventCSRF();

if (UserConfig::$useAccounts) {
	$current_user = User::require_login();
	$account = $current_user->getCurrentAccount();

	if (is_null($account) || $account->getUserRole($current_user) != Account::ROLE_ADMIN) {
		header('Location: ' . UserConfig::$USERSROOTURL . '/manage_account.php');
		exit;
	}

	$errors = array();

	$new_account_name = NULL;
	$selected_plan_slug = NULL;

	if (array_key_exists('account_name', $_POST)) {
		$new_account_name = trim($_POST['account_name']);
		if (empty($new_account_name)) {
			$errors['name'][] = "Account name can't be empty";
		} else {
			$account->setName($new_account_name);
		}

		if (count($errors) == 0) {
			header('Location: ' . UserConfig::$USERSROOTURL . '/manage_account.php');
			exit;
		}
	}

	$ADMIN_SECTION = 'accounts';
	require_once(__DIR__ . '/sidebar_header.php');
	?>
	<div class="span9">
		<h2>Edit Account</h2>

		<?php
		if (count($errors) > 0) {
			?>
			<div class="alert alert-block alert-error fade-in">
				<h4 style="margin-bottom: 0.5em">Snap!</h4>

				<ul>
					<?php
					foreach ($errors as $field => $errorset) {
						foreach ($errorset as $error) {
							?>
							<li>
								<label style="cursor: pointer" for="startupapi-admin-account-edit-<?php echo $field ?>"><?php echo $error ?></label>
							</li>
							<?php
						}
					}
					?>
				</ul>
			</div>
			<?php
		}
		?>

		<form class="form-horizontal" action="" method="POST">
			<div class="control-group">
				<label class="control-label" for="startupapi-admin-account-edit-name">Account Name</label>
				<div class="controls">
					<input type="text" id="startupapi-admin-account-edit-name" name="account_name" value="<?php echo UserTools::escape($account->getName()) ?>">
				</div>
			</div>

			<div class="control-group">
				<div class="controls">
					<button type="submit" class="btn btn-primary">Update Account</button>
					<a class="btn" href="<?php echo UserConfig::$USERSROOTURL ?>/manage_account.php">Discard Changes</a>
				</div>
			</div>
			<?php UserTools::renderCSRFNonce(); ?>
		</form>
	</div>
<?php
}
require_once(__DIR__ . '/sidebar_footer.php');