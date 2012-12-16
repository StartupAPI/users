all:	updatecode depcheck updatedb

depcheck:
	php depcheck.php

updatecode:
ifneq "$(wildcard .svn )" ""
	rm -rf dbupgrade oauth-php admin/swfobject modules/facebook/facebook-php-sdk
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
	mkdir modules/facebook/facebook-php-sdk
	git clone git://github.com/facebook/facebook-php-sdk.git _php-sdk
	( cd _php-sdk; git archive HEAD | tar -x -C ../modules/facebook/facebook-php-sdk )
	rm -rf _php-sdk

	rm -rf twig 
	mkdir twig 
	svn export git://github.com/fabpot/Twig.git _twig
	mv _twig/* twig/
	rm -rf _twig
endif
ifneq "$(wildcard .git )" ""
	git pull origin master
	git submodule init
	git submodule update
endif

checkconfig:
ifeq ($(wildcard ../users_config.php),)
	$(error "Can't find ../users_config.php in parent folder. Create it first by copying users_config.sample.php and edit it")
else
	@echo Found configuration file ../users_config.php
endif

db:	updatedb
updatedb: checkconfig
	php dbupgrade.php
	php aggregatepoints.php

rel:	release
release: releasetag packages

releasetag:
ifndef v
	#
	#   make rel v=1.1.1
	#
	$(error You must specify version number in 'v' parameter: make release v=1.1.1)
else
	#
	# Tagging it with release tag
	#
	git tag -a REL_${subst .,_,${v}}
	git push --tags
endif

packages:
ifndef v
	#
	#   make packages v=1.1.1
	#
	$(error You must specify version number in 'v' parameter: make packages v=1.1.1)
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
documentation: phpdoc apigen

phpdoc:
	# Using PHPDocumentor which wirks with phpdocx.dist.xml
	phpdoc

apigen:
	# Using ApiGen which works with apigen.neon file for configuration
	apigen

code:
	php phptidy/phptidy.php replace
	find . -name '*.phptidybak~' | xargs -n10 rm
