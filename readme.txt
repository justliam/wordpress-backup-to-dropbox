=== WordPress Backup to Dropbox ===
Contributors: michael.dewildt
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=38SEXDYP28CFA
Tags: backup, dropbox
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: trunk

Keep your valuable WordPress website, its media and database backed up to Dropbox in minutes with this sleek, easy to use plugin.

== Description ==

[WordPress Backup to Dropbox](http://wpb2d.com) has been created to give you peace of mind that your blog is backed up on a regular basis.

Just choose a day, time and how often you wish yor backup to be performed and kick back and wait for your websites files
and a SQL dump of its database to be dropped in your Dropbox!

You can set where you want your backup stored within Dropbox and on your server as well as choose what files or directories,
if any, you wish to exclude from the backup.

The plugin uses [OAuth](http://en.wikipedia.org/wiki/OAuth) so your Dropbox account details are not stored for the
plugin to gain access.

Checkout the website - http://wpb2d.com

= Setup =

Once installed, the authorization process is easy -

1. When you first access the plugin’s options page, it will ask you to authorize the plugin with Dropbox.

2. A new window will open and Dropbox will ask you to authenticate and grant the plugin access.

3. Finally, click continue to setup your backup.

= Minimum Requirements =

1. PHP 5.2.16 or higher with [cURL support](http://www.php.net/manual/en/curl.installation.php)

2. [A Dropbox account](https://www.dropbox.com/referrals/NTM1NTcwNjc5)

Note: Version 1.3 of the plugin supports PHP < 5.2.16 and can be [downloaded here.](http://downloads.wordpress.org/plugin/wordpress-backup-to-dropbox.1.3.zip)

= Errors and Warnings =

During the backup process the plugin may experience problems that will be raised as an error or a warning depending on
its severity.

A warning will be raised if your PHP installation is running in safe mode, if you get this warning please read my blog
post on dealing with this.

If the backup encounters a file that is larger then what can be safely handheld within the memory limit of your PHP
installation, or the file fails to upload to Dropbox it will be skipped and a warning will be raised.

The plugin attempts to recover from an error that may occur during a backup where backup process goes away for an unknown
reason. In this case the backup will be restarted from where it left off. Unfortunately, at this time, it cannot recover
from other errors, however a message should be displayed informing you of the reason for failure.

= Premium Extensions =

Premium extensions are downloadable snippets of code that add extra functionality to WordPress Backup to Dropbox. The extensions are features have been requested but may not appeal to all users. Instead of complicating the plugin by adding them to the core, premium extensions allows you to choose what extra functionality you want.

Premium extensions can be purchased securely using [PayPal](http://www.paypal.com) and installed with the click of a button. For more information pelase visit http://wpb2d.com/premium.

= More Information =

For news and updates please visit my blog - http://www.mikeyd.com.au/category/wordpress-backup-to-dropbox/

= Translators =

* Arabic (ar) - [Saif Maki](www.saif.cz.cc)
* Brazilian Portuguese (pt_BR) - [Techload Informatica](http://www.techload.com.br)
* Galician (gl_ES), Spanish (es_ES), Portuguese (pt_PT) - [WordPress Galego](http://gl.wordpress.org/)
* Indonesian (id_ID) - [Bejana](http://www.bejana.com/)
* German (de_DE) - [Bernhard Kau](http://kau-boys.de)
* Chinese (zh_CN) - [HostUCan CN](http://www.hostucan.cn/)
* Taiwanese (zh_TN) - [HostUCan](http://www.hostucan.com/)
* French (fr_FR) - [Yassine HANINI](http://www.yassine-hanini.info/)
* Persian (fa_IR) - [Reza-sh](http://www.rezaonline.net/blog)
* Dutch (nl_NL) - [Rinze Hiddink](http://www.rinzehiddink.nl)
* Hebrew (he_HE) - [Menachem](http://luckyboost.com)
* Italian (it_IT) - [René Querin](http://q-design.it)
* Hungarian (hu_HU) - [Lazarevics](http://hardverborze.tk)
* Russian (ru_RU) - [Evgeny Vlasov](http://verha.net)

== Installation ==

1. Upload the contents of `wordpress-dropbox-backup.zip` to the `/wp-content/plugins/` directory or use WordPress' built-in plugin install tool
2. Once installed, you can access the plugins settings page under the new Backup menu
3. The first time you access the settings you will be prompted to authorize it with Dropbox

== Frequently Asked Questions ==

= Why won’t my backup start? =

To diagnose issues with your plugin please refer to this [sticky topic](http://wordpress.org/support/topic/why-won%E2%80%99t-my-backup-start).

= How do I restore my website? =

Simply download [WPB2D Simple Restore](http://wpb2d.com/simple-restore) and upload it to an empty host. Once uploaded you will be guided through a wizard of 5 easy steps and have your blog restored in no time.

You don’t even have to install WordPress!

= Why does my keep going away and resuming? =

By default PHP has its time limit set to 30 seconds. The plugin will attempt to set the time limit to unlimited in order to complete the backup, however if [safe mode](http://php.net/manual/en/features.safe-mode.php) is enabled this will not be possible.

In addition the Apache [TimeOut](http://httpd.apache.org/docs/2.2/mod/core.html#TimeOut) directive has a default of 300 seconds (5 minutes) that cannot be altered without manual intervention. It is not recommended you change this value.

However, the plugin has been designed to get around these limitations by using a backup monitor that will detect if the backup has gone away and resume it from where it stopped.

In short, this is a feature! :-)

= Where are my database backup files located? =

The database is backed up into two files named '[database name]-backup-core.sql' and '[database name]-backup-plugins.sql'. These files will be will be found at the path 'wp-content/backups' within the App folder of your Dropbox.

The first file contains all the core WordPress tables and data and the second contains tables and data related to your plugins. Sometimes your second file will not have any data in it because most plugins will store their data in the wp_options table.

= Wow! My second backup was heaps faster. Why is that? =

In order to save time and bandwidth the plugin only uploads files that have changed since the last backup. The only exception is your SQL dump file that will be uploaded every time.

= How can I revert to a previous version of a backed up file? =

Dropbox has this functionality built in and it is extremely easy to do, please checkout [this blog post](http://www.mikeyd.com.au/2011/06/05/restoring-previous-versions-of-files-in-dropbox) for more information.

You can also install the zip [premium extension](http://wpb2d.com/premium) that will zip up all your files, including the SQL dumps, before uploading it to Dropbox allowing you to store multiple backups.

== Screenshots ==

1. The Settings: Choose the date, time, frequency and what files or directories you wan't to exclude.
2. The Log: Know what you backup is doing.
3. Premium Extensions: Add extra functionality with ease and a 60 day money back guarantee.

== Changelog ==

= 1.4.5 =
* Added support for multi site
* Added support for running WordPress in its own directory. http://codex.wordpress.org/Giving_WordPress_Its_Own_Directory
* Added support for an alternate WP_CONTENT_DIR
* Fixed an issue where windows servers where uploading with incorrect slashes
* Fixed an issue where diretories where being marked as partial when they had no excluded files
* Fixed a memory leak in the exclude file widget

= 1.4.4 =
* Attempt to set the memory limit WP_MAX_MEMORY_LIMIT and have a better go at setting the time limit
* Added .dropbox to the ignored files list as Dropbox does not accept it
* Added retry logic for normal uploads that receive errors
* Updated the Dropbox API lib that includes retries for chunked uploads
* Fixed a minor potential XSS issue when viewing the backup log, thanks Mahadev Subedi (@blinkms) for the heads up

= 1.4.3 =
* Fixed issue where autorise link was invalid
* Fixed session has time out issue
* Added error message for users who's server has not connection to the internet
* Added depricated page for people using PHP < 5.2.16
* Added priority support premium extension

= 1.4.2 =
* Fixed the uninstaller
* Fixed issue where files over 10mb where not being uploaded in their correct directories
* FIxed a fatal error on a corrupt processed files list

= 1.4.1 =
* Fixed exclude widget checkbox css position
* Fixed issue where all files where being uploaded in subsequent backups
* Fixed cannot access empty property fatal error
* Moved the safe mode warning out of the settings page to the backup log
* The backup log now logs to a file in 'wp-content/backups' that is uploaded to Dropbox at the end of a backup
* Allow for multiple emails in the email extension
* Allow for sub folders in the store in subfolder setting

= 1.4 =

* Implemented a brand new Dropbox API library that utilises chunked uploads for large files.
* Updated the Dutch translations.
* Added the umsak funciton to attempt to run the backup under elevated privileges.
* Set the memory limit to -1 (unlimited) for servers that allow it, the backup will still only use what it needs.
* Added 'unknown%' to the backup estimation instead of the initial estimate to avoid confusion.
* Added a safe mode warning to the settings page so that users can diagnose fix issues related to PHP memory and time limits
* Set the mysql wait time out at the start of a backup
* The file exclude widget has been updated to toggle excluded files better thanks to [Joe Maller](http://github.com/joemaller). In addition ticks have been replaced with crosses to better portray its an exclude function.
* Fixed some minor issues in the OAuth flow.
* Fixed an issue where options will not update due to validation of an option from an older version. This affected starting and stoping of backups, updating email adresses and other options in certain circumstances.
* Fixed up the backup time estimation so it cannot be set to zero or an impossibly low number.
* Fixed memory limit issues in the file list by adding a max directory depth of 10
* Prefixed save actions with wpb2d to avoid clashes with other plugins
* Fixed an issue where WP option cache was interferring with stopping backups

= 1.3 =
* Overhauled logging of a backup to get more visibility of what is happening during a backup.
* More info here => http://www.mikeyd.com.au/2012/10/04/wordpress-backup-to-dropbox-1-3/

= 1.2.2 =
* Removed zipping of the SQL dump due to random PHP memory leaks... intersingly the Zip extension does not share the same issue. Ah PHP you keep me on my toes!

= 1.2.1 =
* Fixed random unlinking issue... again
* Added zipping of the SQL dump file

= 1.2 =
* Reuduced directory nesting to one subfolder and fixed up error message
* Fixed issues where accounts where being incorrectly unlinked
* Added singletons for better performance
* Fixed issue #63 Out of memory in settings page - display error in exclude widget if memory is too low
* Fixed issue #64 UnexpectedValueException
* WordPress core and plugin database tables are now backed up separately

= 1.1 =
* Updated the Dropbox PHP API to fix various issues processing some files
* Un readable directories or files are now skipped instead of causing an exception
* Added Hungarian language
* The backup now attempts to set the memory limit to 256M before backup
* Removed the option to set the temp backup dir for simpicity
* Migrated to Dropbox App Folder mode for added security of your Dropbox account
* Fixed exclude widet issues on Windows Server
* See http://www.mikeyd.com.au/2012/06/20/wordpress-backup-to-dropbox-1-1 for details

= 1.0 =
* Removed backup has gone away warning that seems to be confusing users
* Made a whole bunch of perfomrance improvmentws
* Added premium extensions
* Tested with WordPress 3.4
* See http://www.mikeyd.com.au/2012/06/17/wordpress-backup-to-dropbox-1-0 for details

== Upgrade Notice ==

* After every update make sure that you check that your settings are still correct and run a test backup.
