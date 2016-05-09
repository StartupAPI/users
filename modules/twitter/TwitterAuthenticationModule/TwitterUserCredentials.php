<?php
namespace StartupAPI\Modules\TwitterAuthenticationModule;

/**
 * Twitter user credentials
 *
 * @package StartupAPI
 * @subpackage Authentication\Twitter
 */
class TwitterUserCredentials extends \StartupAPI\OAuthUserCredentials {
	public function getHTML() {
		return StartupAPI::$template->render("@startupapi/modules/twitter/credentials.html.twig", $this->userinfo);
	}
}
