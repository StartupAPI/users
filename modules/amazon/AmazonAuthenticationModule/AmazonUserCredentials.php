<?php
namespace StartupAPI\Modules\AmazonAuthenticationModule;

/**
 * @package StartupAPI
 * @subpackage Authentication\Amazon
 */
class AmazonUserCredentials extends \StartupAPI\OAuth2UserCredentials {
	public function getHTML() {
		return \StartupAPI\StartupAPI::$template->render(
			"@startupapi/modules/amazon/credentials.html.twig",
			$this->userinfo
		);
	}
}
