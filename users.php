<?php
/*
 * Users.php
 * 
 * This is a main file to be included into pap pages
*/
require_once(dirname(__FILE__).'/config.php');

require_once(dirname(__FILE__).'/tools.php');

require_once(dirname(__FILE__).'/User.php');
require_once(dirname(__FILE__).'/Plan.php');
require_once(dirname(__FILE__).'/Account.php');
require_once(dirname(__FILE__).'/Cohort.php');

require_once(dirname(__FILE__).'/CampaignTracker.php');

// do this on each page view (where user.php is included)
CampaignTracker::preserveReferer();
CampaignTracker::recordCampaignVariables();
User::updateReturnActivity(); // only if user is logged in
