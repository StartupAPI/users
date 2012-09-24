<?php
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
	 * @param string $action Action URL the form should submit data to
	 */
	abstract public function renderLoginForm($action);

	/**
	 * Renders registration form HTML
	 *
	 * Implementations of this method must render registration form
	 *
	 * @param boolean $full Whatever or not to display a short version of the form or full
	 * @param string $action Action URL the form should submit data to
	 * @param array $errors An array of error messages to be displayed to the user on error
	 * @param array $data An array of data passed by a form on previous submission to display back to user
	 */
	abstract public function renderRegistrationForm($full = false, $action = null, $errors = null, $data = null);

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
	 */
	abstract public function processRegistration($data, &$remember);

	/**
	 * Returns user credentials
	 *
	 * Implementations of this method must return an instance of UserCredention object or it's subclass
	 *
	 * @param User $user User to get credentials for
	 * @return UserCredentials User credentials object specific to the module
	 */
	abstract public function getUserCredentials($user);

	/**
	 * Returns a number of users connected using this module
	 *
	 * Implementations of this module must returns total number of users connected with provider
	 *
	 * @return int Number of users who have connections through this provider
	 *	       Some modules might allow for multiple connections, but the user is only counted once
	 */
	abstract public function getTotalConnectedUsers();

	/**
	 * Returns authentication module by ID
	 *
	 * @param string $id ID of the module
	 * @return AuthenticationModule Authentication module object
	 */
	public static function get($id) {
		foreach (UserConfig::$authentication_modules as $module)
		{
			if ($module->getID() == $id) {
				return $module;
			}
		}
	}

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
	 */
	public function renderAutoLogoutForm() {
		// by default modules do not support auto-logout
	}

	/**
	 * Retrieves aggregated registration numbers
	 *
	 * @return array Array of date => count pairs for all dates that have users registered on that date
	 */
	public function getDailyRegistrations()
	{
		$db = UserConfig::getDB();

		$dailyregs = array();

		if ($stmt = $db->prepare('SELECT CAST(regtime AS DATE) AS regdate, count(*) AS regs FROM '.UserConfig::$mysql_prefix.'users WHERE regmodule = ? GROUP BY regdate'))
		{
			if (!$stmt->bind_param('s', $this->getID()))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($regdate, $regs))
			{
				throw new DBBindResultException($db, $stmt);
			}

			while($stmt->fetch() === TRUE)
			{
				$dailyregs[] = array('regdate' => $regdate, 'regs' => $regs);
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return $dailyregs;
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
	public function __construct($string, $code, $errors)
	{
		parent::__construct($string, $code);
		$this->errors = $errors;
	}

	/**
	 * Returns a list of error messages to display
	 *
	 * @return array an array of error messages
	 */
	public function getErrors()
	{
		return $this->errors;
	}
}

/**
 * Exception thrown when form fields didn't pass validation
 *
 * @package StartupAPI
 * @subpackage Authentication
 */
class InputValidationException extends AuthenticationException {}

/**
 * Exception thrown when newly registered user already exists
 *
 * @package StartupAPI
 * @subpackage Authentication
 */
class ExistingUserException extends AuthenticationException {
}

/**
 * Exception thrown when user account couldn't be created
 *
 * @todo Check if this is actually used or there is a valid use case for it.
 *
 * @package StartupAPI
 * @subpackage Authentication
 */
class UserCreationException extends AuthenticationException {}

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
