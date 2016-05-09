<?php
namespace StartupAPI\Modules\EmailAuthenticationModule;

/**
 * Email credentials for the user
 *
 * @package StartupAPI
 * @subpackage Authentication\Email
 */
class EmailUserCredentials extends \StartupAPI\UserCredentials {

	/**
	 * @var string User's email address
	 */
	private $email;

	/**
	 * Creates user credentials object
	 *
	 * @param string $email User's email address
	 */
	public function __construct($email) {
		$this->email = $email;
	}

	/**
	 * Returns user's email address
	 *
	 * @return string User's email address
	 */
	public function getEmail() {
		return $this->email;
	}

	public function getHTML() {
		return $this->email;
	}

}
