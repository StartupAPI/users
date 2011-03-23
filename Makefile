all:	updatecode updatedb

updatecode:
ifneq "$(wildcard .svn )" ""
	rm -rf dbupgrade oauth-php admin/swfobject
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
endif
ifneq "$(wildcard .git )" ""
	git pull origin master
	git submodule init
	git submodule update
endif

updatedb:
	php dbupgrade.php
	php aggregatepoints.php
