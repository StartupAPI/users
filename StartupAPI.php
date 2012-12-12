<?php
require_once(dirname(__FILE__) . '/User.php');
require_once(dirname(__FILE__) . '/Plan.php');

require_once(dirname(__FILE__) . '/twig/lib/Twig/Autoloader.php');
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
	private static $minor_version = 4;

	/**
	 * @var int	Startup API patch level (version number) - to be incremented automatically when build script is ran
	 */
	private static $patch_level = 0;

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
		return User::requireLogin();
	}

	/**
	 * This finction should be called within the head of HTML to insert
	 * styles, scripts and potentially meta-tags into the head of the pages on the site
	 */
	static function head() {
		?>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link href="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap/css/bootstrap.css" rel="stylesheet">
		<link href="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
		<script src="<?php echo UserConfig::$USERSROOTURL ?>/jquery-1.8.2.min.js"></script>
		<script src="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap/js/bootstrap.min.js"></script>

		<link rel="stylesheet" type="text/css" href="<?php echo UserConfig::$USERSROOTURL ?>/themes/<?php echo UserConfig::$theme ?>/startupapi.css">
		<?php
	}

	/**
	 * This finction renders the power strip (navigation bar at the top right corner)
	 */
	static function power_strip() {
		$current_user = User::get();
		$current_account = null;

		$accounts = array();
		if (UserConfig::$useAccounts && !is_null($current_user)) {
			$accounts = Account::getUserAccounts($current_user);

			$current_account = Account::getCurrentAccount($current_user);
		}
		?>
		<div id="startupapi-navbox">
			<?php
			if (!is_null($current_user)) {
				if ($current_user->isImpersonated()) {
					?><b id="startupapi-navbox-impersonating"><a href="<?php echo UserConfig::$USERSROOTURL ?>/admin/stopimpersonation.php" title="Impersonated by <?php echo UserTools::escape($current_user->getImpersonator()->getName()) ?>">Stop Impersonation</a></b> | <?php
			}

			if ($current_user->isAdmin()) {
					?><b id="startupapi-navbox-admin"><a href="<?php echo UserConfig::$USERSROOTURL ?>/admin/">Admin</a></b> | <?php
			}

			if (count($accounts) > 1) {
				$destination = "'+encodeURIComponent(document.location)+'";
				if (!is_null(UserConfig::$accountSwitchDestination)) {
					$destination = UserConfig::$accountSwitchDestination;
				}
					?><select id="startupapi-navbox-account-picker" name="account" onchange="document.location.href='<?php echo UserConfig::$USERSROOTURL ?>/change_account.php?return=<?php echo $destination ?>&account='+this.value"><?php
				foreach ($accounts as $account) {
						?><option value="<?php echo $account->getID() ?>"<?php
					if ($current_account->isTheSameAs($account)) {
						echo ' selected';
					}
						?>><?php echo UserTools::escape($account->getName()) ?></option><?php
				}
					?></select>
					<?php
				}

				if (!is_null(UserConfig::$onLoginStripLinks)) {
					$links = call_user_func_array(
							UserConfig::$onLoginStripLinks, array($current_user, $current_account)
					);

					foreach ($links as $link) {
						?><span<?php
					if (array_key_exists('id', $link)) {
							?> id="<?php echo $link['id'] ?>"<?php
					}
						?>><a href="<?php echo $link['url'] ?>"<?php
					if (array_key_exists('title', $link)) {
							?> title="<?php echo $link['title'] ?>"<?php
					}
					if (array_key_exists('target', $link)) {
							?> target="<?php echo $link['target'] ?>"<?php
					}
						?>><?php echo $link['text'] ?></a></span> | <?php
				}
			}
				?>

				<span id="startupapi-navbox-username"><a href="<?php echo UserConfig::$USERSROOTURL ?>/edit.php" title="<?php echo UserTools::escape($current_user->getName()) ?>'s user information"><?php echo UserTools::escape($current_user->getName()) ?></a></span> |
				<span id="startupapi-navbox-logout"><a href="<?php echo UserConfig::$USERSROOTURL ?>/logout.php">logout</a></span>
				<?php
			} else {
				?>
				<span id="startupapi-navbox-signup"><a href="<?php echo UserConfig::$USERSROOTURL ?>/register.php">Sign Up Now!</a></span> |
				<span id="startupapi-navbox-login"><a href="<?php echo UserConfig::$USERSROOTURL ?>/login.php">log in</a></span>
				<?php
			}
			?>
		</div>
		<?php
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
		// Initializing more structures based on user configurations
		Plan::init(UserConfig::$PLANS);

		// Configuring the templating
		$loader = new Twig_Loader_Filesystem(dirname(__FILE__) . '/templates/');
		$loader->addPath(dirname(__FILE__) . '/admin/templates', 'admin');

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

		if (is_null($db)) {
			$exception_message = "[DB] Can't connect to database, \$db object is null";
		} else if ($db->connect_error) {
			$exception_message = "[DB] Can't connect to database: (" . $db->connect_errno . ") " . $db->connect_error;
		} else if ($db->error) {
			$exception_message = "[DB] DB Error: " . $db->error;
		} else if (!$stmt) {
			$exception_message = '[DB]' .
					' $db->error: ' . $db->error .
					' with message: ' . $message;
		} else {
			$exception_message = '[DB]' .
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

