<?php
interface IEmailModule extends IUserBaseModule
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
	public function updateSubscriber($user);

	/**
	 * This function should be called when user chose to unsubscribe from the mailing list
	 */
	public function removeSubscriber($user);
}

abstract class EmailModule extends UserBaseModule implements IEmailModule {
	public function __construct() {
		parent::__construct();

		if (!is_null(UserConfig::$email_module)) {
			throw new EmailModuleException("You can assign only one email module");
		}

		UserConfig::$email_module = $this;
	}
}

class EmailModuleException extends Exception {}

class EmailSubscriptionException extends Exception { }

class EmailSubscriberUpdateException extends Exception { }

class EmailUnSubscriptionException extends Exception { }
