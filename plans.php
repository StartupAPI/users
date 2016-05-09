<?php
namespace StartupAPI;

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

$template_info = StartupAPI::getTemplateInfo();
$template_info['account_name'] = $account->getName();

if (!session_id()) {
	session_start();
}

if (array_key_exists('plan', $_POST)) {
	$data = explode('.', $_REQUEST['plan']);

	if (!isset($data[1])) {
		$data[1] = NULL;
		$data[2] = NULL;
	}

	$selected_plan_slug = $data[0];
	$selected_schedule_slug = $data[1];
	$selected_engine_slug = $data[2];

	$engine = null;
	$schedule = null;

	try {
		// Check if plan and schedule exists
		if (!($plan = Plan::getPlanBySlug($selected_plan_slug))) {
			throw new Exceptions\StartupAPIException("Unknown plan '" . $selected_plan_slug . '"');
		}

		if (!is_null($selected_schedule_slug) && !($schedule = $plan->getPaymentScheduleBySlug($selected_schedule_slug))) {
			throw new Exceptions\StartupAPIException("Unknown schedule '" . $selected_schedule_slug . "' for plan '" . $selected_plan_slug . "'");
		}

		if (!is_null($selected_engine_slug) && !($engine = PaymentEngine::getEngineBySlug($selected_engine_slug))) {
			throw new Exceptions\StartupAPIException("Unknown payment engine '" . $engine . "' for schedule schedule '" . $selected_schedule_slug . "' for plan '" . $selected_plan_slug . "'");
		}
	} catch (Exception $e) {
		$_SESSION['message'][] = $e->getMessage();
		header('Location: ' . UserConfig::$USERSROOTURL . '/plans.php');
		exit;
	}

	if (!$engine || $engine->requiresPrePayment()) {
		// Check balance
		if (!is_null($schedule) && $schedule->getChargeAmount() > $account->getBalance()) {
			$_SESSION['message'][] = "Not enough funds to activate plan/schedule";
		} elseif (
				$account->getPlanSlug() != $selected_plan_slug ||
				(!is_null($account->getNextPlan()) && $account->getNextPlan()->getSlug() != $selected_plan_slug)
		) {
			// Not changing plan if requestfed plan is same as current or next

			if ($account->planChangeRequest($selected_plan_slug, $selected_schedule_slug, $selected_engine_slug)) {
				if ($account->getPlanSlug() != $selected_plan_slug) {
					// Plan activation postponed
					$_SESSION['message'][] = "Your request to activate plan '" . $selected_plan_slug . '/' . $selected_schedule_slug .
							"' accepted. Plan will be activated on the next charge according to your current schedule.";
				} else {
					// Plan activated immediately
					$_SESSION['message'][] = "Plan " . $selected_plan_slug . '/' . $selected_schedule_slug . " activated.";
				}
			} else {
				$_SESSION['message'][] = "Error activating plan";
			}
		} elseif (!is_null($selected_schedule_slug) && ($account->getScheduleSlug() != $selected_schedule_slug ||
				(!is_null($account->getNextSchedule()) && $account->getNextSchedule()->getSlug() != $selected_schedule_slug))) {
			// Not changing schedule if requested schedule is same as current or next

			if ($account->scheduleChangeRequest($selected_schedule_slug, $selected_engine_slug)) {
				if ($account->getScheduleSlug() != $selected_schedule_slug) {
					// Schedule change postponed
					$_SESSION['message'][] = "Your request to change payment schedule to '" . $selected_schedule_slug .
							"' accepted. Schedule will be activated on the next charge according to your current schedule.";
				} else {
					// Schedule changed immediately
					$_SESSION['message'][] = "Payment schedule changed to " . $selected_schedule_slug;
				}
			} else {
				$_SESSION['message'][] = "Error changing schedule";
			}
		}
	} else {
		// now we need to redirect to engine's action URL (potentially external
		$action_url = $engine->getActionURL($plan, $schedule, $account);

		header('Location: ' . $action_url);
		exit;
	}

	header('Location: ' . UserConfig::$DEFAULTLOGINRETURN . '?upgraded');
	exit;
}
/* ------------------- / Handling form submission -------------------------------------- */

/* ------------------- Preparing data for template ------------------------------------- */

$template_info['account'] = array('name' => $account->getName());

if (isset($_SESSION['message'])) {
	$template_info['message'] = $_SESSION['message'];
	unset($_SESSION['message']);
	$fatal = isset($_SESSION['fatal']) ? $_SESSION['fatal'] : 0;
	unset($_SESSION['fatal']);
	if ($fatal) {
		$template_info['fatal'] = 1;
		return;
	}
}

if (!$account->isActive()) {
	$template_info['message'] = array('This account is not active. Please activate it first.');
	$template_info['fatal'] = 1;
	return;
}

$template_info['CSRF_NONCE'] = UserTools::$CSRF_NONCE;

$template_info['next_charge'] = $account->getNextCharge();

$balance = $account->getBalance();
$template_info['balance'] = $balance;
$plan_slugs = Plan::getPlanSlugs();

$current_plan = $account->getPlan(); // can be FALSE

$i = 0;
$base_plan_index = null;

$plans = array();
foreach ($plan_slugs as $plan_slug) { # Iterate over all configured plans
	$this_plan = Plan::getPlanBySlug($plan_slug);
	$plan = array();

	$plan['slug'] = $this_plan->getSlug();
	$plan['name'] = $this_plan->getName();
	$plan['description'] = $this_plan->getDescription();
	$plan['base_price'] = $this_plan->getBasePrice();
	$plan['base_period'] = $this_plan->getBasePeriod();
	$plan['details_url'] = $this_plan->getDetailsURL();
	$plan['downgrade_to'] = $this_plan->getDowngradeToPlan();
	$plan['grace_period'] = $this_plan->getGracePeriod();
	$plan['available'] = $this_plan->isAvailable();

	# Mark plan as current if slugs match
	if ($current_plan && $current_plan->getSlug() == $this_plan->getSlug()) {
		$plan['current'] = TRUE;
	} else {
		$plan['current'] = FALSE;
	}

	$schedule = array();
	$schedule_slugs = $this_plan->getPaymentScheduleSlugs(); # Iterate over all schedules of this plan

	if (!is_null($account->getNextPlan()) &&
			$account->getNextPlan()->getSlug() == $this_plan->getSlug()) {
		$plan['chosen'] = TRUE;
		$template_info['next_chosen'] = TRUE;
	} else {
		$plan['chosen'] = FALSE;
	}

	foreach ($schedule_slugs as $s) {

		$this_schedule = $this_plan->getPaymentScheduleBySlug($s);

		$schedule['slug'] = $this_schedule->getSlug();
		$schedule['name'] = $this_schedule->getName();
		$schedule['description'] = $this_schedule->getDescription();
		$schedule['charge_amount'] = $this_schedule->getChargeAmount();
		$schedule['charge_period'] = $this_schedule->getChargePeriod();

		$account_schedule = $account->getSchedule();

		if ($plan['current'] && !is_null($account_schedule) && $account_schedule->getSlug() == $this_schedule->getSlug()) {
			$schedule['current'] = TRUE;
		} else {
			$schedule['current'] = FALSE;
		}

		if ($plan['chosen'] && !is_null($account->getNextSchedule()) && $account->getNextSchedule()->getSlug() == $this_schedule->getSlug()) {
			$schedule['chosen'] = TRUE;
		} else {
			$schedule['chosen'] = FALSE;
		}

		$plan['schedules'][] = $schedule;
	}
	$plans[] = $plan;

	// Plan which is being upgraded
	if ($plan['current']) {
		$template_info['base_plan'] = $plan;
		$base_plan_index = $i;
	}

	if ($plan['chosen']) {
		$template_info['base_plan'] = $plan;
		$base_plan_index = $i;
	}

	$i++;
}

$template_info['plans'] = $plans;

if (array_key_exists('base_plan', $template_info)) {
	$template_info['plans'][$base_plan_index]['is_base_plan'] = true;
}

$display_template = '@startupapi/plan/plans.html.twig';

/*
 * If plan was selected, show schedule and payment engine selection UI
 */
if (array_key_exists('plan', $_GET)) {
	$selected_plan_slug = Plan::getPlanBySlug($_GET['plan']);

	if ($selected_plan_slug) {
		foreach ($template_info['plans'] as $plan) {
			if ($plan['slug'] == $selected_plan_slug->getSlug()) {
				$template_info['plan'] = $plan;
				$display_template = '@startupapi/plan/select_payment_method.html.twig';
				break;
			}
		}
	}
}

foreach (UserConfig::$payment_modules as $payment_engine) {
	$template_info['payment_engines'][] = array(
		'slug' => $payment_engine->getSlug(),
		'title' => $payment_engine->getTitle(),
		'button_label' => $payment_engine->getActionButtonLabel(),
		'requires_pre_payment' => $payment_engine->requiresPrePayment()
	);
}

/* ------------------- / Preparing data for template -------------------------------------- */

StartupAPI::$template->display($display_template, $template_info);
