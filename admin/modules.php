<?php
require_once(__DIR__ . '/admin.php');

// temporary switch to make it easy to see experimental modules
$show_experimental = array_key_exists('experimental', $_GET);

$module_categories = array(
	'auth' => array(
		'title' => 'Authentication modules'
	),
	'email' => array(
		'title' => 'Email module'
	),
	'payment' => array(
		'title' => 'Payment engines'
	)
);

$builtin_modules = array(
	'usernamepass' => array(
		'class' => 'UsernamePasswordAuthenticationModule',
		'category_slug' => 'auth'
	),
	'email' => array(
		'class' => 'EmailAuthenticationModule',
		'experimental' => true,
		'category_slug' => 'auth'
	),
	'facebook' => array(
		'class' => 'FacebookAuthenticationModule',
		'category_slug' => 'auth'
	),
	'twitter' => array(
		'class' => 'TwitterAuthenticationModule',
		'category_slug' => 'auth'
	),
	'google' => array(
		'class' => 'GoogleAuthenticationModule',
		'category_slug' => 'auth'
	),
	'linkedin' => array(
		'class' => 'LinkedInAuthenticationModule',
		'category_slug' => 'auth'
	),
	'meetup' => array(
		'class' => 'MeetupAuthenticationModule',
		'category_slug' => 'auth'
	),
	'etsy' => array(
		'class' => 'EtsyAuthenticationModule',
		'category_slug' => 'auth'
	),
	'foursquare' => array(
		'class' => 'FoursquareAuthenticationModule',
		'category_slug' => 'auth'
	),
	'github' => array(
		'class' => 'GithubAuthenticationModule',
		'category_slug' => 'auth'
	),
	'ohloh' => array(
		'class' => 'OhlohAuthenticationModule',
		'category_slug' => 'auth'
	),
	'mailchimp' => array(
		'class' => 'MailChimpModule',
		'experimental' => true,
		'category_slug' => 'email'
	),
	'manual' => array(
		'class' => 'ManualPaymentEngine',
		'experimental' => true,
		'category_slug' => 'payment'
	),
	'external_payment' => array(
		'class' => 'ExternalPaymentEngine',
		'experimental' => true,
		'category_slug' => 'payment'
	),
	'stripe' => array(
		'class' => 'StripePaymentEngine',
		'experimental' => true,
		'category_slug' => 'payment'
	),
	'shallow' => array(
		'class' => 'ShallowAuthenticationModule',
		'experimental' => true,
		'category_slug' => 'auth'
	),
);

$ADMIN_SECTION = 'modules';
require_once(__DIR__ . '/header.php');
?>
<div class="span9">
	<?php
	if ($show_experimental) {
		?>
		<a href="modules.php" class="btn btn-warning pull-right">Hide Experimental</a>
		<?php
	} else {
		?>
		<a href="modules.php?experimental" class="btn pull-right">Show Experimental</a>
		<?php
	}

	foreach ($module_categories as $category_slug => $module_category) {
		$category_modules = array();
		foreach ($builtin_modules as $module_slug => $module) {
			if ($module['category_slug'] == $category_slug && ($show_experimental || !array_key_exists('experimental', $module) || !$module['experimental'])
			) {
				$category_modules[$module_slug] = $module;
			}
		}

		/*
		 * Order installed modules first
		 */
		$installed_modules = array();
		$not_installed_modules = array();
		foreach ($category_modules as $module_slug => $module) {
			$instances = array();
			foreach (UserConfig::$all_modules as $installed_module) {
				if (get_class($installed_module) == $module['class']) {
					$instances[] = $installed_module;
				}
			}

			if (count($instances) > 0) {
				$installed_modules[$module_slug] = $module;
			} else {
				/*
				 * Ignore experimental modules that are not installed
				 */
				if ($show_experimental || !array_key_exists('experimental', $module) || !$module['experimental']) {
					$not_installed_modules[$module_slug] = $module;
				}
			}
		}

		$category_modules = array_merge($installed_modules, $not_installed_modules);

		/*
		 * Don't show empty categories
		 */
		if (count($category_modules) == 0) {
			continue;
		}
		?>
		<h2><?php echo $module_category['title'] ?></h2>
		<div class="modules">
			<?php
			foreach ($category_modules as $module_slug => $module) {
				UserConfig::loadModule($module_slug);

				$instances = array();
				foreach (UserConfig::$all_modules as $installed_module) {
					// checking if this built-in module was ever instantiated
					if (get_class($installed_module) == $module['class']) {
						$instances[] = $installed_module;
					}
				}

				$is_experimental = array_key_exists('experimental', $module) && $module['experimental'];

				if (count($instances) > 0) {
					// going through module objects
					foreach ($instances as $module) {
						?>
						<div class="well well-small startupapi-module">
							<?php
							$logo = $module->getLogo(100);

							if (!is_null($logo)) {
								?>
								<img src="<?php echo UserTools::escape($logo); ?>" width="100" height="100" class="pull-right"/>
								<?php
							}
							?>
							<p class="startupapi-module-title"><?php echo $module->getTitle() ?></p>

							<p>
								<span class="label label-success"><i class="icon-ok icon-white"></i>
									Installed
								</span>

								<?php
								if ($is_experimental) {
									?>
									<span class="label label-warning"><i class="icon-exclamation-sign icon-white"></i>
										Experimental
									</span>
									<?php
								}
								?>
							</p>

							<p><?php echo $module->getDescription() ?></p>
						</div>

						<?php
					}
				} else {
					?>
					<div class="well well-small startupapi-module startupapi-module-not-installed">
						<?php
						$class = $module['class'];

						// getting info from the class
						$logo = $class::getModulesLogo(100);
						if (!is_null($logo)) {
							?>
							<img src="<?php echo UserTools::escape($logo); ?>" class="pull-right startupapi-module-logo"/>
							<?php
						}
						?>
						<p class="startupapi-module-title"><?php echo UserTools::escape($class::getModulesTitle()) ?></p>

						<p>
							<span class="label">
								<i class="icon-minus icon-white"></i>
								Not Installed
							</span>

							<?php
							if ($is_experimental) {
								?>
								<span class="label label-warning"><i class="icon-exclamation-sign icon-white"></i>
									Experimental
								</span>
								<?php
							}
							?>
						</p>

						<p><?php echo $class::getModulesDescription() ?></p>
						<?php
						$url = $class::getSignupURL();
						if (!is_null($url)) {
							?>
							<p>
								<i class="icon-home"></i>
								Sign up:
								<a href="<?php echo UserTools::escape($url) ?>" target="_blank">
									<?php echo UserTools::escape($url) ?>
								</a>
							</p>
							<?php
						}
						?>
					</div>
					<?php
				}
				?>
				<?php
			}
			?>
		</div>
		<?php
	}
	?>
</div>
<style>
	.modules .startupapi-module {
		width: 44%;
	}

	@media (max-width: 800px) {
		.modules .startupapi-module {
			width: 100%;
		}
	}
</style>
<script src="<?php echo UserConfig::$USERSROOTURL ?>/imagesloaded/imagesloaded.pkgd.min.js"></script>
<script src="<?php echo UserConfig::$USERSROOTURL ?>/masonry/dist/masonry.pkgd.min.js"></script>
<script>
	$('.modules').imagesLoaded(function () {
		$('.modules').masonry({
			columnWidth: '.startupapi-module',
			itemSelector: '.startupapi-module',
			gutter: 20
		});
	});
</script>
<?php
require_once(__DIR__ . '/footer.php');
