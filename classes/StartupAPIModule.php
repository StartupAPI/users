<?php
require_once(dirname(__DIR__).'/global.php');

/**
 * StartupAPI module class
 *
 * Subclass it if you want to add a module/extension
 *
 * @package StartupAPI
 * @subpackage Extensions
 *
 * @todo Rename module IDs to module slugs throughout the application to confirm with coding standards
 */
abstract class StartupAPIModule {
	/**
	 * Creates new module and registers it with the system
	 */
	public function __construct() {
		UserConfig::$all_modules[] = $this;
	}

	/**
	 * Returns module ID string
	 *
	 * Each module must implement this method and use unique ID
	 *
	 * @return string Unique module ID
	 */
	abstract public function getID();

	/**
	 * Returns human readable module name
	 *
	 * @return string Module name
	 */
	abstract public function getTitle();

	/**
	 * Returns module by slug
	 *
	 * @param string $slug Module slug
	 *
	 * @return StartupAPIModule StartupAPIModule object
	 */
	public static function get($slug) {
		foreach (UserConfig::$all_modules as $module)
		{
			if ($module->getID() == $slug) {
				return $module;
			}
		}
	}
}

require_once(__DIR__.'/AuthenticationModule.php');
require_once(__DIR__.'/EmailModule.php');
require_once(__DIR__.'/PaymentEngine.php');

