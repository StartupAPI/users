<?php
namespace StartupAPI\Modules\LinkedInAuthenticationModule;

/**
 * LinkedIn user credentials class
 *
 * @package StartupAPI
 * @subpackage Authentication\Linkedin
 */
class LinkedInUserCredentials extends OAuthUserCredentials {

	public function getHTML() {
		return \StartupAPI\StartupAPI::$template->render(
			"@startupapi/modules/linkedin/credentials.html.twig",
			$this->userinfo
		);
	}

}
