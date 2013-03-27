#!/bin/sh
TRUNK=$HOME/Projects/wpb2d/svn/wordpress-backup-to-dropbox/trunk/
ARCHIVE=$HOME/Projects/wpb2d/releases/wordpress-backup-to-dropbox.zip

cp *.txt $TRUNK
cp *.png $TRUNK
cp *.php $TRUNK
cp *.js $TRUNK
cp *.css $TRUNK
cp -r Languages $TRUNK
cp -r JQueryFileTree $TRUNK
cp -r Dropbox/Dropbox $TRUNK/Dropbox/
cp -r Classes $TRUNK
cp -r Views $TRUNK
cp -r Images/WordPressBackupToDropbox_16.png $TRUNK/Images/
cp -r Images/guarantee.gif $TRUNK/Images/
cp -r Images/banner-772x250.png $TRUNK/../assets/

cd $TRUNK
zip -r $ARCHIVE * -x '*/.svn/*'