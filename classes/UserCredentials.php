<?php
namespace StartupAPI;

/**
 * Class representing user credentials for particular module
 * Must be subclassed and implemented by module
 *
 * @package StartupAPI
 * @subpackage Authentication
 */
abstract class UserCredentials {

	/**
	 * This method should return HTML representation of user credentials to be included in admin interface
	 * Usually linking to user's public profile at the source service
	 *
	 * @return string HTML representation of user credentials
	 */
	public abstract function getHTML();
}
