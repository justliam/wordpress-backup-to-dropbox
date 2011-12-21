#!/bin/sh
TRUNK='../../svn/wordpress-backup-to-dropbox/trunk/'
ARCHIVE='../../../releases/wordpress-backup-to-dropbox.zip'

cp *.php $TRUNK
cp -R PEAR_Includes $TRUNK
cp -R Languages $TRUNK
cp -R JQueryFileTree $TRUNK
cp -R Dropbox_API $TRUNK

cd $TRUNK
zip $ARCHIVE *