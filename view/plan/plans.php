<?php

$template_data['account'] = array('name' => $account->getName());

session_start();

if (isset($_SESSION['message'])) {
	$template_data['message'] = $_SESSION['message'];
	unset($_SESSION['message']);
	$fatal = isset($_SESSION['fatal']) ? $_SESSION['fatal'] : 0;
	unset($_SESSION['fatal']);
	if ($fatal) {
		$template_data['fatal'] = 1;
		return;
	}
}

if (!$account->isActive()) {
	$template_data['message'] = array('This account is not active. Please activate it first.');
	$template_data['fatal'] = 1;
	return;
}

$plan_data = array(
	'slug', 'name', 'description', 'base_price', 'base_period', 'details_url', 'downgrade_to', 'grace_period', 'available');
$schedule_data = array(
	'slug', 'name', 'description', 'charge_amount', 'charge_period');

$plans = array();

UserTools::preventCSRF();
$template_data['CSRF_NONCE'] = UserTools::$CSRF_NONCE;

$template_data['next_charge'] = $account->getNextCharge();

$balance = $account->getBalance();
$template_data['balance'] = $balance;
$plan_slugs = Plan::getPlanSlugs();

foreach ($plan_slugs as $p) { # Iterate over all configured plans
	$this_plan = Plan::getPlanBySlug($p);
	$plan = array();
	foreach ($plan_data as $d) {
		# Put all plan properties
		$plan[$d] = $this_plan->$d;
	}

	$current_plan = $account->getPlan(); // can be FALSE
	//
	# Mark plan as current if slugs match
	if ($current_plan && $current_plan->slug == $this_plan->slug) {
		$plan['current'] = TRUE;
	} else {
		$plan['current'] = FALSE;
	}

	$schedule = array();
	$schedule_slugs = $this_plan->getPaymentScheduleSlugs(); # Iterate over all schedules of this plan

	if (empty($schedule_slugs) && !is_null($account->getNextPlan()) &&
			$account->getNextPlan()->slug == $this_plan->slug) {
		$plan['chosen'] = TRUE;
	} else {
		$plan['chosen'] = FALSE;
	}

	foreach ($schedule_slugs as $s) {

		$this_schedule = $this_plan->getPaymentScheduleBySlug($s);
		foreach ($schedule_data as $sd) {
			# Put all schedule properties
			$schedule[$sd] = $this_schedule->$sd;
		}

		$schedule['available'] = TRUE;
		if ($plan['current'] && $account->getSchedule()->slug == $this_schedule->slug) {
			$schedule['current'] = TRUE;
		} else {
			$schedule['current'] = FALSE;
			# If user has enough on his balance, schedule could be activated
			if ($balance < $this_schedule->charge_amount) {
				$schedule['available'] = FALSE;
			}
		}

		if (!is_null($account->getNextSchedule()) && $account->getNextSchedule()->slug == $this_schedule->slug) {
			$schedule['chosen'] = TRUE;
		} else {
			$schedule['chosen'] = FALSE;
		}

		$plan['schedules'][] = $schedule;
	}
	$plans[] = $plan;
}

$template_data['plans'] = $plans;
$template_data['USERSROOTURL'] = UserConfig::$USERSROOTURL;
