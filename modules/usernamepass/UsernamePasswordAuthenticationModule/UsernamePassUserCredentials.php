<?php
namespace StartupAPI\Modules\UsernamePasswordAuthenticationModule;

/**
 * Username credentials
 *
 * @package StartupAPI
 * @subpackage Authentication\UsernamePassword
 */
class UsernamePassUserCredentials extends \StartupAPI\UserCredentials {
	/**
	 * @var string Username
	 */
	private $username;

	/**
	 * Creates Username credentials object
	 *
	 * @param type $username
	 */
	public function __construct($username) {
		$this->username = $username;
	}

	/**
	 * Returns user's username
	 *
	 * @return string Username
	 */
	public function getUsername() {
		return $this->username;
	}

	public function getHTML() {
		return $this->username;
	}
}
