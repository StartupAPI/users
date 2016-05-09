<?php
namespace StartupAPI;

/**
 * StartupAPI module class
 *
 * Subclass it if you want to add a module/extension
 *
 * @package StartupAPI
 * @subpackage Extensions
 *
 * @todo Rename module IDs to module slugs throughout the application to confirm with coding standards
 */
abstract class StartupAPIModule implements StartupAPIModuleInterface {

	/**
	 * List of builtin module categories user in admin UI
	 *
	 * @var array
	 */
	public static $module_categories = array(
		'auth' => array(
			'title' => 'Authentication modules'
		),
		'email' => array(
			'title' => 'Email module'
		),
		'payment' => array(
			'title' => 'Payment engines'
		)
	);

	/**
	 * List of builtin modules used in admin UI and for module autoloading
	 *
	 * @var array
	 */
	public static $builtin_modules = array(
		'usernamepass' => array(
			'class' => 'UsernamePasswordAuthenticationModule',
			'category_slug' => 'auth'
		),
		'email' => array(
			'class' => 'EmailAuthenticationModule',
			'experimental' => true,
			'category_slug' => 'auth'
		),
		'facebook' => array(
			'class' => 'FacebookAuthenticationModule',
			'category_slug' => 'auth'
		),
		'twitter' => array(
			'class' => 'TwitterAuthenticationModule',
			'category_slug' => 'auth'
		),
		'google_oauth' => array(
			'class' => 'GoogleOAuthAuthenticationModule',
			'category_slug' => 'auth'
		),
		'linkedin' => array(
			'class' => 'LinkedInAuthenticationModule',
			'category_slug' => 'auth'
		),
		'meetup' => array(
			'class' => 'MeetupAuthenticationModule',
			'category_slug' => 'auth'
		),
		'etsy' => array(
			'class' => 'EtsyAuthenticationModule',
			'category_slug' => 'auth'
		),
		'foursquare' => array(
			'class' => 'FoursquareAuthenticationModule',
			'category_slug' => 'auth'
		),
		'github' => array(
			'class' => 'GithubAuthenticationModule',
			'category_slug' => 'auth'
		),
		'mailchimp' => array(
			'class' => 'MailChimpModule',
			'experimental' => true,
			'category_slug' => 'email'
		),
		'manual' => array(
			'class' => 'ManualPaymentEngine',
			'experimental' => true,
			'category_slug' => 'payment'
		),
		'external_payment' => array(
			'class' => 'ExternalPaymentEngine',
			'experimental' => true,
			'category_slug' => 'payment'
		),
		'stripe' => array(
			'class' => 'StripePaymentEngine',
			'experimental' => true,
			'category_slug' => 'payment'
		),
	);

	/**
	 * Creates new module and registers it with the system
	 */
	public function __construct() {
		UserConfig::$all_modules[] = $this;
	}

	/**
	 * Returns human readable module name
	 *
	 * Default implementation assumes title of instances is the same
	 * as title of the class of modules, override if not singleton
	 *
	 * @return string Module name
	 */
	public function getTitle() {
		$class = get_class($this);
		return $class::getModulesTitle();
	}

	/**
	 * Returns description HTML for a class of modules
	 *
	 * Usually different from module description, used when no modules are
	 * instantiated to entice people to install and instruct how to do so
	 *
	 * @return string Module description
	 */
	public static function getModulesDescription() {
		return null;
	}

	/**
	 * Returns module description HTML
	 *
	 * Usually different from description for a class of modules,
	 * used to describe installed and instantiated module (and how to use it)
	 *
	 * @return string Module description
	 */
	public function getDescription() {
		return null;
	}

	/**
	 * Returns URL of signup page for modules that use external providers
	 *
	 * @return string Signup page URL
	 */
	public static function getSignupURL() {
		return null;
	}

	/**
	 * Returns logo URL for a class of modules (if specified size of logo is available)
	 *
	 * @param int $size Size of the logo
	 *
	 * @return string Logo URL
	 */
	public static function getModulesLogo($size = 100) {
		return null;
	}

	/**
	 * Returns a logo URL for particular module
	 *
	 * Override it if you want custom logos per instance of module
	 * (rare, you will most likely not need it)
	 *
	 * @param int $size Size of the logo
	 *
	 * @return string Logo URL
	 */
	public function getLogo($size = 100) {
		$class = get_class($this);
		return $class::getModulesLogo($size);
	}

	/**
	 * Returns module by slug
	 *
	 * @param string $slug Module slug
	 *
	 * @return StartupAPIModule|null StartupAPIModule object or null if no such module is registered with the system
	 */
	public static function get($slug) {
		foreach (UserConfig::$all_modules as $module) {
			if ($module->getID() == $slug) {
				return $module;
			}
		}

		return null;
	}

}
