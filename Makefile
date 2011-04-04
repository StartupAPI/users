all:	updatecode updatedb

updatecode:
ifneq "$(wildcard .svn )" ""
	rm -rf dbupgrade oauth-php admin/swfobject modules/facebook/php-sdk
	svn update

	mkdir dbupgrade/
	svn export http://svn.github.com/sergeychernyshev/DBUpgrade.git _dbupgrade
	mv _dbupgrade/* dbupgrade/
	rm -rf _dbupgrade

	mkdir oauth-php
	svn export http://svn.github.com/sergeychernyshev/oauth-php.git _oauth-php
	mv _oauth-php/* oauth-php
	rm -rf _oauth-php

	mkdir admin/swfobject
	svn export http://svn.github.com/swfobject/swfobject.git _swfobject
	mv _swfobject/* admin/swfobject
	rm -rf _swfobject

	mkdir modules/facebook/php-sdk
	svn export http://svn.github.com/facebook/php-sdk.git _php-sdk
	mv _php-sdk/* modules/facebook/php-sdk
	rm -rf _php-sdk 
endif
ifneq "$(wildcard .git )" ""
	git pull origin master
	git submodule init
	git submodule update
endif

updatedb:
	php dbupgrade.php
	php aggregatepoints.php
