<?php
namespace StartupAPI\Modules\MeetupAuthenticationModule;

/**
 * Meetup user credentials class
 *
 * @package StartupAPI
 * @subpackage Authentication\Meetup
 */
class MeetupUserCredentials extends OAuthUserCredentials {
	public function getHTML() {
		return StartupAPI::$template->render("@startupapi/modules/meetup/credentials.html.twig", $this->userinfo);
	}
}
