<?php
require_once(__DIR__ . '/User.php');
require_once(__DIR__ . '/Plan.php');

require_once(dirname(__DIR__) . '/twig/lib/Twig/Autoloader.php');
Twig_Autoloader::register();

/**
 * StartupAPI class contains some global static functions and entry points for API
 *
 * @package StartupAPI
 */
class StartupAPI {

	/**
	 * @var int Startup API major version number - to be changed only manually in this code
	 */
	private static $major_version = 0;

	/**
	 * @var int Startup API minor version - to be incremented automatically when asked for
	 */
	private static $minor_version = 5;

	/**
	 * @var int	Startup API patch level (version number) - to be incremented automatically when build script is ran
	 */
	private static $patch_level = 1;

	/**
	 * @var string Startup API pre-release version string
	 */
	private static $pre_release_version;

	/**
	 * @var string Startup API build version string
	 */
	private static $build_version;

	/**
	 * @var Twig_Environment Templating tool to use for rendering templates
	 */
	public static $template;

	/**
	 * Just a proxy to static User::get() method in User class
	 *
	 * @return User|null
	 */
	static function getUser() {
		return User::get();
	}

	/**
	 * Just a proxy to static User::require_login() method in User class
	 *
	 * @return User
	 */
	static function requireLogin() {
		return User::require_login();
	}

	/**
	 * This finction should be called within the head of HTML to insert
	 * styles, scripts and potentially meta-tags into the head of the pages on the site
	 */
	static function head() {
		$bootstrapCSS = UserConfig::$USERSROOTURL . '/bootstrap/css/bootstrap.min.css';
		if (!is_null(UserConfig::$bootstrapCSS)) {
			$bootstrapCSS = UserConfig::$bootstrapCSS;
		}
		?>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link href="<?php echo $bootstrapCSS ?>" rel="stylesheet">
		<link href="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
		<script src="<?php echo UserConfig::$USERSROOTURL ?>/jquery-1.10.2.min.js"></script>
		<script src="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap/js/bootstrap.min.js"></script>

		<link rel="stylesheet" type="text/css" href="<?php echo UserConfig::$USERSROOTURL ?>/themes/<?php echo UserConfig::$theme ?>/startupapi.css">
		<?php
	}

	/**
	 * This finction renders the power strip (navigation bar at the top right corner)
	 */
	static function power_strip($nav_pills = null, $show_navbar = null, $inverted_navbar = null, $pull_right = null) {
		$current_user = User::get();
		$current_account = null;

		$accounts = array();
		if (!is_null($current_user)) {
			$accounts = Account::getUserAccounts($current_user);

			$current_account = Account::getCurrentAccount($current_user);
		}

		/**
		 * Setting instance defaults
		 */
		if (is_null($nav_pills)) {
			$nav_pills = UserConfig::$powerStripNavPills;
		}

		if (is_null($show_navbar)) {
			$show_navbar = UserConfig::$powerStripShowNavbar;
		}

		if (is_null($inverted_navbar)) {
			$inverted_navbar = UserConfig::$powerStripInvertedNavbar;
		}

		if (is_null($pull_right)) {
			$pull_right = UserConfig::$powerStripPullRight;
		}

		if (!$nav_pills && $show_navbar) {
			?>
			<div class="navbar<?php if ($inverted_navbar) { ?> navbar-inverse<?php } ?> <?php if ($pull_right) { ?> pull-right<?php } ?>">
				<div class="navbar-inner">
					<?php
				}
				?>
				<ul class="nav<?php if ($nav_pills) { ?> nav-pills<?php } ?>  <?php if ($pull_right) { ?> pull-right<?php } ?>">
					<?php
					if (!is_null($current_user)) {
						if ($current_user->isImpersonated()) {
							?>
							<li><span><a class="btn btn-danger" id="startupapi-navbox-impersonating" href="<?php echo UserConfig::$USERSROOTURL ?>/admin/stopimpersonation.php" title="Impersonated by <?php echo UserTools::escape($current_user->getImpersonator()->getName()) ?>">Stop Impersonation</a></span></li>
							<?php
						}

						if ($current_user->isAdmin()) {
							?>
							<li><a id="startupapi-navbox-admin" href="<?php echo UserConfig::$USERSROOTURL ?>/admin/">Admin</a></li>
							<?php
						}

						if (count($accounts) > 1) {
							$destination = "'+encodeURIComponent(document.location)+'";
							if (!is_null(UserConfig::$accountSwitchDestination)) {
								$destination = UserConfig::$accountSwitchDestination;
							}
							?>
							<li class="dropdown">
								<a href="#" title="Change account" class="dropdown-toggle" data-toggle="dropdown"><?php echo UserTools::escape($current_account->getName()) ?><b class="caret"></b></a>
								<ul class="dropdown-menu" role="menu" aria-labelledby="dLabel" id="startupapi-account-switcher">
									<?php
									foreach ($accounts as $account) {
										$is_current = $current_account->isTheSameAs($account);

										if ($is_current) {
											continue;
										}
										?>
										<li>
											<a tabindex="-1" href="#"
											<?php
											if (!$is_current) {
												?>
												   data-account-swtich-to="<?php echo $account->getID() ?>"
												   <?php
											   }
											   ?>
											   >
												   <?php
												   echo UserTools::escape($account->getName());
												   ?>
											</a>
										</li>
										<?php
									}
									?>
								</ul>
								<script>
									$('#startupapi-account-switcher').click(function(e) {
										var account_swtich_to = $(e.target).data('account-swtich-to');

										if (typeof(account_swtich_to) !== 'undefined') {
											document.location.href = '<?php echo UserConfig::$USERSROOTURL ?>/change_account.php?return=<?php echo $destination ?>&account='+account_swtich_to;
										}

										return false;
									});
								</script>
							</li>
							<?php
						}

						if (!is_null(UserConfig::$onLoginStripLinks)) {
							$links = call_user_func_array(
									UserConfig::$onLoginStripLinks, array($current_user, $current_account)
							);

							foreach ($links as $link) {
								?>
								<li
								<?php
								if (array_key_exists('id', $link)) {
									?>
										id="<?php echo $link['id'] ?>"
										<?php
									}
									?>
									><a href="<?php echo $link['url'] ?>"
										<?php
										if (array_key_exists('title', $link)) {
											?>
										title="<?php echo $link['title'] ?>"
										<?php
									}
									if (array_key_exists('target', $link)) {
										?>
										target="<?php echo $link['target'] ?>"
										<?php
									}
									?>
									><?php echo $link['text'] ?></a>
								</li>
								<?php
							}
						}
						?>

						<li id="startupapi-navbox-username"><a href="<?php echo UserConfig::$USERSROOTURL ?>/edit.php" title="<?php echo UserTools::escape($current_user->getName()) ?>'s user information"><?php echo UserTools::escape($current_user->getName()) ?></a></li>
						<li id="startupapi-navbox-logout"><a href="<?php echo UserConfig::$USERSROOTURL ?>/logout.php">Logout</a></li>
						<?php
					} else {
						?>
						<li id="startupapi-navbox-signup"><a href="<?php echo UserConfig::$USERSROOTURL ?>/register.php">Sign Up Now!</a></li>
						<li id="startupapi-navbox-login"><a href="<?php echo UserConfig::$USERSROOTURL ?>/login.php">Log in</a></li>
						<?php
					}
					?>
				</ul>
				<?php
				if (!$nav_pills && $show_navbar) {
					?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Incrememts minor version of software
	 */
	public static function incrementMinorVersion() {
		self::$minor_version++;
	}

	/**
	 * Incrememts patch level of software
	 */
	public static function incrementPatchLevel() {
		self::$patch_level++;
	}

	/**
	 * Returns a string representing Statup API version
	 *
	 * @return string Startup API version
	 */
	public static function getVersion() {
		$version = self::$major_version . '.' . self::$minor_version . '.' . self::$patch_level;

		if (!is_null(self::$pre_release_version)) {
			$version .= '-' . self::$pre_release_version;
		}

		if (!is_null(self::$build_version)) {
			$version .= '+build.' . self::$build_version;
		}

		return $version;
	}

	/**
	 * This function should be called after all configuration is loaded to initialize the system.
	 */
	static function _init() {
		/**
		 * Legacy configuration options support
		 */
		if (!is_null(UserConfig::$enableInvitations)) {
			UserConfig::$adminInvitationOnly = UserConfig::$enableInvitations;
			error_log('[Deprecated] You are using deprecated configuration setting: UserConfig::$enableInvitations - rename it to UserConfig::$adminInvitationOnly');
		}

		if (!is_null(UserConfig::$appName)) {
			UserConfig::$supportEmailXMailer = UserConfig::$appName . ' using ' . UserConfig::$supportEmailXMailer;
		}

		// Initializing more structures based on user configurations
		Plan::init(UserConfig::$PLANS);

		// Configuring the templating
		$loader = new Twig_Loader_Filesystem(dirname(__DIR__) . '/templates/');
		$loader->addPath(dirname(__DIR__) . '/admin/templates', 'admin');

		self::$template = new Twig_Environment($loader, UserConfig::$twig_options);
	}

}

/**
 * Exception superclass used for all exceptions in StartupAPI
 *
 * @package StartupAPI
 */
class StartupAPIException extends Exception {

	/**
	 * General Startup API Exception
	 *
	 * @param string $message Exception message
	 * @param int $code Exception code
	 * @param Exception $previous Previous exception in the chain
	 */
	function __construct($message, $code = null, $previous = null) {
		parent::__construct('[StartupAPI] ' . $message, $code, $previous);
	}

}

/**
 * Exception thrown when deprecated method is called
 *
 * Replace deprecated code with this exception to make sure instances that use
 * deprecated functionality have last warning to remove it.
 *
 * @package StartupAPI
 */
class StartupAPIDeprecatedException extends StartupAPIException {

}

/**
 * Exception for database-related problems
 *
 * @package StartupAPI
 */
class DBException extends StartupAPIException {

	/**
	 * Creates a database-related exception
	 *
	 * @param mysqli $db MySQLi database object
	 * @param mysqli_stmt $stmt MySQLi database statement
	 * @param string $message Exception message
	 * @param int $code Exception code
	 * @param Exception $previous Previous exception in the chain
	 */
	function __construct(mysqli $db = null, $stmt = null, $message = null, $code = null, $previous = null) {
		$exception_message = $message;

		$class = get_class($this);

		if (is_null($db)) {
			$exception_message = "[$class] Can't connect to database, \$db object is null";
		} else if ($db->connect_error) {
			$exception_message = "[$class] Can't connect to database: (" . $db->connect_errno . ") " .
					$db->connect_error;
		} else if ($db->error) {
			$exception_message = "[$class] DB Error: " . $db->error;
		} else if (!$stmt) {
			$exception_message = "[$class]" .
					' $db->error: ' . $db->error .
					' with message: ' . $message;
		} else {
			$exception_message = "[$class]" .
					' $stmt->error: ' . $stmt->error .
					' with message: ' . $message;
		}

		parent::__construct($exception_message, $code, $previous);
	}

}

/**
 * Paremeter Binding Exception
 *
 * @package StartupAPI
 */
class DBBindParamException extends DBException {

}

/**
 * Result binding Exception
 *
 * @package StartupAPI
 */
class DBBindResultException extends DBException {

}

/**
 * Statement Execution Exception
 *
 * @package StartupAPI
 */
class DBExecuteStmtException extends DBException {

}

/**
 * Statement preparation Exception
 *
 * @package StartupAPI
 */
class DBPrepareStmtException extends DBException {

}

