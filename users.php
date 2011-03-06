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

require_once(dirname(__FILE__).'/CampaignTracker.php');

CampaignTracker::preserveReferer();
CampaignTracker::recordCampaignVariables();
