<?php
/**
 * Abstract class for newsletter management modules to subclass
 *
 * @package StartupAPI
 * @subpackage Email
 */
abstract class EmailModule extends StartupAPIModule {
	/**
	 * Registers newsletter management module with the system
	 *
	 * Only one module can be registered simultaneously
	 *
	 * @throws EmailModuleException
	 */
	public function __construct() {
		parent::__construct();

		if (!is_null(UserConfig::$email_module)) {
			throw new EmailModuleException("You can assign only one email module");
		}

		UserConfig::$email_module = $this;
	}

	/**
	 * This function should be called when new user is created
	 * or email is recorded for the user for the first time
	 *
	 * @param User $user User with newly registered email
	 */
	abstract public function registerSubscriber($user);

	/**
	 * This function should be called when user information has changed
	 * e.g. email address or additional information passed to provider like name or gender and etc.
	 *
	 * @param User $old_user User object representing old state of user information
	 * @param User $new_user User object representing new state of user information
	 */
	abstract public function updateSubscriber($old_user, $new_user);

	/**
	 * This function should be called when user chose to unsubscribe from the mailing list
	 *
	 * @param User $user User to remove subscription for
	 */
	abstract public function removeSubscriber($user);

	/**
	 * This method should be called by userChanged to decide if updateSubscriber needs to be called
	 * It's up to implementing class to decide if email provider needs to be updated
	 *
	 * @param User $old_user User object representing old state of user information
	 * @param User $new_user User object representing new state of user information
	 *
	 * @return boolean Returns true if user's information has changed and needs to be synced
	 */
	abstract public function hasUserInfoChanged($old_user, $new_user);

	/**
	 * This method will be called if some user info is changed
	 *
	 * @param User $old_user User object representing old state of user information
	 * @param User $new_user User object representing new state of user information
	 */
	public function userChanged($old_user, $new_user) {
		// submodule to decide if it needs to sync the user info
		$userInfoChanged = $this->hasUserInfoChanged($old_user, $new_user);

		if (($old_user->getEmail() != $new_user->getEmail()) || $userInfoChanged) {
			try {
				if (is_null($old_user->getEmail()) && !is_null($new_user->getEmail())) {
					// new subscriber - they just got an email address
					UserConfig::$email_module->registerSubscriber($new_user);
				} else if (!is_null($old_user->getEmail()) && is_null($new_user->getEmail())) {
					// delete subscriber - they no longer have email address with us
					UserConfig::$email_module->removeSubscriber($old_user);
				} else {
					// update subscriber info
					UserConfig::$email_module->updateSubscriber($old_user, $new_user);
				}
			} catch (EmailModuleException $e) {
				error_log($e."\n[User Info]: ".var_export($new_user, true));
			}
		}
	}
}

/**
 * Generic Email Module Exception
 *
 * @package StartupAPI
 * @subpackage Email
 */
class EmailModuleException extends Exception {}

/**
 * Exception thrown when there are subscription problems
 *
 * @package StartupAPI
 * @subpackage Email
 */
class EmailSubscriptionException extends EmailModuleException { }

/**
 * Exception thrown when there are problem with updating subscriber information
 *
 * @package StartupAPI
 * @subpackage Email
 */
class EmailSubscriberUpdateException extends EmailModuleException { }

/**
 * Exception thrown when there are problems with unsubscribing a user
 *
 * @package StartupAPI
 * @subpackage Email
 */
class EmailUnSubscriptionException extends EmailModuleException { }
