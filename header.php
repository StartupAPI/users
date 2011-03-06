<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>Users</title>
<style>
#userbase_body {
	font-family: georgia, garamond, serif;
	margin: 0; background-color: #68818D;
}

#userbase_header {
	color: white;
	font-weight: bold; padding: 0.3em;
	border-bottom: 2px solid #51626b;
}

#userbase_header_icon {
	border: 0;
	vertical-align: bottom;
	margin: 0.4em 0.3em 0.2em 0.3em;
}

#userbase_navbox {
	color: white;
	float: right;
	margin-right: 0.5em;
}

#userbase_navbox a {
	color: #efe8e8;
}

#userbase_navbox a:hover {
	color: white;
}

#userbase_main {
	padding: 0.2em 0.5em;
	background-color: white;
}

#userbase_footer {
	height: 1em;
	border-top: 2px solid #8199a5;
}

#userbase_footerpad {
	height: 0.5em;
	background-color: white;
}

#userbase_poweredby {
	color: white;
	float: right;
	font-size: small;
	padding: 0.5em 1em;
}

#userbase_poweredby a {
	color: #efe8e8;
}

#userbase_poweredby a:hover {
	color: white;
}
</style>
</head>

<body id="userbase_body">
<div id="userbase_header">
<div id="userbase_navbox">
<?php include(dirname(__FILE__).'/navbox.php') ?>
</div>
<img src="<?php echo UserConfig::$USERSROOTURL ?>/images/header_icon.png" id="userbase_header_icon"/>
</div>
<div id="userbase_main">
