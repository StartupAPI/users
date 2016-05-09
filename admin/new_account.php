<?php
namespace StartupAPI;

require_once(__DIR__ . '/admin.php');

if (array_key_exists('account_name', $_POST)) {
	$account_name = trim($_POST['account_name']);

	if (!empty($account_name)) {
		$plan_slug = UserConfig::$default_plan_slug;
		if (array_key_exists('plan_slug', $_POST)) {
			if (in_array($_POST['plan_slug'], Plan::getPlanSlugs())) {
				$plan_slug = $_POST['plan_slug'];
			}
		}

		$new_account = Account::createAccount(trim($_POST['account_name']), $plan_slug);

		if (!is_null($new_account)) {
			header('Location: ' . UserConfig::$USERSROOTURL . '/admin/account.php?id=' . $new_account->getID());
			exit;
		}
	}
}

$ADMIN_SECTION = 'accounts';
require_once(__DIR__ . '/header.php');
?>
<div class="span9">
	<h2>New Account</h2>
	<form class="form-horizontal" action="" method="POST">
		<div class="control-group">
			<label class="control-label" for="account_name">Account Name</label>
			<div class="controls">
				<input type="text" name="account_name"<?php if (!empty($account_name)) { ?> value="<?php echo UserTools::escape($account_name) ?>"<?php } ?>>
			</div>
		</div>
		<?php
		$plan_slugs = Plan::getPlanSlugs();

		if (count($plan_slugs) > 1) {
			?>
			<div class="control-group">
				<label class="control-label">Service plan</label>
				<div class="controls">
					<?php
					foreach ($plan_slugs as $plan_slug) {
						$plan = Plan::getPlanBySlug($plan_slug);
						?>
						<label class="radio">
							<input type="radio"
								   name="plan_slug"
								   value="<?php echo UserTools::escape($plan_slug) ?>"
								   <?php
								   if ($plan_slug == UserConfig::$default_plan_slug) {
									   ?>
									   checked
									   <?php
								   }
								   ?>
								   >
							<span class="badge badge-info"><i class="icon-briefcase icon-white"></i> <?php echo UserTools::escape($plan->getName()) ?></span>
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
				<button type="submit" class="btn btn-primary">Create Account</button>
			</div>
		</div>
		<?php UserTools::renderCSRFNonce(); ?>
	</form>
</div>
<?php
require_once(__DIR__ . '/footer.php');
