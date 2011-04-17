# UserBase

UserBase is a drop-in user management tool for Saas (Software As A Service), or simply put, online business sites.

It supports many registration and authentication methods, integrates with various useful modules like newsletters and provides comprehensive administrative dashboard that will help you to make business decisions.

The goal of the project is to allow fast idea-to-business cycle so we all can concentrate on know-how and not the infrastracture.

## Installation

The goal for the project is to simplify installation and upgrades as much as possible. Let us know where you experience problems and we'll try to reduce the friction.

Follow these steps:

1. Get the code from GitHub into the root of your site under users folder:

		git clone git://github.com/sergeychernyshev/UserBase.git users

2. If you don't have it yet, create MySQL database and get user credentials for it

3. Copy sample configuration file to the same folder where you created users folder in step 1:

		cp users/users_config.sample.php users_config.php

3. Fill out users_config.php - as minimum, set DB credentials and seed the secret for cookie encryption

		UserConfig::$SESSION_SECRET= '...some.random.characters.go.here...';
		UserConfig::setDB(new mysqli( 'localhost', '...username...', '...password...', '...database...'));

4. Run make to generate database tables and other files required by the different parts of UserBase

		make

5. Uncomment and configure API keys for more authentication modules for Facebook, Twitter, Google and etc.

6. In your code, use either User::get() function to get a user object

		/**
		 * Get User object or null if user is not logged in
		 */
		$current_user = User::get();

   or if you want to protect the page from anonymous users, use User::require_login() function

		/**
		 * Get User object or redirect to login page if user is not logged in
		 */
		$current_user = User::require_login();

7. <s>Sit back and relax</s> Go implement the business logic now. You can call getID(), getName() and other methods on the user object to utilize it in your code.

## Additional features

When you're comfortable with basic features and want to explore more, go check the documentation (TODO [write the documentation](https://github.com/sergeychernyshev/UserBase/issues/46)) for more advanced features like embedding registration / login forms on your pages, activity tracking, feature management and so on.

## Problems and Questions

If you have any problems with installations check out the [list of issues/tasks](https://github.com/sergeychernyshev/UserBase/issues) or let us know about new one.
