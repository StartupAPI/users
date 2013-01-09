<?php
/*
 * Users.php
 *
 * This is a main file to be included into pap pages
*/
require_once(dirname(__FILE__).'/global.php');

require_once(dirname(__FILE__).'/classes/User.php');
require_once(dirname(__FILE__).'/classes/Plan.php');
require_once(dirname(__FILE__).'/classes/Account.php');
require_once(dirname(__FILE__).'/classes/Cohort.php');
require_once(dirname(__FILE__).'/classes/Feature.php');
require_once(dirname(__FILE__).'/classes/CampaignTracker.php');

// do this on each page view (where user.php is included)
CampaignTracker::preserveReferer();
CampaignTracker::recordCampaignVariables();
User::updateReturnActivity(); // only if user is logged in
