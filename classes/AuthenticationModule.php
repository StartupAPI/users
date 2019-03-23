<?php

require_once(__DIR__ . '/StartupAPIModule.php');

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
	 * @throws DBException
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
	 * @throws DBException
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
	 * @throws DBException
	 */
	public function getDailyRegistrations() {
		$db = UserConfig::getDB();

		$dailyregs = array();

		if ($stmt = $db->prepare('SELECT CAST(regtime AS DATE) AS regdate, count(*) AS regs FROM u_users WHERE regmodule = ? GROUP BY regdate')) {
			if (!$stmt->bind_param('s', $this->getID())) {
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute()) {
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($regdate, $regs)) {
				throw new DBBindResultException($db, $stmt);
			}

			while ($stmt->fetch() === TRUE) {
				$dailyregs[] = array('regdate' => $regdate, 'regs' => $regs);
			}

			$stmt->close();
		} else {
			throw new DBPrepareStmtException($db);
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

/**
 * Abstract authentication exception class for specific exceptions to subclass
 *
 * Comparing to regular exception, this one also stores a list of messages to be passed back to forms being rendered
 *
 * @package StartupAPI
 * @subpackage Authentication
 */
abstract class AuthenticationException extends Exception {

	/**
	 * An associative array of error messages
	 *
	 * Each form is free to define it's own keys, usually to indicate
	 * part of the form where error messages will be displayed.
	 *
	 * Values can also be linear arrays to allow for display of
	 * multiple messages in the same location.
	 *
	 * @var array
	 */
	protected $errors;

	/**
	 * Creates an exception accepting the list of error messages as 3rd parameter
	 *
	 * Example:
	 * <code>
	 * $errors['email'][] = "Using example domain";
	 * $errors['email'][] = "Verification email doesn't match";
	 * throw new InputValidationException('Email validation failed', 0, $errors);
	 * </code>
	 *
	 * @see InputValidationException
	 *
	 * @param string $string Exception message
	 * @param int $code Excepton code
	 * @param array $errors A list of error messages to be displayed to the user
	 */
	public function __construct($string, $code, $errors) {
		parent::__construct($string, $code);
		$this->errors = $errors;
	}

	/**
	 * Returns a list of error messages to display
	 *
	 * @return array an array of error messages
	 */
	public function getErrors() {
		return $this->errors;
	}

}

/**
 * Exception thrown when form fields didn't pass validation
 *
 * @package StartupAPI
 * @subpackage Authentication
 */
class InputValidationException extends AuthenticationException {

}

/**
 * Exception thrown when newly registered user already exists
 *
 * @package StartupAPI
 * @subpackage Authentication
 */
class ExistingUserException extends AuthenticationException {
	/**
	 * User that was found to already exist.
	 * Can be null if we just know that user exists, but did not get object specifically.
	 *
	 * @var User|null Existing user or null if we just know that user exists, but did not get object specifically.
	 */
	private $existing_user;
	public function __construct(User $user = null, $string = "User Alerady Exists", $code = 500, $errors = ["User Already Exists"]) {
		parent::__construct($string, $code, $errors);
		$this->existing_user = $user;
	}

	/**
	 * Returns existing user or null
	 * @return User|null Existing user or null if we just know that user exists, but did not get object specifically.
	 */
	public function getExistingUser() {
		return $this->existing_user;
	}
}

/**
 * Exception thrown when user account couldn't be created
 *
 * @todo Check if this is actually used or there is a valid use case for it.
 *
 * @package StartupAPI
 * @subpackage Authentication
 */
class UserCreationException extends AuthenticationException {

}

/**
 * Class representing user credentials for particular module
 * Must be subclassed and implemented by module
 *
 * @package StartupAPI
 * @subpackage Authentication
 */
abstract class UserCredentials {

	/**
	 * This method should return HTML representation of user credentials to be included in admin interface
	 * Usually linking to user's public profile at the source service
	 *
	 * @return string HTML representation of user credentials
	 */
	public abstract function getHTML();
}
