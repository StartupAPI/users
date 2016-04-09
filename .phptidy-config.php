<?php
$project_files = array(
	"*.php",
	"*/*.php",
	"*/*/*.php",
	"*/*/*/*.php",
	"*/*/*/*/*.php",
	"*/*/*/*/*/*.php",
	"*/*/*/*/*/*/*.php"
);

$project_files_exclude_regexes = array(
	"|^cache/|",
	"|^docs/|",
	"|^tags/|",
	"|^oauth-php/|",
	"|^modules/facebook/facebook-php-sdk/|",
	"|^php-bootstrap/|",
	"|^dbupgrade/|",
	"|^phptidy/|",
	"|^twig/|"
);

$default_package = "StartupAPI";
$indent_char = "\t";
