<?php
/**
 * @package StartupAPI
 */
require_once(dirname(__FILE__).'/global.php');

interface IUserBaseModule
{
	/**
	 * Returns module ID
	 * Each module must implement this method and use unique ID
	 */
	public function getID();
	public function getTitle();
}

abstract class UserBaseModule implements IUserBaseModule {
	public function __construct() {
		UserConfig::$all_modules[] = $this;
	}

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

