all:	dbupgrade pull updatedb

# until git will be able to update it's submodules automatically, we'll have to use make
dbupgrade: .svn .git

# if we don't have .git folder, let's assume we use SVN export
.git:
	svn export http://github.com/sergeychernyshev/DBUpgrade.git

# and vice versa
.svn:
	git submodule init
	git submodule update

pull:
	git pull

updatedb:
	php dbupgrade.php
