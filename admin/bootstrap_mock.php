<?php
require_once(dirname(__FILE__) . '/admin.php');
?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link href="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap/css/bootstrap.css" rel="stylesheet">
		<link href="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
		<script src="http://code.jquery.com/jquery-latest.js"></script>
		<script src="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap/js/bootstrap.min.js"></script>
		<style>

			.startupapi-sidebar.affix {
				top: 4em;
			}

			body {
				padding-top: 40px;
			}

			.logo {
				margin-right: 0.5em;
			}

			.footer {
				padding: 70px 0;
				margin-top: 70px;
				border-top: 1px solid #E5E5E5;
				background-color: whiteSmoke;
			}
		</style>
	</head>
	<body>
		<div class="navbar">
			<div class="navbar-inner navbar-fixed-top">
				<span class="brand"><a href="<?php echo UserConfig::$USERSROOTURL ?>"><img class="logo" src="<?php echo UserConfig::$USERSROOTURL ?>/images/header_icon.png"/><?php echo is_null(UserConfig::$appName) ? 'Startup API' : UserConfig::$appName; ?></a></span>
				<ul class="nav">
					<li class="active"><a href="#">Home</a></li>
					<li><a href="#">Users</a></li>
					<li><a href="#">Settings</a></li>
				</ul>
				<ul class="nav pull-right">
					<li class="navbar-text">Sergey Chernyshev</li>
					<li><a href="#">Logout</a></li>
				</ul>
			</div>
		</div>
		<div class="container-fluid">
			<div class="row-fluid">
				<div class="span3">
					<div class="well sidebar-nav startupapi-sidebar">
						<ul class="nav nav-list">
							<li class="active"><a href="#"><i class="icon-home"></i> Home</a></li>
							<li class="divider"></li>

							<li class="nav-header">Dashboards</li>
							<li><a href="#"><l class="icon-signal"></l>Basic Metrics</a></li>
							<li class="divider"></li>

							<li class="nav-header">Users</li>
							<li><a href="#"><l class="icon-user"></l>Registered Users</a></li>
							<li><a href="#"><i class="icon-signal"></i>Activity</a></li>
							<li><a href="#"><i class="icon-th"></i>Cohort Analysis</a></li>
							<li><a href="#"><i class="icon-th-large"></i>Registrations by Module</a></li>
							<li class="disabled" title="Coming soon"><a href="#"><i class="icon-minus"></i>Subscriptions</a></li>
							<li class="divider"></li>

							<li class="nav-header">Promotion</li>
							<li class="disabled" title="Coming soon"><a href="#"><i class="icon-random"></i>Channels</a></li>
							<li class="disabled" title="Coming soon"><a href="#"><i class="icon-comment"></i>Campaigns</a></li>
							<li class="divider"></li>

							<li class="nav-header">Settings</li>
							<li><a href="#"><i class="icon-check"></i>Features</a></li>
							<li class="disabled" title="Coming soon"><a href="#"><i class="icon-cog"></i>Configuration</a></li>
							<li class="disabled" title="Coming soon"><a href="#"><i class="icon-list-alt"></i>Templates</a></li>
						</ul>
					</div>
					<!--Sidebar content-->
				</div>

				<div class="span9">

					<div class="alert alert-block fade in">
						<button type="button" class="close" data-dismiss="alert">×</button>
						<button class="btn btn-primary btn-large pull-right">Upgrade</button>
						<h4>Version 1.23 of Startup API is available, you should upgrade!</h4>
						<a href="">Release notes for v1.23</a>
					</div>

					<ul class="breadcrumb">
						<li><a href="#">Users</a> <span class="divider">/</span></li>
						<li class="active">Registered Users</li>
					</ul>

				</div>

				<div class="span5">

					<div class="hero-unit">
						<h1>Heading</h1>
						<p>Tagline</p>
						<p>
							<a class="btn btn-primary btn-large">
								Learn more
							</a>
						</p>
					</div>


					<!--Middle column content-->

					<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. In nisl erat, placerat nec facilisis ut, venenatis at magna. Pellentesque ac ultricies nulla. Sed ornare dictum ultrices. Aenean sagittis, sem at blandit adipiscing, mi velit placerat purus, vitae molestie libero mi vitae eros. Nunc a vestibulum eros. Aliquam at nunc in lacus porttitor tempus nec ac velit. Donec in sem auctor est posuere euismod nec id metus.</p>

					<p>Sed at nisl vel urna convallis ullamcorper sodales eu felis. Curabitur eu augue vel libero elementum auctor. Aenean consequat, nisi sed porta aliquam, nisl ipsum lacinia nisi, id molestie lacus odio vitae augue. Pellentesque tempor convallis odio sed consequat. Morbi vestibulum dapibus nisl in iaculis. Proin rutrum, diam sit amet consequat scelerisque, nisi nunc congue enim, sit amet pharetra arcu magna quis ligula. Nulla facilisi. Duis nibh enim, tempus non imperdiet in, fermentum a orci. Etiam in leo erat, ac ultricies odio. Suspendisse potenti. Vestibulum eu justo eget enim interdum condimentum.</p>

					<p>Donec nunc neque, cursus quis ultrices vitae, semper quis augue. Aenean fringilla, dui et imperdiet dapibus, purus ante gravida justo, eu dictum eros nulla et nisl. Phasellus lorem neque, varius id faucibus at, mattis at erat. Mauris metus augue, fringilla in dapibus eu, porttitor et orci. Ut ultricies, lacus ac interdum venenatis, massa magna imperdiet nunc, eget egestas tellus arcu et purus. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Suspendisse elit augue, molestie eget lobortis et, accumsan vel ante. Quisque tincidunt, odio at semper sodales, odio mauris suscipit lacus, id fermentum nisi eros ac elit. Vivamus id aliquet nisi. Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>

					<p>Donec justo tortor, pulvinar sit amet adipiscing quis, ultrices sit amet risus. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. In rhoncus euismod hendrerit. Cras feugiat dolor vitae neque posuere a tincidunt diam mollis. Cras in fringilla orci. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Nunc nec velit sem, ut varius ipsum. Aliquam laoreet, tellus ac adipiscing tincidunt, arcu nisi fringilla lectus, ut vulputate ligula metus non nulla. Donec lobortis adipiscing tincidunt. Proin et odio et ipsum tincidunt fringilla.</p>

					<p>Suspendisse potenti. Vestibulum pharetra eros et ante faucibus volutpat. Quisque congue nunc at tortor aliquam bibendum. Sed venenatis arcu nec nulla consequat sit amet pharetra sapien dictum. Pellentesque id nunc nisi. Mauris luctus feugiat dui eget pulvinar. Nunc ullamcorper ante sit amet nisi vestibulum a pretium risus molestie. Sed quam mauris, interdum ut sagittis eget, vulputate id purus. Vivamus adipiscing elit et tellus pellentesque non sagittis sapien eleifend. Duis accumsan risus quis eros scelerisque porta. In eros ligula, ullamcorper nec venenatis et, imperdiet non nunc. Nunc et viverra metus. Donec elementum enim sed tortor tincidunt sodales. Fusce in orci nunc.</p>

					<p>Nulla quis ante placerat mi dictum consequat et quis lacus. Nullam nunc leo, venenatis ut placerat non, vestibulum ut felis. Nam massa dolor, ultricies eu mattis vitae, rutrum sit amet arcu. Vestibulum at tellus urna, at volutpat lorem. Donec vel mi ac enim congue dignissim non nec ipsum. Vestibulum vitae luctus lectus. Fusce odio libero, aliquet eget venenatis vitae, posuere ac turpis.</p>

					<p>Vestibulum arcu orci, vulputate quis aliquet at, hendrerit venenatis lacus. Etiam in nulla justo, non molestie massa. Proin consequat augue quis orci euismod luctus. Aliquam non enim felis. Sed in tortor eget justo ultricies volutpat ut vel nisi. Integer vel magna orci, sed varius nunc. Praesent malesuada nisl eu lorem consectetur sed porttitor felis interdum. Pellentesque vel justo quam. Etiam at purus libero. Morbi in leo lectus, non tincidunt nisl. Sed nisi urna, eleifend eget blandit a, posuere pellentesque sapien. Nulla suscipit consectetur auctor. Duis molestie quam sed felis laoreet et varius urna rutrum. Curabitur at mattis quam.</p>

					<p>Etiam ipsum eros, suscipit vel cursus a, porttitor id sem. Donec non ante vitae felis pellentesque aliquet. Nullam eget blandit magna. Nam et lobortis magna. Etiam ac aliquet augue. Proin varius dictum risus in consequat. Nam posuere metus eu tortor auctor non pharetra arcu cursus. Suspendisse eleifend, dui nec venenatis congue, nunc mauris laoreet magna, a mollis massa est varius lacus. Nam placerat vulputate est non tristique. Morbi pellentesque, magna gravida posuere elementum, purus nunc lobortis ligula, sed pulvinar nibh nisi et sem. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Mauris tempor est dolor. Donec vel sollicitudin lacus. Integer eu sapien turpis, quis egestas diam.</p>

					<p>Curabitur sit amet augue lorem, vitae tincidunt urna. Nunc ut purus metus. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Aenean a porttitor ante. Etiam at odio pulvinar arcu varius pretium non at elit. Nullam et nibh ac risus sollicitudin ultrices quis in diam. Quisque ut purus mattis massa interdum pharetra non sed est. Fusce id enim ac diam ullamcorper placerat. Nullam ac tincidunt neque. Aenean sed magna ante.</p>

					<p>Etiam egestas purus sed urna pharetra accumsan. Aenean lobortis, orci ut pulvinar venenatis, erat dolor lobortis erat, eget sodales nunc diam et sem. Quisque in elementum nunc. Ut eu velit consequat odio viverra tincidunt quis in dui. Nunc egestas, nisi vel bibendum malesuada, ante felis consequat mi, a egestas felis turpis a arcu. Donec odio elit, tincidunt ac dictum at, porta sed libero. Praesent cursus vehicula neque, varius viverra mi condimentum bibendum. Donec volutpat porta lacus, id pellentesque libero semper vel. Proin vulputate posuere orci, non rutrum quam congue in. Nullam urna enim, rutrum eu aliquet eget, volutpat ut ante. Phasellus purus nulla, molestie quis semper nec, interdum id nunc. Nunc ornare diam at urna pharetra facilisis. Donec enim diam, commodo nec euismod in, venenatis eget ipsum. Vestibulum mattis ultricies blandit. Curabitur quis nibh ut urna faucibus vulputate sit amet dapibus dolor. Ut a turpis id urna feugiat varius quis et risus.</p>

					<p>Cras id nibh tortor, gravida tristique nisi. Donec ornare tellus vel lacus auctor ultricies. Curabitur at neque suscipit magna tristique mollis non sed elit. Aliquam eu lectus eu tortor mattis consequat. Donec pellentesque, erat at ultrices pharetra, nulla neque convallis neque, eget bibendum sem arcu id lacus. Sed a ligula ac odio ullamcorper bibendum. Donec in tellus massa, ut porta nisl. In dictum sagittis lorem at hendrerit. Nunc nec dolor neque, non vulputate est. Nam egestas odio ut nisl viverra id scelerisque felis bibendum. Fusce sit amet quam lorem, eget rutrum justo. In hendrerit, lacus sodales tristique feugiat, nulla dolor volutpat ipsum, placerat eleifend mauris risus in ligula. Sed lectus arcu, pellentesque at eleifend eget, bibendum sit amet sapien.</p>

					<p>Curabitur fringilla convallis risus sit amet porttitor. Donec tempor dapibus congue. Sed at massa orci. Cras quam mauris, adipiscing a dapibus sit amet, viverra id felis. Praesent a massa nisi, sed aliquet lorem. Vivamus sagittis leo a urna consectetur vitae euismod sapien iaculis. Praesent quis nibh quis lorem venenatis hendrerit. Nullam tempor condimentum felis, eget convallis nibh aliquam egestas. Nam ac ante ac mi bibendum elementum eget nec sapien. Curabitur sed felis lectus, vel facilisis eros. Suspendisse vulputate, est nec fermentum convallis, sapien enim aliquet mauris, eu tempus ipsum neque at dui. Aenean nisi ipsum, tincidunt ac vestibulum ac, ullamcorper quis ante. Sed suscipit lacus et lacus ultricies non eleifend elit tincidunt. Nulla quis dui quam, eget vulputate quam. Ut porttitor est a tortor viverra nec molestie arcu convallis. Nulla facilisi.</p>

					<p>Vestibulum quis ligula dolor. Aliquam erat volutpat. Quisque in risus tellus, in consectetur dolor. Sed viverra quam eget ligula lobortis imperdiet sit amet at ipsum. Donec suscipit iaculis placerat. Vivamus porttitor, lacus et porttitor placerat, erat quam rhoncus diam, in consequat magna augue sit amet lectus. Quisque vestibulum hendrerit felis vel tempus. Aenean non hendrerit nunc. Aliquam erat volutpat. Cras ante nibh, viverra bibendum porttitor sed, elementum eu lorem. Nullam iaculis vulputate nisi. Curabitur in nisl quam. Quisque laoreet sapien ut velit tincidunt aliquet condimentum felis mattis. Praesent dictum condimentum rutrum. Praesent vel lacus et elit scelerisque suscipit eu et odio.</p>

					<p>Etiam pharetra sapien ut ante feugiat porta. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Mauris sit amet lacus quam, eu pharetra nulla. Vivamus faucibus nisl vel sem cursus malesuada. Cras iaculis dignissim ultrices. Fusce id velit et justo gravida tempus. Morbi lorem metus, posuere ut convallis quis, fringilla ut massa. Duis dictum, neque in malesuada tristique, nisi risus suscipit mauris, in pellentesque leo nisi nec purus.</p>

					<p>Donec ac leo vel erat mattis posuere in non nulla. Nunc vitae rhoncus ipsum. Sed tempus lectus lacinia nulla mattis laoreet. Proin bibendum quam ornare ante condimentum interdum quis ac libero. Sed elementum urna ut erat sagittis vel porttitor lectus facilisis. Cras augue turpis, porta quis pulvinar sit amet, fringilla ut tortor. Aliquam erat volutpat. Pellentesque magna eros, consequat sed blandit sed, vulputate et felis. Nullam eget ipsum arcu, at congue mi. Morbi hendrerit malesuada felis quis vestibulum.</p>


				</div>
				<div class="span4">
					<!--Body content-->

					<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. In nisl erat, placerat nec facilisis ut, venenatis at magna. Pellentesque ac ultricies nulla. Sed ornare dictum ultrices. Aenean sagittis, sem at blandit adipiscing, mi velit placerat purus, vitae molestie libero mi vitae eros. Nunc a vestibulum eros. Aliquam at nunc in lacus porttitor tempus nec ac velit. Donec in sem auctor est posuere euismod nec id metus.</p>

					<p>Sed at nisl vel urna convallis ullamcorper sodales eu felis. Curabitur eu augue vel libero elementum auctor. Aenean consequat, nisi sed porta aliquam, nisl ipsum lacinia nisi, id molestie lacus odio vitae augue. Pellentesque tempor convallis odio sed consequat. Morbi vestibulum dapibus nisl in iaculis. Proin rutrum, diam sit amet consequat scelerisque, nisi nunc congue enim, sit amet pharetra arcu magna quis ligula. Nulla facilisi. Duis nibh enim, tempus non imperdiet in, fermentum a orci. Etiam in leo erat, ac ultricies odio. Suspendisse potenti. Vestibulum eu justo eget enim interdum condimentum.</p>

					<p>Donec nunc neque, cursus quis ultrices vitae, semper quis augue. Aenean fringilla, dui et imperdiet dapibus, purus ante gravida justo, eu dictum eros nulla et nisl. Phasellus lorem neque, varius id faucibus at, mattis at erat. Mauris metus augue, fringilla in dapibus eu, porttitor et orci. Ut ultricies, lacus ac interdum venenatis, massa magna imperdiet nunc, eget egestas tellus arcu et purus. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Suspendisse elit augue, molestie eget lobortis et, accumsan vel ante. Quisque tincidunt, odio at semper sodales, odio mauris suscipit lacus, id fermentum nisi eros ac elit. Vivamus id aliquet nisi. Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>

					<p>Donec justo tortor, pulvinar sit amet adipiscing quis, ultrices sit amet risus. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. In rhoncus euismod hendrerit. Cras feugiat dolor vitae neque posuere a tincidunt diam mollis. Cras in fringilla orci. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Nunc nec velit sem, ut varius ipsum. Aliquam laoreet, tellus ac adipiscing tincidunt, arcu nisi fringilla lectus, ut vulputate ligula metus non nulla. Donec lobortis adipiscing tincidunt. Proin et odio et ipsum tincidunt fringilla.</p>

					<p>Suspendisse potenti. Vestibulum pharetra eros et ante faucibus volutpat. Quisque congue nunc at tortor aliquam bibendum. Sed venenatis arcu nec nulla consequat sit amet pharetra sapien dictum. Pellentesque id nunc nisi. Mauris luctus feugiat dui eget pulvinar. Nunc ullamcorper ante sit amet nisi vestibulum a pretium risus molestie. Sed quam mauris, interdum ut sagittis eget, vulputate id purus. Vivamus adipiscing elit et tellus pellentesque non sagittis sapien eleifend. Duis accumsan risus quis eros scelerisque porta. In eros ligula, ullamcorper nec venenatis et, imperdiet non nunc. Nunc et viverra metus. Donec elementum enim sed tortor tincidunt sodales. Fusce in orci nunc.</p>

					<p>Nulla quis ante placerat mi dictum consequat et quis lacus. Nullam nunc leo, venenatis ut placerat non, vestibulum ut felis. Nam massa dolor, ultricies eu mattis vitae, rutrum sit amet arcu. Vestibulum at tellus urna, at volutpat lorem. Donec vel mi ac enim congue dignissim non nec ipsum. Vestibulum vitae luctus lectus. Fusce odio libero, aliquet eget venenatis vitae, posuere ac turpis.</p>

					<p>Vestibulum arcu orci, vulputate quis aliquet at, hendrerit venenatis lacus. Etiam in nulla justo, non molestie massa. Proin consequat augue quis orci euismod luctus. Aliquam non enim felis. Sed in tortor eget justo ultricies volutpat ut vel nisi. Integer vel magna orci, sed varius nunc. Praesent malesuada nisl eu lorem consectetur sed porttitor felis interdum. Pellentesque vel justo quam. Etiam at purus libero. Morbi in leo lectus, non tincidunt nisl. Sed nisi urna, eleifend eget blandit a, posuere pellentesque sapien. Nulla suscipit consectetur auctor. Duis molestie quam sed felis laoreet et varius urna rutrum. Curabitur at mattis quam.</p>

					<p>Etiam ipsum eros, suscipit vel cursus a, porttitor id sem. Donec non ante vitae felis pellentesque aliquet. Nullam eget blandit magna. Nam et lobortis magna. Etiam ac aliquet augue. Proin varius dictum risus in consequat. Nam posuere metus eu tortor auctor non pharetra arcu cursus. Suspendisse eleifend, dui nec venenatis congue, nunc mauris laoreet magna, a mollis massa est varius lacus. Nam placerat vulputate est non tristique. Morbi pellentesque, magna gravida posuere elementum, purus nunc lobortis ligula, sed pulvinar nibh nisi et sem. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Mauris tempor est dolor. Donec vel sollicitudin lacus. Integer eu sapien turpis, quis egestas diam.</p>

					<p>Curabitur sit amet augue lorem, vitae tincidunt urna. Nunc ut purus metus. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Aenean a porttitor ante. Etiam at odio pulvinar arcu varius pretium non at elit. Nullam et nibh ac risus sollicitudin ultrices quis in diam. Quisque ut purus mattis massa interdum pharetra non sed est. Fusce id enim ac diam ullamcorper placerat. Nullam ac tincidunt neque. Aenean sed magna ante.</p>

					<p>Etiam egestas purus sed urna pharetra accumsan. Aenean lobortis, orci ut pulvinar venenatis, erat dolor lobortis erat, eget sodales nunc diam et sem. Quisque in elementum nunc. Ut eu velit consequat odio viverra tincidunt quis in dui. Nunc egestas, nisi vel bibendum malesuada, ante felis consequat mi, a egestas felis turpis a arcu. Donec odio elit, tincidunt ac dictum at, porta sed libero. Praesent cursus vehicula neque, varius viverra mi condimentum bibendum. Donec volutpat porta lacus, id pellentesque libero semper vel. Proin vulputate posuere orci, non rutrum quam congue in. Nullam urna enim, rutrum eu aliquet eget, volutpat ut ante. Phasellus purus nulla, molestie quis semper nec, interdum id nunc. Nunc ornare diam at urna pharetra facilisis. Donec enim diam, commodo nec euismod in, venenatis eget ipsum. Vestibulum mattis ultricies blandit. Curabitur quis nibh ut urna faucibus vulputate sit amet dapibus dolor. Ut a turpis id urna feugiat varius quis et risus.</p>

					<p>Cras id nibh tortor, gravida tristique nisi. Donec ornare tellus vel lacus auctor ultricies. Curabitur at neque suscipit magna tristique mollis non sed elit. Aliquam eu lectus eu tortor mattis consequat. Donec pellentesque, erat at ultrices pharetra, nulla neque convallis neque, eget bibendum sem arcu id lacus. Sed a ligula ac odio ullamcorper bibendum. Donec in tellus massa, ut porta nisl. In dictum sagittis lorem at hendrerit. Nunc nec dolor neque, non vulputate est. Nam egestas odio ut nisl viverra id scelerisque felis bibendum. Fusce sit amet quam lorem, eget rutrum justo. In hendrerit, lacus sodales tristique feugiat, nulla dolor volutpat ipsum, placerat eleifend mauris risus in ligula. Sed lectus arcu, pellentesque at eleifend eget, bibendum sit amet sapien.</p>

					<p>Curabitur fringilla convallis risus sit amet porttitor. Donec tempor dapibus congue. Sed at massa orci. Cras quam mauris, adipiscing a dapibus sit amet, viverra id felis. Praesent a massa nisi, sed aliquet lorem. Vivamus sagittis leo a urna consectetur vitae euismod sapien iaculis. Praesent quis nibh quis lorem venenatis hendrerit. Nullam tempor condimentum felis, eget convallis nibh aliquam egestas. Nam ac ante ac mi bibendum elementum eget nec sapien. Curabitur sed felis lectus, vel facilisis eros. Suspendisse vulputate, est nec fermentum convallis, sapien enim aliquet mauris, eu tempus ipsum neque at dui. Aenean nisi ipsum, tincidunt ac vestibulum ac, ullamcorper quis ante. Sed suscipit lacus et lacus ultricies non eleifend elit tincidunt. Nulla quis dui quam, eget vulputate quam. Ut porttitor est a tortor viverra nec molestie arcu convallis. Nulla facilisi.</p>

					<p>Vestibulum quis ligula dolor. Aliquam erat volutpat. Quisque in risus tellus, in consectetur dolor. Sed viverra quam eget ligula lobortis imperdiet sit amet at ipsum. Donec suscipit iaculis placerat. Vivamus porttitor, lacus et porttitor placerat, erat quam rhoncus diam, in consequat magna augue sit amet lectus. Quisque vestibulum hendrerit felis vel tempus. Aenean non hendrerit nunc. Aliquam erat volutpat. Cras ante nibh, viverra bibendum porttitor sed, elementum eu lorem. Nullam iaculis vulputate nisi. Curabitur in nisl quam. Quisque laoreet sapien ut velit tincidunt aliquet condimentum felis mattis. Praesent dictum condimentum rutrum. Praesent vel lacus et elit scelerisque suscipit eu et odio.</p>

					<p>Etiam pharetra sapien ut ante feugiat porta. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Mauris sit amet lacus quam, eu pharetra nulla. Vivamus faucibus nisl vel sem cursus malesuada. Cras iaculis dignissim ultrices. Fusce id velit et justo gravida tempus. Morbi lorem metus, posuere ut convallis quis, fringilla ut massa. Duis dictum, neque in malesuada tristique, nisi risus suscipit mauris, in pellentesque leo nisi nec purus.</p>

					<p>Donec ac leo vel erat mattis posuere in non nulla. Nunc vitae rhoncus ipsum. Sed tempus lectus lacinia nulla mattis laoreet. Proin bibendum quam ornare ante condimentum interdum quis ac libero. Sed elementum urna ut erat sagittis vel porttitor lectus facilisis. Cras augue turpis, porta quis pulvinar sit amet, fringilla ut tortor. Aliquam erat volutpat. Pellentesque magna eros, consequat sed blandit sed, vulputate et felis. Nullam eget ipsum arcu, at congue mi. Morbi hendrerit malesuada felis quis vestibulum.</p>


				</div>

				<!--
				<div class="span9">
					<ul class="breadcrumb">
						<li><a href="#">Users</a> <span class="divider">/</span></li>
						<li class="active">Registered Users</li>
					</ul>

					<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. In nisl erat, placerat nec facilisis ut, venenatis at magna. Pellentesque ac ultricies nulla. Sed ornare dictum ultrices. Aenean sagittis, sem at blandit adipiscing, mi velit placerat purus, vitae molestie libero mi vitae eros. Nunc a vestibulum eros. Aliquam at nunc in lacus porttitor tempus nec ac velit. Donec in sem auctor est posuere euismod nec id metus.</p>

					<p>Sed at nisl vel urna convallis ullamcorper sodales eu felis. Curabitur eu augue vel libero elementum auctor. Aenean consequat, nisi sed porta aliquam, nisl ipsum lacinia nisi, id molestie lacus odio vitae augue. Pellentesque tempor convallis odio sed consequat. Morbi vestibulum dapibus nisl in iaculis. Proin rutrum, diam sit amet consequat scelerisque, nisi nunc congue enim, sit amet pharetra arcu magna quis ligula. Nulla facilisi. Duis nibh enim, tempus non imperdiet in, fermentum a orci. Etiam in leo erat, ac ultricies odio. Suspendisse potenti. Vestibulum eu justo eget enim interdum condimentum.</p>

					<p>Donec nunc neque, cursus quis ultrices vitae, semper quis augue. Aenean fringilla, dui et imperdiet dapibus, purus ante gravida justo, eu dictum eros nulla et nisl. Phasellus lorem neque, varius id faucibus at, mattis at erat. Mauris metus augue, fringilla in dapibus eu, porttitor et orci. Ut ultricies, lacus ac interdum venenatis, massa magna imperdiet nunc, eget egestas tellus arcu et purus. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Suspendisse elit augue, molestie eget lobortis et, accumsan vel ante. Quisque tincidunt, odio at semper sodales, odio mauris suscipit lacus, id fermentum nisi eros ac elit. Vivamus id aliquet nisi. Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>

					<p>Donec justo tortor, pulvinar sit amet adipiscing quis, ultrices sit amet risus. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. In rhoncus euismod hendrerit. Cras feugiat dolor vitae neque posuere a tincidunt diam mollis. Cras in fringilla orci. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Nunc nec velit sem, ut varius ipsum. Aliquam laoreet, tellus ac adipiscing tincidunt, arcu nisi fringilla lectus, ut vulputate ligula metus non nulla. Donec lobortis adipiscing tincidunt. Proin et odio et ipsum tincidunt fringilla.</p>

					<p>Suspendisse potenti. Vestibulum pharetra eros et ante faucibus volutpat. Quisque congue nunc at tortor aliquam bibendum. Sed venenatis arcu nec nulla consequat sit amet pharetra sapien dictum. Pellentesque id nunc nisi. Mauris luctus feugiat dui eget pulvinar. Nunc ullamcorper ante sit amet nisi vestibulum a pretium risus molestie. Sed quam mauris, interdum ut sagittis eget, vulputate id purus. Vivamus adipiscing elit et tellus pellentesque non sagittis sapien eleifend. Duis accumsan risus quis eros scelerisque porta. In eros ligula, ullamcorper nec venenatis et, imperdiet non nunc. Nunc et viverra metus. Donec elementum enim sed tortor tincidunt sodales. Fusce in orci nunc.</p>

					<p>Nulla quis ante placerat mi dictum consequat et quis lacus. Nullam nunc leo, venenatis ut placerat non, vestibulum ut felis. Nam massa dolor, ultricies eu mattis vitae, rutrum sit amet arcu. Vestibulum at tellus urna, at volutpat lorem. Donec vel mi ac enim congue dignissim non nec ipsum. Vestibulum vitae luctus lectus. Fusce odio libero, aliquet eget venenatis vitae, posuere ac turpis.</p>

					<p>Vestibulum arcu orci, vulputate quis aliquet at, hendrerit venenatis lacus. Etiam in nulla justo, non molestie massa. Proin consequat augue quis orci euismod luctus. Aliquam non enim felis. Sed in tortor eget justo ultricies volutpat ut vel nisi. Integer vel magna orci, sed varius nunc. Praesent malesuada nisl eu lorem consectetur sed porttitor felis interdum. Pellentesque vel justo quam. Etiam at purus libero. Morbi in leo lectus, non tincidunt nisl. Sed nisi urna, eleifend eget blandit a, posuere pellentesque sapien. Nulla suscipit consectetur auctor. Duis molestie quam sed felis laoreet et varius urna rutrum. Curabitur at mattis quam.</p>

					<p>Etiam ipsum eros, suscipit vel cursus a, porttitor id sem. Donec non ante vitae felis pellentesque aliquet. Nullam eget blandit magna. Nam et lobortis magna. Etiam ac aliquet augue. Proin varius dictum risus in consequat. Nam posuere metus eu tortor auctor non pharetra arcu cursus. Suspendisse eleifend, dui nec venenatis congue, nunc mauris laoreet magna, a mollis massa est varius lacus. Nam placerat vulputate est non tristique. Morbi pellentesque, magna gravida posuere elementum, purus nunc lobortis ligula, sed pulvinar nibh nisi et sem. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Mauris tempor est dolor. Donec vel sollicitudin lacus. Integer eu sapien turpis, quis egestas diam.</p>

					<p>Curabitur sit amet augue lorem, vitae tincidunt urna. Nunc ut purus metus. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Aenean a porttitor ante. Etiam at odio pulvinar arcu varius pretium non at elit. Nullam et nibh ac risus sollicitudin ultrices quis in diam. Quisque ut purus mattis massa interdum pharetra non sed est. Fusce id enim ac diam ullamcorper placerat. Nullam ac tincidunt neque. Aenean sed magna ante.</p>

					<p>Etiam egestas purus sed urna pharetra accumsan. Aenean lobortis, orci ut pulvinar venenatis, erat dolor lobortis erat, eget sodales nunc diam et sem. Quisque in elementum nunc. Ut eu velit consequat odio viverra tincidunt quis in dui. Nunc egestas, nisi vel bibendum malesuada, ante felis consequat mi, a egestas felis turpis a arcu. Donec odio elit, tincidunt ac dictum at, porta sed libero. Praesent cursus vehicula neque, varius viverra mi condimentum bibendum. Donec volutpat porta lacus, id pellentesque libero semper vel. Proin vulputate posuere orci, non rutrum quam congue in. Nullam urna enim, rutrum eu aliquet eget, volutpat ut ante. Phasellus purus nulla, molestie quis semper nec, interdum id nunc. Nunc ornare diam at urna pharetra facilisis. Donec enim diam, commodo nec euismod in, venenatis eget ipsum. Vestibulum mattis ultricies blandit. Curabitur quis nibh ut urna faucibus vulputate sit amet dapibus dolor. Ut a turpis id urna feugiat varius quis et risus.</p>

					<p>Cras id nibh tortor, gravida tristique nisi. Donec ornare tellus vel lacus auctor ultricies. Curabitur at neque suscipit magna tristique mollis non sed elit. Aliquam eu lectus eu tortor mattis consequat. Donec pellentesque, erat at ultrices pharetra, nulla neque convallis neque, eget bibendum sem arcu id lacus. Sed a ligula ac odio ullamcorper bibendum. Donec in tellus massa, ut porta nisl. In dictum sagittis lorem at hendrerit. Nunc nec dolor neque, non vulputate est. Nam egestas odio ut nisl viverra id scelerisque felis bibendum. Fusce sit amet quam lorem, eget rutrum justo. In hendrerit, lacus sodales tristique feugiat, nulla dolor volutpat ipsum, placerat eleifend mauris risus in ligula. Sed lectus arcu, pellentesque at eleifend eget, bibendum sit amet sapien.</p>

					<p>Curabitur fringilla convallis risus sit amet porttitor. Donec tempor dapibus congue. Sed at massa orci. Cras quam mauris, adipiscing a dapibus sit amet, viverra id felis. Praesent a massa nisi, sed aliquet lorem. Vivamus sagittis leo a urna consectetur vitae euismod sapien iaculis. Praesent quis nibh quis lorem venenatis hendrerit. Nullam tempor condimentum felis, eget convallis nibh aliquam egestas. Nam ac ante ac mi bibendum elementum eget nec sapien. Curabitur sed felis lectus, vel facilisis eros. Suspendisse vulputate, est nec fermentum convallis, sapien enim aliquet mauris, eu tempus ipsum neque at dui. Aenean nisi ipsum, tincidunt ac vestibulum ac, ullamcorper quis ante. Sed suscipit lacus et lacus ultricies non eleifend elit tincidunt. Nulla quis dui quam, eget vulputate quam. Ut porttitor est a tortor viverra nec molestie arcu convallis. Nulla facilisi.</p>

					<p>Vestibulum quis ligula dolor. Aliquam erat volutpat. Quisque in risus tellus, in consectetur dolor. Sed viverra quam eget ligula lobortis imperdiet sit amet at ipsum. Donec suscipit iaculis placerat. Vivamus porttitor, lacus et porttitor placerat, erat quam rhoncus diam, in consequat magna augue sit amet lectus. Quisque vestibulum hendrerit felis vel tempus. Aenean non hendrerit nunc. Aliquam erat volutpat. Cras ante nibh, viverra bibendum porttitor sed, elementum eu lorem. Nullam iaculis vulputate nisi. Curabitur in nisl quam. Quisque laoreet sapien ut velit tincidunt aliquet condimentum felis mattis. Praesent dictum condimentum rutrum. Praesent vel lacus et elit scelerisque suscipit eu et odio.</p>

					<p>Etiam pharetra sapien ut ante feugiat porta. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Mauris sit amet lacus quam, eu pharetra nulla. Vivamus faucibus nisl vel sem cursus malesuada. Cras iaculis dignissim ultrices. Fusce id velit et justo gravida tempus. Morbi lorem metus, posuere ut convallis quis, fringilla ut massa. Duis dictum, neque in malesuada tristique, nisi risus suscipit mauris, in pellentesque leo nisi nec purus.</p>

					<p>Donec ac leo vel erat mattis posuere in non nulla. Nunc vitae rhoncus ipsum. Sed tempus lectus lacinia nulla mattis laoreet. Proin bibendum quam ornare ante condimentum interdum quis ac libero. Sed elementum urna ut erat sagittis vel porttitor lectus facilisis. Cras augue turpis, porta quis pulvinar sit amet, fringilla ut tortor. Aliquam erat volutpat. Pellentesque magna eros, consequat sed blandit sed, vulputate et felis. Nullam eget ipsum arcu, at congue mi. Morbi hendrerit malesuada felis quis vestibulum.</p>


				</div> -->
			</div>
		</div>
		<footer class="footer">
			<div class="container">
				<div class="pull-right"><a href="http://www.startupapi.com/" target="_blank"><img src="<?php echo UserConfig::$USERSROOTURL ?>/images/powered_by_logo.png" width="149" height="67"/></a></div>
				<p>Powered by <a href="http://www.startupapi.com/" target="_blank">Startup API v1.19</a>. Code licensed under the <a href="https://github.com/sergeychernyshev/users/blob/master/LICENSE" target="_blank">MIT License</a>.</p>
				<p>This application uses <a href="http://twitter.github.com/bootstrap/" target="_blank">Twitter Bootstrap</a>.</p>
				<p>Icons from <a href="http://glyphicons.com" target="_blank">Glyphicons Free</a>, licensed under <a href="http://creativecommons.org/licenses/by/3.0/" target="_blank">CC BY 3.0</a>.</p>

			</div>
		</footer>
	</body>
</html>
