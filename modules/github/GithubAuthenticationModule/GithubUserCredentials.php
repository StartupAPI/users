<?php
namespace StartupAPI\Modules\GithubAuthenticationModule;

/**
 * @package StartupAPI
 * @subpackage Authentication\Github
 */
class GithubUserCredentials extends \StartupAPI\OAuth2UserCredentials {
	public function getHTML() {
		return StartupAPI::$template->render("@startupapi/modules/github/credentials.html.twig", $this->userinfo);
	}
}
