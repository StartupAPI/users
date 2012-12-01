<?php
require_once(dirname(__FILE__) . '/admin.php');

if (!array_key_exists('slug', $_GET)) {
	header("HTTP/1.0 400 Plan slug is not specified");
	?><h1>400 Plan slug is not specified</h1><?php
	exit;
}

$plan_slug = intval(trim($_GET['slug']));

$plan = Plan::getPlanBySlug($plan_slug);
if (is_null($plan)) {
	header("HTTP/1.0 404 Plan Not Found");
	?><h1>404 Plan Not Found</h3><?php
	exit;
}

$ADMIN_SECTION = 'plans';
$BREADCRUMB_EXTRA = $plan->name;
require_once(dirname(__FILE__) . '/header.php');
?>
<div class="span9">
	<h2><?php echo UserTools::escape($plan->name); ?></h2>
	<p>
		<?php if (isset($plan->capabilities['individual']) && $plan->capabilities['individual']) { ?>
			<span class="label">individual</span>
		<?php } ?>
	</p>
</div>

<?php
require_once(dirname(__FILE__) . '/footer.php');