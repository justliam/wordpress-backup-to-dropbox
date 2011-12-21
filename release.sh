#!/bin/sh
TRUNK='../../svn/wordpress-backup-to-dropbox/trunk/'
ARCHIVE='../../../releases/wordpress-backup-to-dropbox.zip'

cp *.txt $TRUNK
cp *.png $TRUNK
cp *.php $TRUNK
cp -r PEAR_Includes $TRUNK
cp -r Languages $TRUNK
cp -r JQueryFileTree $TRUNK
cp -r Dropbox_API $TRUNK

cd $TRUNK
zip -r $ARCHIVE * -x '*/.svn/*'