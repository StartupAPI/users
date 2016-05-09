<?php
namespace StartupAPI\Modules\EtsyAuthenticationModule;

/**
 * Etsy user credentials
 *
 * @package StartupAPI
 * @subpackage Authentication\Etsy
 */
class EtsyUserCredentials extends \StartupAPI\OAuthUserCredentials {
	public function getHTML() {
		return \StartupAPI\StartupAPI::$template->render("@startupapi/modules/etsy/user_info.html.twig", unserialize($this->userinfo));
	}
}
