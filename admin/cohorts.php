<?php
$ADMIN_SECTION = 'cohorts';
require_once(dirname(__FILE__).'/header.php');
?>
<form action="" name="activities" style="margin: 1em 0">
<div>
Pick activity:
<select name="activityid" onchange="document.activities.submit();">
<?php

// sorting by point-value of activities
function mostpoints($a, $b) {
	if (UserConfig::$activities[$a][1] > UserConfig::$activities[$b][1]) {
		return -1;
	} else if (UserConfig::$activities[$a][1] < UserConfig::$activities[$b][1]) {
		return 1;
	}

	return strcmp(UserConfig::$activities[$a][0], UserConfig::$activities[$b][0]);
}

uksort(UserConfig::$activities, 'mostpoints');

$stats = User::getActivityStatistics();

$selectedactivityid = null;

if (array_key_exists('activityid', $_REQUEST) && is_numeric($_REQUEST['activityid'])) {
	$selectedactivityid = $_REQUEST['activityid'];
}

$firstactivityid = null; // most popular one, first on the list

foreach (UserConfig::$activities as $id => $activity) {
	if (!array_key_exists($id, $stats)) {
		continue;
	}

	if (is_null($firstactivityid)) {
		$firstactivityid = $id;
	}
?>
	<option value="<?php echo $id ?>"<?php echo $selectedactivityid == $id ? ' selected="yes"' : '' ?>><?php echo $activity[0] ?> (<?php echo $activity[1] ?> points)</option>
<?php } ?>
</select>


<?php 

$boxes = array_key_exists('boxes', $_REQUEST);
$zoom = array_key_exists('zoom', $_REQUEST);
?>

<span style="padding-left: 1em"><input type="checkbox" id="boxes" name="boxes"<?php if ($boxes) {?> checked<?php } ?> onchange="document.activities.submit();"/><?php if (!$boxes && $zoom) {?><input type="hidden" name="zoom" value="on"/><?php } ?> <label for="boxes">Show Boxes</label></span>
<span style="padding-left: 1em" title="Zoom in to fit maximum velue into the box"><input type="checkbox"<?php if ($boxes) {?> id="zoom" name="zoom"<?php } else { ?> disabled<?php } ?><?php if ($boxes && $zoom) {?> checked<?php } ?> onchange="document.activities.submit();"/> <label for="zoom">Zoom in</label></span>

<div>
How to drop users into cohorts:
<select name="cohorts" onchange="document.activities.submit();">
<?php
$requested_cohort_provider = null;
if (array_key_exists('cohorts', $_REQUEST)) {
	$requested_cohort_provider = $_REQUEST['cohorts'];
}

$cohort_provider = null;
foreach (UserConfig::$cohort_providers as $provider) {
	$id = $provider->getID();

	$selected = false;
	if ($id == $requested_cohort_provider) {
		$cohort_provider = $provider;
		$selected = true;
	}

	?><option value="<?php echo UserTools::escape($id) ?>"<?php if ($selected) { ?> selected="yes"<?php } ?>>
	<?php echo UserTools::escape($provider->getTitle()) ?>
	</option><?php
}

if (is_null($cohort_provider)) {
	$cohort_provider = UserConfig::$cohort_providers[0];
}

?>
</select>
</div>

</form>
</div>

<?php
$cohort_lookup = array();

$cohorts = $cohort_provider->getCohorts();
foreach ($cohorts as $cohort) {
	$cohort_lookup[$cohort->getID()] = $cohort;
}

$aggregates = null;

if (is_null($selectedactivityid)) {
	$selectedactivityid = $firstactivityid;
}

$selectedactivity = UserConfig::$activities[$selectedactivityid];

$actperiodtype = 'Month';
$actperiodlength = 30;

$aggregates = $cohort_provider->getActivityRate($selectedactivityid, $actperiodlength);

$minactperiod = 0;
$maxactperiod = $minactperiod;

$maxvalue = 0;
foreach (array_keys($aggregates) as $cohort_id) {
	foreach (array_keys($aggregates[$cohort_id]) as $actperiod) {
		if ($actperiod < $minactperiod) {
			continue;
		}

		if ($actperiod > $maxactperiod) {
			$maxactperiod = $actperiod;
		}

		$rate = $aggregates[$cohort_id][$actperiod] / $cohort_lookup[$cohort_id]->getTotal();

		if ($rate > $maxvalue) {
			$maxvalue = $rate;
		}
	}
}

$total = 0;

if (!is_null($aggregates)) {

$squaresize = 70;
?>
<style>
.outerbox {
	width: <?php echo $squaresize + 4 ?>px;
	height: <?php echo $squaresize + 4 ?>px;
	border: 1px dashed black;
	position: relative;
	padding: 2px;
}

.emptybox {
	width: <?php echo $squaresize + 4 ?>px;
	height: <?php echo $squaresize + 4 ?>px;
	border: 1px dashed silver;
	padding: 2px;
}


.innerbox {
	background-color: #759ff9;
	border: 2px solid #4269d6;
	color: black;
	position: absolute;
	bottom: 2px;
	left: 2px;
}

.ratebox {
	text-align: right;
	font-size: <?php echo ceil($squaresize / 4) ?>px;
	font-weight: bold;
	position: absolute;
	right: 2px;
	top: 2px;
}

.up {
	color: green;
	font-size: <?php echo ceil($squaresize / 6) ?>px;
}

.down {
	color: red;
	font-size: <?php echo ceil($squaresize / 6) ?>px;
}

.numbers {
	font-size: <?php echo ceil($squaresize / 7) ?>px;
}
</style>

<table cellpadding="5" cellspacing="0" border="0">
<tr>
	<th><?php echo $cohort_provider->getDimensionTitle() ?></th>
<?php
for ($period = $minactperiod; $period <= $maxactperiod; $period ++) {
?>	<th><?php echo $actperiodtype.' '.$period ?></th><?php
}
?>
</tr>

<?php 
for ($cohort_number = 0; $cohort_number < count($cohorts); $cohort_number += 1) {
	$cohort = $cohorts[$cohort_number];
	$total_cohort_users = $cohort->getTotal();

	?><tr><th><?php echo $cohort->getTitle() ?></th><?php
	for ($actperiod = $minactperiod; $actperiod <= $maxactperiod; $actperiod++) {
		$cohort_id = $cohort->getID();

		?><td><?php
		if (array_key_exists($cohort_id, $aggregates)
			&& array_key_exists($actperiod, $aggregates[$cohort_id]))
		{
			$rate = $aggregates[$cohort_id][$actperiod] / $total_cohort_users;

			$ratepercent = round($rate * 100, 2);
			if ($zoom) {
				$boxsize = ceil(sqrt($squaresize * $squaresize * $rate / $maxvalue));
			} else {
				$boxsize = ceil(sqrt($squaresize * $squaresize * $rate));
			}

			?><div class="outerbox"<?php echo $boxes ? 'title="'.$ratepercent.'% ('.$aggregates[$cohort_id][$actperiod].' out of total '.$total_cohort_users.' users in this cohort)"' : ''; ?>><?php
			if ($boxes) {

				?>
				<div class="innerbox" style="width: <?php echo $boxsize ?>px; height: <?php echo $boxsize ?>px"></div>
				<?php
			}
			?><div class="ratebox"><?php
			echo $ratepercent?>%<?php

			if ($cohort_provider->canCompareToPreviousCohort()
				&& $cohort_number < (count($cohorts) - 1))
			{
				$prevrate = 0;
				$next_cohort = $cohorts[$cohort_number + 1];
				$next_cohort_id = $next_cohort->getID();

				if (array_key_exists($next_cohort_id, $aggregates) &&
					array_key_exists($actperiod, $aggregates[$next_cohort_id]))
				{
					$prevrate = $aggregates[$next_cohort_id][$actperiod]
							/ $next_cohort->getTotal();
				}

				$diff = $rate - $prevrate;

				if ($diff > 0) {
					?> <div class="up">+<?php echo round($diff * 100, 2) ?>%</div><?php
				} else if ($diff < 0) {
					?> <div class="down"><?php echo round($diff * 100, 2) ?>%</div><?php
				}
			}

			?><div class="numbers"><?php echo $aggregates[$cohort_id][$actperiod] ?> / <?php echo $total_cohort_users ?></div>
			</div>
			</div>
			<?php
		} else {
			?><div class="emptybox" /><?php
#			echo '<span style="color: silver">0</span>';
		}
		?></td><?php
	}
	?></tr><?php
}
?>
</table>

<?php
}

require_once(dirname(__FILE__).'/footer.php');
