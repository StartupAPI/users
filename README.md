# Startup API

Startup API is a drop-in user management tool for on-line projects and SaaS (Software As A Service) web sites.

It supports many registration and authentication methods, integrates with various useful services like newsletters and provides comprehensive administrative dashboard that helps make business decisions based on data collected from the users.

One of main goals for the project is to allow fast idea-to-product cycle so we all can concentrate on know-how and not the infrastructure.

It is distributed under [MIT license](http://opensource.org/licenses/MIT) distributed with the code in [LICENSE](LICENSE) file.

## Installation

Another important goal for the project are simple installation and upgrades. Let us know where you experience problems and we'll try to reduce the friction.

Follow these steps:

1. Get the code from GitHub into the root of your site under users folder:
```
	git clone git://github.com/StartupAPI/users.git users
```
	or you can just download [latest package](https://github.com/StartupAPI/users/downloads) from downloads section

2. If you don't have it yet, create MySQL database and get user credentials for it

3. Copy sample configuration file to the same folder where you created users folder in step 1:
```
	cp users/users_config.sample.php users_config.php
```
4. Fill out `users_config.php` - as minimum, seed the secret for cookie encryption and set DB credentials
```php
	<?php
	/**
	 * You must fill it in with some random string
	 * this protects some of your user's data when sent over the network
	 * and must be different from other sites
	 */
	UserConfig::$SESSION_SECRET= '...type.some.random.characters.here...';
	
	/**
	 * Database connectivity
	 */
	UserConfig::$mysql_db = '...database...';
	UserConfig::$mysql_user = '...username...';
	UserConfig::$mysql_password = '...password...';
	#UserConfig::$mysql_host = 'localhost';
	#UserConfig::$mysql_port = 3306;
```
5. Run make to generate database tables and other submodules and files required by the different parts of Startup API.
```
	make
```
6. Uncomment and configure API keys for more authentication modules for Facebook, Twitter, Google and etc.

7. Start building your app by copying code from [`sample.php`](https://github.com/StartupAPI/users/blob/master/sample.php). Basically, you'll need to include `users.php` above any other output
```php
	<?php
	require_once(dirname(__FILE__).'/users/users.php');
```
	and then use either `User::get()` static method to get a user object
```php
	<?php
	/**
	 * Get User object or null if user is not logged in
	 */
	$current_user = User::get();
```
	or if you want to protect the page from anonymous users, use `User::require_login()` instead - this will automatically redirect to a login form and back to yout page after user successfully logged in or registered.
```php
	<?php
	/**
	 * Get User object or redirect to login page if user is not logged in
	 */
	$current_user = User::require_login();
```
	you can get numeric user ID to use in your data model by calling `getID()` method
```php
	<?php
	/**
	 * Get user's unique ID
	 */
	$user_id = $current_user->getID();
```
	You will most likely want to show a login menu in the top-right corner of your page. You can do it by simply including navbox.php file like so:
```php
	<div style="float: right"><?php include(dirname(__FILE__).'/users/navbox.php'); ?></div>
```

8. <del>Sit back and relax</del> Go implement your business logic now. You can call `getID()`, `getName()` and other methods on the user object to utilize it in your code.

## Upgrading

As usual, make a backup of the database to avoid loosing data in case of disasters.

Then just run make - it should grab the latest code and run database update scripts to bring schema up to date with the code.
```
make
```
## Additional features

When you're comfortable with basic features and want to explore more, go check the documentation (TODO [write the documentation](https://github.com/StartupAPI/users/issues/46)) for more advanced features like embedding registration / login forms on your pages, activity tracking, feature management and so on.

## Problems and Questions

If you have any problems with installations check out the [list of issues/tasks](https://github.com/StartupAPI/users/issues) or let us know about new one.
