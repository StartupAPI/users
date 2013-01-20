<?php
/*
 * Users.php
 *
 * This is a main file to be included at the very top of your application pages
*/
require_once(__DIR__.'/global.php');

require_once(__DIR__.'/classes/User.php');
require_once(__DIR__.'/classes/Plan.php');
require_once(__DIR__.'/classes/Account.php');
require_once(__DIR__.'/classes/Cohort.php');
require_once(__DIR__.'/classes/Feature.php');
require_once(__DIR__.'/classes/CampaignTracker.php');

// do this on each page view (where user.php is included)
CampaignTracker::preserveReferer();
CampaignTracker::recordCampaignVariables();
User::updateReturnActivity(); // only if user is logged in
