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
	"|^modules/facebook/php-sdk/|",
	"|^dbupgrade/|",
	"|^phptidy/|",
	"|^twig/|"
);

$default_package = "StartupAPI";
$indent_char = "\t";
