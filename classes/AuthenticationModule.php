<?php
namespace StartupAPI;

/**
 * Abstract class that can be subclassed to implement StartupAPI Authentication modules/extensions
 *
 * This class implements registration tracking by module out of the box.
 *
 * @package StartupAPI
 * @subpackage Authentication
 */
abstract class AuthenticationModule extends StartupAPIModule {

	/**
	 * Creates a new module and registers it in the system
	 */
	public function __construct() {
		parent::__construct();
		UserConfig::$authentication_modules[] = $this;
	}

	/**
	 * Renders login form HTML
	 *
	 * Implementations of this method must render login form
	 *
	 * @param array[] $template_info Array of base information for Twig template
	 * @param string $action Action URL the form should submit data to
	 */
	abstract public function renderLoginForm($template_info, $action);

	/**
	 * Renders registration form HTML
	 *
	 * Implementations of this method must render registration form
	 *
	 * @param array[] $template_info Array of base information for Twig template
	 * @param boolean $full Whatever or not to display a short version of the form or full
	 * @param string $action Action URL the form should submit data to
	 * @param array $errors An array of error messages to be displayed to the user on error
	 * @param array $data An array of data passed by a form on previous submission to display back to user
	 */
	abstract public function renderRegistrationForm($template_info, $full = false, $action = null, $errors = null, $data = null);

	/**
	 * Renders user editing form
	 *
	 * Implementations of this method must render login form
	 *
	 * @param array[] $template_info Array of base information for Twig template
	 * @param string $action Form action URL to post back to
	 * @param array $errors Array of error messages to display
	 * @param User $user User object for current user that is being edited
	 * @param array $data Data submitted to the form
	 *
	 * @return string Rendered user ediging form for this module
	 */
	abstract public function renderEditUserForm($template_info, $action, $errors, $user, $data);

	/**
	 * Processes login form data
	 *
	 * In some implementations this method can trigger automatic registration
	 * and sometimes just calls processRegistration method
	 *
	 * @todo Rework how &$remember is used across modules - current implementation is kind-of weird.
	 *
	 * @param array $data An array of data passed by the login form
	 * @param boolean $remember Remember flag to be updated if user is to be remembered longer then one session.
	 *
	 * @return User User object for a logged in user or null if login failed
	 */
	abstract public function processLogin($data, &$remember);

	/**
	 * Processes registration form data
	 *
	 * In some implementations this method can trigger automatic login if user is already registered
	 * and sometimes just calls processLogin method
	 *
	 * @todo Rework how &$remember is used across modules - current implementation is kind-of weird.
	 *
	 * @param array $data An array of data passed by the login form
	 * @param boolean $remember Remember flag to be updated if user is to be remembered longer then one session.
	 *
	 * @return User User object for a successfully registered user or null on failure
	 *
	 * @throws InputValidationException
	 * @throws ExistingUserException
	 */
	abstract public function processRegistration($data, &$remember);

	/**
	 * Process user connection form data
	 *
	 * @param User $user User who's information is being edited
	 * @param array $data Form data
	 *
	 * @return boolean True if successful, false if unsuccessful
	 *
	 * @throws InputValidationException If there are problems with input data
	 */
	abstract public function processEditUser($user, $data);

	/**
	 * Returns user credentials
	 *
	 * Implementations of this method must return an instance of UserCredention object or it's subclass
	 *
	 * @param User $user User to get credentials for
	 * @return UserCredentials User credentials object specific to the module
	 *
	 * @throws Exceptions\DBException
	 */
	abstract public function getUserCredentials($user);

	/**
	 * Returns a number of users connected using this module
	 *
	 * Implementations of this module must returns total number of users connected with provider
	 *
	 * @return int Number of users who have connections through this provider
	 * 	       Some modules might allow for multiple connections, but the user is only counted once
	 *
	 * @throws Exceptions\DBException
	 */
	abstract public function getTotalConnectedUsers();

	/**
	 * Returns authentication module by ID
	 *
	 * @param string $id ID of the module
	 * @return AuthenticationModule Authentication module object
	 */
	public static function get($id) {
		foreach (UserConfig::$authentication_modules as $module) {
			if ($module->getID() == $id) {
				return $module;
			}
		}
	}

	/**
	 * Returns module color to be used in chart legends
	 *
	 * Submodules must implement this method returning a hex color value
	 *
	 * @return string Hex color string
	 */
	abstract public function getLegendColor();

	/**
	 * By default, do not auto-login, should be overriden by modules that support auto-login
	 *
	 * @return User Returns automatically logged in user or null if user is not logged in
	 * @see FacebookAuthenticationModule
	 */
	public function processAutoLogin() {
		return null;
	}

	/**
	 * Returns auto-logout URL
	 *
	 * Implementations can return a URL for auto-logout (remote logout) if provider
	 * supports and requires applications to log out the user upon app logout
	 * and provides instante logout URL.
	 *
	 * @param string $return URL to return to upon remote logout
	 * @return string Logout URL to redirect to or null if provider does not support auto-logout
	 */
	public function getAutoLogoutURL($return) {
		return null;
	}

	/**
	 * Renders auto-logout form
	 *
	 * Show a page indicating a logout process and/or logging out user using and API call or something
	 *
	 * @param array[] $template_info Array of base information for Twig template
	 */
	public function renderAutoLogoutForm($template_info) {
		// by default modules do not support auto-logout
	}

	/**
	 * Retrieves aggregated registration numbers
	 *
	 * @return array Array of date => count pairs for all dates that have users registered on that date
	 *
	 * @throws Exceptions\DBException
	 */
	public function getDailyRegistrations() {
		$db = UserConfig::getDB();

		$dailyregs = array();

		if ($stmt = $db->prepare('SELECT CAST(regtime AS DATE) AS regdate, count(*) AS regs FROM u_users WHERE regmodule = ? GROUP BY regdate')) {
			if (!$stmt->bind_param('s', $this->getID())) {
				throw new Exceptions\DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new Exceptions\DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($regdate, $regs)) {
				throw new Exceptions\DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$dailyregs[] = array('regdate' => $regdate, 'regs' => $regs);
			}

			$stmt->close();
		} else {
			throw new Exceptions\DBPrepareStmtException($db);
		}

		return $dailyregs;
	}

	/**
	 * Returns true if sign-up, login and registration forms are compact and
	 * can be combined on one page.
	 *
	 * @return boolean True if compact
	 */
	public function isCompact() {
		return false;
	}

}
