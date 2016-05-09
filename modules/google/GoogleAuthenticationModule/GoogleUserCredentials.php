<?php
namespace StartupAPI\Modules\GoogleAuthenticationModule;

/**
 * @package StartupAPI
 * @subpackage Authentication\Google
 */
class GoogleUserCredentials extends \StartupAPI\OAuth2UserCredentials {
	public function getHTML() {
		return \StartupAPI\StartupAPI::$template->render(
			"@startupapi/modules/google/credentials.html.twig",
			$this->userinfo
		);
	}
}
