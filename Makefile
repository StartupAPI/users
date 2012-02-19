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

	rm -rf _php-sdk
	mkdir modules/facebook/php-sdk
	git clone git://github.com/facebook/php-sdk.git _php-sdk
	( cd _php-sdk; git archive v2.1.2 | tar -x -C ../modules/facebook/php-sdk )
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

rel:	release
release: releasetag packages

releasetag:
ifndef v
	# Must specify version as 'v' param
	#
	#   make rel v=1.1.1
	#
else
	#
	# Tagging it with release tag
	#
	git tag -a REL_${subst .,_,${v}}
	git push --tags
endif

packages:
ifndef v
	# Must specify version as 'v' param
	#
	#   make rel v=1.1.1
	#
else
	mkdir StartupAPI_${v}

	# generate the package
	git clone . StartupAPI_${v}/users
	cd StartupAPI_${v}/users/ && git checkout REL_${subst .,_,${v}}
	cd StartupAPI_${v}/users/ && ${MAKE} updatecode
	cd StartupAPI_${v}/users/ && find ./ -name "\.git*" | xargs -n10 rm -r

	tar -c StartupAPI_${v}/ |bzip2 > StartupAPI_${v}.tar.bz2
	zip -r StartupAPI_${v}.zip StartupAPI_${v}
	rm -rf StartupAPI_${v}
endif

docs:	documentation
documentation:
	phpdoc -o HTML:frames:default -d . -t docs -i "*/oauth-php/*,*/modules/facebook/php-sdk/*,*/dbupgrade/*,*/admin/swfobject/*,*/docs/*"
