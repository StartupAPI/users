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

</form>
</div>

<?php
$cohorts = null;

if (is_null($selectedactivityid)) {
	$selectedactivityid = $firstactivityid;
}

$selectedactivity = UserConfig::$activities[$selectedactivityid];

$regperiodtype = 'Month';
$regperiodlength = 30;
$actperiodtype = 'Month';
$actperiodlength = 30;

$cohorts = User::getActivityRateByRegistrationPeriod($selectedactivityid, $regperiodlength, $actperiodlength);

$minregperiod = 0;
$maxregperiod = $minregperiod;

$minactperiod = 0;
$maxactperiod = $minactperiod;

$maxvalue = 0;
foreach (array_keys($cohorts) as $regperiod) {
	foreach (array_keys($cohorts[$regperiod]['rates']) as $actperiod) {
		if ($regperiod < $minregperiod || $actperiod < $minactperiod) {
			continue;
		}

		if ($regperiod > $maxregperiod) {
			$maxregperiod = $regperiod;
		}

		if ($actperiod > $maxactperiod) {
			$maxactperiod = $actperiod;
		}

		if ($cohorts[$regperiod]['rates'][$actperiod] > $maxvalue) {
			$maxvalue = $cohorts[$regperiod]['rates'][$actperiod];
		}
	}
}

$total = 0;
$cohort_type = 'Reg. date';

if (!is_null($cohorts)) {

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
	<th><?php echo $cohort_type ?></th>
<?php
for ($period = $minactperiod; $period <= $maxactperiod; $period ++) {
?>	<th><?php echo $actperiodtype.' '.$period ?></th><?php
}
?>
</tr>

<?php 

for ($regperiod = $maxregperiod; $regperiod >= $minregperiod; $regperiod--) {
	?><tr><th><?php
		echo $regperiodtype.' '.$regperiod."\n<br/>"
			. $cohorts[$regperiod]['periodstart'].' - '
			. $cohorts[$regperiod]['periodend']
	?></th><?php
	for ($actperiod = $minactperiod; $actperiod <= $maxactperiod; $actperiod++) {

		?><td><?php
		if (array_key_exists($regperiod, $cohorts)
			&& array_key_exists($actperiod, $cohorts[$regperiod]['rates']))
		{
			$rate = $cohorts[$regperiod]['rates'][$actperiod];

			$ratepercent = round($rate * 100, 1);
			if ($zoom) {
				$boxsize = ceil(sqrt($squaresize * $squaresize * $rate / $maxvalue));
			} else {
				$boxsize = ceil(sqrt($squaresize * $squaresize * $rate));
			}

			?><div class="outerbox"<?php echo $boxes ? 'title="'.$ratepercent.'% ('.$cohorts[$regperiod]['activeusers'][$actperiod].' out of '.$cohorts[$regperiod]['totalusers'].' uers registered during this period)"' : ''; ?>><?php
			if ($boxes) {

				?>
				<div class="innerbox" style="width: <?php echo $boxsize ?>px; height: <?php echo $boxsize ?>px"></div>
				<?php
			}
			?><div class="ratebox"><?php
			echo $ratepercent?>%<?php
			if ($regperiod > $minregperiod) {
				$prevrate = 0;
				if (array_key_exists($regperiod - 1, $cohorts) &&
					array_key_exists($actperiod, $cohorts[$regperiod - 1]['rates']))
				{
					$prevrate = $cohorts[$regperiod - 1]['rates'][$actperiod];
				}

				$diff = $rate - $prevrate;

				if ($diff > 0) {
					?> <div class="up">+<?php echo round($diff * 100, 2) ?>&uarr;</div><?php
				} else if ($diff < 0) {
					?> <div class="down"><?php echo round($diff * 100, 2) ?>&darr;</div><?php
				}
			}
			?><div class="numbers"><?php echo $cohorts[$regperiod]['activeusers'][$actperiod] ?> / <?php echo $cohorts[$regperiod]['totalusers'] ?></div>
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
