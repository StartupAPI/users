all:	updatecode updatedb

updatecode:
ifneq "$(wildcard .svn )" ""
	rm -rf dbupgrade
	svn update
	mkdir dbupgrade/
	svn export http://svn.github.com/sergeychernyshev/DBUpgrade.git _dbupgrade
	mv _dbupgrade/* dbupgrade/
	rm -rf _dbupgrade
endif
ifneq "$(wildcard .git )" ""
	git pull origin master
	git submodule init
	git submodule update
endif

updatedb:
	php dbupgrade.php
	php aggregatepoints.php
