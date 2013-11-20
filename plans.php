<?php

require_once(__DIR__ . '/global.php');

// This page is only used when subscriptions are enabled, no need to have it otherwise
if (!UserConfig::$useSubscriptions) {
	header('Location: ' . UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

/*
 * This page is used for assigning plans event if no plan is currently set for account
 */
UserConfig::$IGNORE_CURRENT_ACCOUNT_PLAN_VERIFICATION = true;

$user = User::require_login();
$account = Account::getCurrentAccount($user);

/*
 * Only admin can view or change subscription plans
 */
if ($account->getUserRole($user) !== Account::ROLE_ADMIN) {
	header('Location: ' . UserConfig::$DEFAULTLOGOUTRETURN);
	exit;
}

/* ------------------- Handling form submission -------------------------------------- */
UserTools::preventCSRF();

session_start();

if (array_key_exists('plan', $_POST)) {
	$data = explode('.', $_REQUEST['plan']);

	if (!isset($data[1])) {
		$data[1] = NULL;
	}

	try {
		// Check if plan and schedule exists
		if (!($plan = Plan::getPlanBySlug($data[0])))
			throw new Exception("Unknown plan '" . $data[0] . '"');

		if (!is_null($data[1]) && !($schedule = $plan->getPaymentScheduleBySlug($data[1])))
			throw new Exception("Unknown schedule '" . $data[1] . "' for plan '" . $data[0] . "'");
	} catch (Exception $e) {
		$_SESSION['message'][] = $e->getMessage();
		header('Location: ' . $_SERVER['HTTP_REFERER']);
		exit;
	}

// Check balance
	if (!is_null($schedule) && $schedule->charge_amount > $account->getBalance()) {
		$_SESSION['message'][] = "Not enough funds to activate plan/schedule";
	} elseif ($account->getPlanSlug() != $data[0] ||
			(!is_null($account->getNextPlan()) && $account->getNextPlan()->slug != $data[0])) {
		// Not changing plan if requested plan is same as current or next

		if ($account->planChangeRequest($data[0], $data[1])) {
			if ($account->getPlanSlug() != $data[0]) {
				// Plan activation postponed
				$_SESSION['message'][] = "Your request to activate plan '" . $data[0] . '/' . $data[1] .
						"' accepted. Plan will be activated on the next charge according to your current schedule.";
			} else {
				// Plan activated immediately
				$_SESSION['message'][] = "Plan " . $data[0] . '/' . $data[1] . " activated.";
			}
		} else {
			$_SESSION['message'][] = "Error activating plan";
		}
	} elseif (!is_null($data[1]) && ($account->getScheduleSlug() != $data[1] ||
			(!is_null($account->getNextSchedule()) && $account->getNextSchedule()->slug != $data[1]))) {
		// Not changing schedule if requested schedule is same as current or next

		if ($account->scheduleChangeRequest($data[1])) {
			if ($account->getScheduleSlug() != $data[1]) {
				// Schedule change postponed
				$_SESSION['message'][] = "Your request to change payment schedule to '" . $data[1] .
						"' accepted. Schedule will be activated on the next charge according to your current schedule.";
			} else {
				// Schedule changed immediately
				$_SESSION['message'][] = "Payment schedule changed to " . $data[1];
			}
		} else {
			$_SESSION['message'][] = "Error changing schedule";
		}
	}

	header('Location: ' . $_SERVER['HTTP_REFERER']);
}
/* ------------------- / Handling form submission -------------------------------------- */

/* ------------------- Preparing data for template ------------------------------------- */

$template_data['account'] = array('name' => $account->getName());

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

$template_data['CSRF_NONCE'] = UserTools::$CSRF_NONCE;

$template_data['next_charge'] = $account->getNextCharge();

$balance = $account->getBalance();
$template_data['balance'] = $balance;
$plan_slugs = Plan::getPlanSlugs();

$plans = array();
foreach ($plan_slugs as $p) { # Iterate over all configured plans
	$this_plan = Plan::getPlanBySlug($p);
	$plan = array();
	foreach ($plan_data as $d) {
		# Put all plan properties
		$plan[$d] = $this_plan->$d;
	}

	$current_plan = $account->getPlan(); // can be FALSE
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

/* ------------------- / Preparing data for template -------------------------------------- */

require_once(UserConfig::$header);

StartupAPI::$template->display('plan/plans.html.twig', $template_data);

require_once(UserConfig::$footer);

