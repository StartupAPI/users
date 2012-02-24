<?php
interface ISubscriptionModule extends IUserBaseModule
{
	/**
	 * This function should be called when new user is created
	 */
	public function registerStartTariff($user);

	/**
	 * This function should be called when user information has changed
	 * e.g. email address or additional information passed to provider like name or gender and etc.
	 */
	public function updateTariff($old_user, $new_user);

}

abstract class SubscriptionModule extends UserBaseModule implements ISubscriptionModule {
	public function __construct() {
		parent::__construct();

		if (!is_null(UserConfig::$subscription_module)) {
			throw new SubscriptionModuleException("You can assign only one subscription module");
		}

		UserConfig::$subscription_module = $this;
	}
}

class SubscriptionModuleException extends Exception {}