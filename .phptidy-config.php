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

$project_files_excludes = array(
	"|^cache/|",
	"|^docs/|",
	"|^tags/|",
	"|^oauth-php/|",
	"|^modules/facebook/php-sdk/|",
	"|^dbupgrade/|",
	"|^phptidy/|",
	"|^smarty/|"
);

$default_package = "StartupAPI";
$indent_char = "\t";
