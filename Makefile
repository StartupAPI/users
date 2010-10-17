all:	dbupgrade pull updatedb

# until git will be able to update it's submodules automatically, we'll have to use make
dbupgrade:
	git submodule init
	git submodule update

pull:
	git pull

updatedb:
	php dbupgrade.php
