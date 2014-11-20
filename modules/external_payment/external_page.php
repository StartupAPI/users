<?php

/**
 * @package StartupAPI
 * @subpackage Subscriptions
 */
/**
 * This page is an example of external resource, in *real* implementations
 * this page will actually be on other sites, e.g. PayPal or Amazon Payments
 */
require_once(dirname(dirname(__DIR__)) . '/global.php');

UserConfig::$IGNORE_CURRENT_ACCOUNT_PLAN_VERIFICATION = true;

/*
 * This is a sample of external page that can receive users from external link payment engine
 *
 * It's supposed to send users back when it's done so their plan and schedule can be changed and balance updated
 */
$template_info = StartupAPI::getTemplateInfo();
$template_info['QUERY_STRING'] = $_SERVER['QUERY_STRING'];

if (array_key_exists('engine', $_GET)) {
	$engine = PaymentEngine::getEngineBySlug($_GET['engine']);
	$template_info['engine']['name'] = $engine->getTitle();
	$template_info['engine']['logo'] = $engine->getLogo();
}

StartupAPI::$template->display('@startupapi/modules/external_payment/external_page.html.twig', $template_info);