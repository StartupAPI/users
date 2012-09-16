<?php
/**
 * @package StartupAPI
 * @subpackage Email
 */
interface IEmailModule extends IStartupAPIModule
{
	/**
	 * This function should be called when new user is created
	 * or email is recorded for the user for the first time
	 */
	public function registerSubscriber($user);

	/**
	 * This function should be called when user information has changed
	 * e.g. email address or additional information passed to provider like name or gender and etc.
	 */
	public function updateSubscriber($old_user, $new_user);

	/**
	 * This function should be called when user chose to unsubscribe from the mailing list
	 */
	public function removeSubscriber($user);

	/**
	 * This method will be called if some user info is changed
	 */
	public function userChanged($old_user, $new_user);

	/**
	 * This method should be called by userChanged to decide if updateSubscriber needs to be called
	 * It's up to implementing class to decide if email provider needs to be updated
	 *
	 * @return boolean Returns true if user's information has changed and needs to be synced
	 */
	public function hasUserInfoChanged($old_user, $new_user);
}

/**
 * @package StartupAPI
 * @subpackage Email
 */
abstract class EmailModule extends StartupAPIModule implements IEmailModule {
	public function __construct() {
		parent::__construct();

		if (!is_null(UserConfig::$email_module)) {
			throw new EmailModuleException("You can assign only one email module");
		}

		UserConfig::$email_module = $this;
	}

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
 * @package StartupAPI
 * @subpackage Email
 */

class EmailModuleException extends Exception {}

/**
 * @package StartupAPI
 * @subpackage Email
 */
class EmailSubscriptionException extends EmailModuleException { }

/**
 * @package StartupAPI
 * @subpackage Email
 */
class EmailSubscriberUpdateException extends EmailModuleException { }

/**
 * @package StartupAPI
 * @subpackage Email
 */
class EmailUnSubscriptionException extends EmailModuleException { }
