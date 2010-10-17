all:	.svn .git updatedb

# if we don't have .git folder, let's assume we use SVN export
.git:
	rm -rf dbupgrade
	svn update
	mkdir dbupgrade/
	svn export http://svn.github.com/sergeychernyshev/DBUpgrade.git _dbupgrade
	mv _dbupgrade/* dbupgrade/
	rm -rf _dbupgrade

# and vice versa
.svn:
	git pull
	git submodule init
	git submodule update

updatedb:
	php dbupgrade.php
