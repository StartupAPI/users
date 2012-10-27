<?php
require_once(dirname(__FILE__) . '/admin.php');

if (!array_key_exists('id', $_GET) || !is_numeric($_GET['id'])) {
	header("HTTP/1.0 400 Plan ID is not specified");
	?><h1>400 Plan ID is not specified</h1><?php
	exit;
}

$plan_id = intval(trim($_GET['id']));

$plan = Plan::getByID($plan_id);
if (is_null($plan)) {
	header("HTTP/1.0 404 Plan Not Found");
	?><h1>404 Plan Not Found</h3><?php
	exit;
}

$ADMIN_SECTION = 'plans';
$BREADCRUMB_EXTRA = $plan->getName();
require_once(dirname(__FILE__) . '/header.php');
?>
<div class="span9">
	<h2><?php echo UserTools::escape($plan->getName()); ?></h2>
	<p>
		<?php if ($plan->isIndividual()) { ?>
			<span class="label">individual</span>
		<?php } ?>
	</p>
</div>

<?php
require_once(dirname(__FILE__) . '/footer.php');