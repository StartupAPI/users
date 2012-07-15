<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>Users</title>
<style>
#userbase_body {
	color: #666666;
	font-family: georgia, garamond, serif;
	margin: 0;
	background-color: #DCDCDC;
}

#userbase_header {
	padding: 0.5em;
	font-size: small;
	border-bottom: 1px solid #c6c6c6;
}

#userbase_header_icon {
	border: 0;
	vertical-align: bottom;
	margin: 0.4em 0.3em 0.2em 0.3em;
}

#userbase_navbox {
	color: #c6c6c6;
	float: right;
	margin-right: 0.5em;
}

#userbase_navbox a {
	color: #666666;
	text-shadow: #EEE 0px 1px 0px;
	text-decoration: none;
}

#userbase_navbox a:hover {
	color: #69818d;
}

#userbase_main {
	padding: 0.2em 0.5em;
	background-color: white;
}

#userbase_footer {
	height: 1em;
	border-top: 1px solid #c6c6c6;
}

#userbase_footerpad {
	height: 1em;
	background-color: white;
}

#userbase_poweredby {
	color: #666;
	float: right;
	font-size: small;
	padding: 2em;
}

#userbase_poweredby a {
	color: #efe8e8;
}

#userbase_poweredby a:hover {
	color: white;
}

#userbase_adminmenu {
	padding: 1em;
	color: black;
	font-size: larger;
}

#userbase_adminmenu a {
	color: #888;
	text-decoration: none;
}

#userbase_adminmenu a:hover {
	color: #69818d;
}
</style>
</head>

<body id="userbase_body">
<div id="userbase_header">
<div id="userbase_navbox">
<?php include(dirname(__FILE__).'/navbox.php') ?>
</div>
<a href="<?php echo UserConfig::$SITEROOTURL ?>"><img src="<?php echo UserConfig::$USERSROOTURL ?>/images/header_icon.png" id="userbase_header_icon" title="Powered by Startup API"/></a>
</div>
<div id="userbase_main">
