<?php
namespace StartupAPI;

/*
 * Users.php
 *
 * This is a main file to be included at the very top of your application pages
*/
require_once(__DIR__.'/global.php');

User::updateReturnActivity(); // only if user is logged in
