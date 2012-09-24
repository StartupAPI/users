<?php
/**
 * @package StartupAPI
 */
require_once(dirname(__FILE__).'/User.php');

/**
 * StartupAPI class contains some global static functions and entry points for API
 */
class StartupAPI {
	// just a proxy to static methods in User class
	static function getUser() {
		return User::get();
	}

	// just a proxy to static methods in User class
	static function requireLogin() {
		return User::requireLogin();
	}

	/**
	 * This finction should be called within the head of HTML to insert
	 * styles, scripts and potentially meta-tags into the head of the pages on the site
	*/
	static function head() {
		?><link rel="stylesheet" type="text/css" href="<?php echo UserConfig::$USERSROOTURL ?>/themes/classic/startupapi.css"><?php
	}

	/**
	 * This finction renders the power strip (navigation bar at the top right corner)
	*/
	static function power_strip()
	{
		$current_user = User::get();
		$current_account = null;

		$accounts = array();
		if (UserConfig::$useAccounts && !is_null($current_user)) {
			$accounts = Account::getUserAccounts($current_user);

			$current_account = Account::getCurrentAccount($current_user);
		}
		?>
	<div id="startupapi-navbox">
		<?php if (!is_null($current_user))
		{
			if ($current_user->isImpersonated()) {
				?><b id="startupapi-navbox-impersonating"><a href="<?php echo UserConfig::$USERSROOTURL ?>/admin/stopimpersonation.php" title="Impersonated by <?php echo UserTools::escape($current_user->getImpersonator()->getName())?>">Stop Impersonation</a></b> | <?php
			}

			if ($current_user->isAdmin()) {
				?><b id="startupapi-navbox-admin"><a href="<?php echo UserConfig::$USERSROOTURL ?>/admin/">Admin</a></b> | <?php
			}

			if (count($accounts) > 1)
			{
				$destination = "'+encodeURIComponent(document.location)+'";
				if (!is_null(UserConfig::$accountSwitchDestination)) {
					$destination = UserConfig::$accountSwitchDestination;
				}

				?><select id="startupapi-navbox-account-picker" name="account" onchange="document.location.href='<?php echo UserConfig::$USERSROOTURL ?>/change_account.php?return=<?php echo $destination ?>&account='+this.value"><?php

				foreach ($accounts as $account)
				{
					?><option value="<?php echo $account->getID()?>"<?php if ($current_account->isTheSameAs($account)) { echo ' selected'; } ?>><?php echo UserTools::escape($account->getName())?></option><?php
				}
			?></select>
			<?php
			}

			if (!is_null(UserConfig::$onLoginStripLinks)) {
				$links = call_user_func_array(
					UserConfig::$onLoginStripLinks,
					array($current_user, $current_account)
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

			<span id="startupapi-navbox-username"><a href="<?php echo UserConfig::$USERSROOTURL ?>/edit.php" title="<?php echo UserTools::escape($current_user->getName())?>'s user information"><?php echo UserTools::escape($current_user->getName()) ?></a></span> |
			<span id="startupapi-navbox-logout"><a href="<?php echo UserConfig::$USERSROOTURL ?>/logout.php">logout</a></span>
			<?php
		}
		else
		{
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
	 * This function should be called after all configuration is loaded to initialize the system.
	*/
	static function _init() {
		//currently empty
	}
}
/**
 * Exception superclass used for all exceptions in StartupAPI
 *
 * @package StartupAPI
 */
class StartupAPIException extends Exception {
	function __construct($message, $code, $previous) {
		parent::__construct('[StartupAPI] ' . $message, $code, $previous);
	}
}

/**
 * Exception for database-related problems
 *
 * @package StartupAPI
 */
class DBException extends StartupAPIException {
	function __construct(mysqli $db = null, $stmt = null, $message = null, $code = null, $previous = null) {
		$exception_message = $message;

		if (is_null($db)) {
			$exception_message = "[DB] Can't connect to database, \$db object is null";
		} else if ($db->connect_error) {
			$exception_message = "[DB] Can't connect to database: (" . $db->connect_errno . ") " . $db->connect_error;
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
 */
class DBBindParamException extends DBException {}

/**
 * Result binding Exception
 */
class DBBindResultException extends DBException {}

/**
 * Statement Execution Exception
 */
class DBExecuteStmtException extends DBException {}

/**
 * Statement preparation Exception
 */
class DBPrepareStmtException extends DBException {}
