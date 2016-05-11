<?php
namespace StartupAPI;

/**
 * Adding an interface to allow defining unimplemented static methods
 *
 * @package StartupAPI
 * @subpackage Extensions
 */
interface StartupAPIModuleInterface {

	/**
	 * Returns module ID string
	 *
	 * Each module must implement this method and use unique ID
	 *
	 * @return string Unique module ID
	 */
	public function getID();

	/**
	 * Returns human readable module name for a class of modules
	 *
	 * @return string Module name
	 */
	public static function getModulesTitle();
}
