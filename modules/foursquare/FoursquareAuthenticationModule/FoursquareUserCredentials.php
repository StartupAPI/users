<?php
namespace StartupAPI\Modules\FoursquareAuthenticationModule;

/**
 * @package StartupAPI
 * @subpackage Authentication\Foursquare
 */
class FoursquareUserCredentials extends \StartupAPI\OAuth2UserCredentials {
	public function getHTML() {
		return StartupAPI::$template->render("@startupapi/modules/foursquare/credentials.html.twig", $this->userinfo);
	}
}
