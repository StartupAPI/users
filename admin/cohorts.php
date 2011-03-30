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
$normalize = array_key_exists('normalize', $_REQUEST);
?>

<span style="padding-left: 1em"><input type="checkbox" name="boxes"<?php if ($boxes) {?> checked<?php } ?> onchange="document.activities.submit();"/><?php if (!$boxes && $normalize) {?><input type="hidden" name="normalize" value="on"/><?php } ?> Show Boxes</span>
<span style="padding-left: 1em"><input type="checkbox" name="normalize"<?php if (!$boxes) {?> disabled<?php } ?><?php if ($boxes && $normalize) {?> checked<?php } ?> onchange="document.activities.submit();"/> Normalize</span>

</form>
</div>

<?php
$cohorts = null;

if (is_null($selectedactivityid)) {
	$selectedactivityid = $firstactivityid;
}

$selectedactivity = UserConfig::$activities[$selectedactivityid];

$regperiodname = 'Month';
$regperiodlength = 30;
$actperiodname = 'Month';
$actperiodlength = 30;

$cohorts = User::getActivityRateByRegistrationPeriod($selectedactivityid, $regperiodlength, $actperiodlength);

$minregperiod = 5;
$maxregperiod = $minregperiod;

$minactperiod = 0;
$maxactperiod = $minactperiod;

$maxvalue = 0;
foreach (array_keys($cohorts) as $regperiod) {
	foreach (array_keys($cohorts[$regperiod]) as $actperiod) {
		if ($regperiod < $minregperiod || $actperiod < $minactperiod) {
			continue;
		}

		if ($regperiod > $maxregperiod) {
			$maxregperiod = $regperiod;
		}

		if ($actperiod > $maxactperiod) {
			$maxactperiod = $actperiod;
		}

		if ($cohorts[$regperiod][$actperiod] > $maxvalue) {
			$maxvalue = $cohorts[$regperiod][$actperiod];
		}
	}
}

$total = 0;
$cohort_type = 'Reg. date';

if (!is_null($cohorts)) {

$squaresize = 100;
?>
<style>
.outerbox {
	width: <?php echo $squaresize ?>px;
	height: <?php echo $squaresize ?>px;
	border: 1px dashed #e51837;
	padding: 2px;
}

.innerbox {
	background-color: #e51837;
	color: black;
}

.nobox {
	width: <?php echo $squaresize ?>px;
	height: <?php echo $squaresize ?>px;
	border: 1px dashed #e51837;
	padding: 2px;
	font-size: <?php echo ceil($squaresize / 5) ?>px;
}
</style>

<table cellpadding="5" cellspacing="0" border="0">
<tr>
	<th><?php echo $cohort_type ?></th>
<?php
for ($period = $minactperiod; $period <= $maxactperiod; $period ++) {
?>	<th><?php echo $actperiodname.' '.$period ?></th><?php
}
?>
</tr>

<?php 

for ($regperiod = $minregperiod; $regperiod <= $maxregperiod; $regperiod++) {
	?><tr><th><?php echo $regperiodname.' '.$regperiod ?></th><?php
	for ($actperiod = $minactperiod; $actperiod <= $maxactperiod; $actperiod++) {

		?><td><?php
		if ($boxes) {
			if (array_key_exists($regperiod, $cohorts) && array_key_exists($actperiod, $cohorts[$regperiod]))
			{
				$rate = ceil($cohorts[$regperiod][$actperiod] * 1000) / 10;

				if ($normalize) {
					$percent = ceil($cohorts[$regperiod][$actperiod] / $maxvalue * 100);
				} else {
					$percent = ceil($cohorts[$regperiod][$actperiod] * 100);
				}

				?><div class="outerbox" title="<?php echo $rate ?>%"><div style="width: <?php echo $percent ?>%; height: <?php echo $percent ?>%" class="innerbox"></div></div><?php
			} else {
				?><div style="width: <?php echo $squaresize ?>px; height: <?php echo $squaresize ?>px"/><?php
	#			echo '<span style="color: silver">0</span>';
			}
		} else {
			if (array_key_exists($regperiod, $cohorts) && array_key_exists($actperiod, $cohorts[$regperiod]))
			{
				$rate = ceil($cohorts[$regperiod][$actperiod] * 1000) / 10;
				?><div class="nobox"><?php echo $rate ?>%</div><?php
			}
			
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
