all:	.svn .git updatedb

# if we don't have .git folder, let's assume we use SVN export
.git:
	svn update
	svn co http://svn.github.com/sergeychernyshev/DBUpgrade.git dbupgrade

# and vice versa
.svn:
	git pull
	git submodule init
	git submodule update

updatedb:
	php dbupgrade.php
