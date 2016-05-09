<?php
namespace StartupAPI\Modules\InstagramAuthenticationModule;

/**
 * @package StartupAPI
 * @subpackage Authentication\Instagram
 */
class InstagramUserCredentials extends \StartupAPI\OAuth2UserCredentials {
	public function getHTML() {
		return \StartupAPI\StartupAPI::$template->render(
			"@startupapi/modules/instagram/credentials.html.twig",
			$this->userinfo
		);
	}
}
