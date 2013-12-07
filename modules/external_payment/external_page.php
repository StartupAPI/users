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

require_once(UserConfig::$header);
?>

<a class="btn btn-success btn-large" href="<?php echo UserConfig::$USERSROOTFULLURL ?>/modules/external_payment/callback.php?<?php echo htmlentities($_SERVER['QUERY_STRING'])?>&amp;paid=yes">Paid!</a>

<?php
require_once(UserConfig::$footer);

