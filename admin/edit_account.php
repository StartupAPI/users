<?php
require_once(__DIR__ . '/admin.php');

$account_id = $_GET['id'];
$account = Account::getByID($account_id);

if (is_null($account)) {
	header('Location: ' . UserConfig::$USERSROOTURL . '/admin/accounts.php');
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

	if (array_key_exists('plan_slug', $_POST)) {
		$selected_plan_slug = $_POST['plan_slug'];
		if (in_array($selected_plan_slug, Plan::getPlanSlugs())
				&& $account->getPlanSlug() !== $_POST['plan_slug']
		) {
			if (!$account->activatePlan($selected_plan_slug)) {
				$errors['plan'][] = "Can't update plan";
			}
		}
	}

	if (count($errors) == 0) {
		header('Location: ' . UserConfig::$USERSROOTURL . '/admin/account.php?id=' . $account->getID());
		exit;
	}
}

$ADMIN_SECTION = 'accounts';
require_once(__DIR__ . '/header.php');
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
		<?php
		$plan_slugs = Plan::getPlanSlugs();

		if (count($plan_slugs) > 1) {
			?>
			<div class="control-group<?php if (array_key_exists('plan', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['plan'])) ?><?php } ?>">
				<label class="control-label">Service plan</label>
				<div class="controls">
					<?php
					$current_plan_slug = $account->getPlanSlug();


					foreach ($plan_slugs as $plan_slug) {
						$plan = Plan::getPlanBySlug($plan_slug);
						?>
						<label class="radio">
							<input type="radio"
								   name="plan_slug"
								   value="<?php echo UserTools::escape($plan_slug) ?>"
								   <?php
								   if ($plan_slug == $current_plan_slug) {
									   ?>
									   checked
									   <?php
								   }
								   ?>
								   >
							<span class="badge badge-info"><i class="icon-briefcase icon-white"></i> <?php echo UserTools::escape($plan->name) ?></span>
						</label>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		}
		?>
		<div class="control-group">
			<div class="controls">
				<button type="submit" class="btn btn-primary">Update Account</button>
				<a class="btn" href="account.php?id=<?php echo $account->getID() ?>">Discard Changes</a>
			</div>
		</div>
		<?php UserTools::renderCSRFNonce(); ?>
	</form>
</div>
<?php
require_once(__DIR__ . '/footer.php');