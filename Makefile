all:	updatecode depcheck updatedb success

depcheck:
	php depcheck.php

success:
	@echo "[*** SUCCESS ***] Installation and upgrade of Startup API completed successfully"

updatecode:
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
