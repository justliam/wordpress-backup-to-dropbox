=== WordPress Backup to Dropbox ===
Contributors: michael.dewildt
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=38SEXDYP28CFA
Tags: backup, dropbox
Requires at least: 3.0
Tested up to: 3.4
Stable tag: trunk

Keep your valuable WordPress website, its media and database backed up to Dropbox in minutes with this sleek, easy to use plugin.

== Description ==

[WordPress Backup to Dropbox](http://wpb2d.com) has been created to give you piece of mind that your blog is backed up on a regular basis.

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

1. PHP 5.2 or higher

2. [A Dropbox account](https://www.dropbox.com/referrals/NTM1NTcwNjc5)

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

= Issues =

If you notice any bugs or want to request a feature please do so on GitHub - http://github.com/michaeldewildt/WordPress-Backup-to-Dropbox/issues

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

== Installation ==

1. Upload the contents of `wordpress-dropbox-backup.zip` to the `/wp-content/plugins/` directory or use WordPress' built-in plugin install tool
2. Once installed, you can access the plugins settings page under the new Backup menu
3. The first time you access the settings you will be prompted to authorize it with Dropbox

== Frequently Asked Questions ==

= How do I get a free Dropbox account? =

Browse to http://db.tt/szCyl7o and create a free account.

= Nothing seems to happen when backing up, whats up? =

Your server settings (.htaccess file) might be blocking wp-cron wich is required to start the backup process. Please refer to the following thread for information on to solve the issue - http://wordpress.org/support/topic/plugin-wordpress-backup-to-dropbox-nothing-seems-to-happen-when-backing-up

= Why doesn't my backup execute at the exact time I set? =

The backup is executed using WordPress' scheduling system that, unlike a cron job, kicks of tasks the next time your
blog is accessed after the scheduled time.

= Where is my database SQL dump located? =
The database is backed up into a file named '[database name]-backup.sql'. It will be found within the local backup location
you have set. Using the default settings the file will be found at the path 'WordPressBackups/wp-content/backups' within
your Dropbox.

= Can I perform a backup if my PHP installation has safe mode enabled? =
Yes you can, however you need to modify the max execution time in your php.ini manually.
[Please read this blog post for more information.](http://www.mikeyd.com.au/2011/05/24/setting-the-maximum-execution-time-when-php-is-running-in-safe-mode/)

= How can I revert to a previous version of a backed up file? =
Dropbox has this functionality built in and it is extremely easy to do.
[Please read this blog post for more information.](http://www.mikeyd.com.au/2011/06/05/restoring-previous-versions-of-files-in-dropbox/)

= Why does my backup keep stalling and restarting? =
Sometimes hosts implement measures to prevent long running tasks like a backup. To circumvent this I have implemented a backup monitor that restarts the backup if it is terminated before it is fully completed. So it is quite normal to see up to ten or more backup restarts.

= Why cant I see the exclude files and directories widget in Internet Explorer 7? =
That is because it only supports IE8 or higher or any of the awesome modern better alternatives like Google Chrome, Firefox,
Opera, etc. In order to use the widget you have no choice but to update to IE8 or any of the aforementioned browsers.

== Screenshots ==

1. WordPress Backup to Dropbox options
2. WordPress Backup to Dropbox monitor

== Changelog ==

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

* New more secure Dropbox App folder verson and bug fixes
