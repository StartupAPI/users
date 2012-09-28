<?php
require_once(dirname(__FILE__).'/global.php');

/**
 * StartupAPI module class
 *
 * Subclass it if you want to add a module/extension
 *
 * @package StartupAPI
 * @subpackage Extensions
 */
abstract class StartupAPIModule {
	public function __construct() {
		UserConfig::$all_modules[] = $this;
	}

	/**
	 * Returns module ID string
	 * Each module must implement this method and use unique ID
	 *
	 * @return string Descriptionunique module ID
	 */
	abstract public function getID();

	/**
	 * Returns human readable module name
	 *
	 * @return string Module name
	 */
	abstract public function getTitle();

	/**
	 * Returns module by ID
	 * @param string $id ID of the module
	 */
	public static function get($id) {
		foreach (UserConfig::$all_modules as $module)
		{
			if ($module->getID() == $id) {
				return $module;
			}
		}
	}
}

require_once(dirname(__FILE__).'/AuthenticationModule.php');
require_once(dirname(__FILE__).'/EmailModule.php');

