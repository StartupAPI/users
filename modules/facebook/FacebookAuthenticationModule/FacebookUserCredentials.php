<?php
namespace StartupAPI\Modules\FacebookAuthenticationModule;

/**
 * User credentials for Facebook users
 *
 * @package StartupAPI
 * @subpackage Authentication\Facebook
 */
class FacebookUserCredentials extends \StartupAPI\UserCredentials {

	/**
	 * @var int Facebook user id
	 */
	private $fb_id;

	/**
	 * Creates credemtials onject
	 *
	 * @param int $fb_id Facebook user ID
	 */
	public function __construct($fb_id) {
		$this->fb_id = $fb_id;
	}

	/**
	 * Returns Facebook user ID
	 *
	 * @return int Facebook user ID
	 */
	public function getFacebookID() {
		return $this->fb_id;
	}

	public function getHTML() {
		return \StartupAPI\StartupAPI::$template->render(
			"@startupapi/modules/facebook/credentials.html.twig",
			array('fb_id' => $this->fb_id)
		);
	}

}
