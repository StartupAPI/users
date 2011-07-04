# UserBase

UserBase is a drop-in user management tool for on-line projects and SaaS (Software As A Service) web sites.

It supports many registration and authentication methods, integrates with various useful services like newsletters and provides comprehensive administrative dashboard that helps make business decisions based on data collected from the users.

One of main goals for the project is to allow fast idea-to-product cycle so we all can concentrate on know-how and not the infrastracture.

## Installation

Another important goal for the project are simple installation and upgrades. Let us know where you experience problems and we'll try to reduce the friction.

Follow these steps:

1. Get the code from GitHub into the root of your site under users folder:

		git clone git://github.com/sergeychernyshev/UserBase.git users

2. If you don't have it yet, create MySQL database and get user credentials for it

3. Copy sample configuration file to the same folder where you created users folder in step 1:

		cp users/users_config.sample.php users_config.php

3. Fill out users_config.php - as minimum, set DB credentials and seed the secret for cookie encryption

		UserConfig::$SESSION_SECRET= '...some.random.characters.go.here...';

		UserConfig::$mysql_host = 'localhost';
		UserConfig::$mysql_db = '...database...';
		UserConfig::$mysql_user = '...username...';
		UserConfig::$mysql_password = '...password...';

4. Run make to generate database tables and other submodules and files required by the different parts of UserBase

		make

5. Uncomment and configure API keys for more authentication modules for Facebook, Twitter, Google and etc.

6. In your code, add include users.php file above any other output

		require_once(dirname(__FILE__).'/users/users.php');

then use either User::get() function to get a user object

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

## Upgrading

As usual, make a backup of the database to avoid loosing data in case of disasters.

Then just run make - it should grab the latest code and run database update scripts to bring schema up to date with the code.

	make

## Additional features

When you're comfortable with basic features and want to explore more, go check the documentation (TODO [write the documentation](https://github.com/sergeychernyshev/UserBase/issues/46)) for more advanced features like embedding registration / login forms on your pages, activity tracking, feature management and so on.

## Problems and Questions

If you have any problems with installations check out the [list of issues/tasks](https://github.com/sergeychernyshev/UserBase/issues) or let us know about new one.
