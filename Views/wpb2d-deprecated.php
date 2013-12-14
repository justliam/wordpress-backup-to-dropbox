<?php
/**
 * This file contains the contents of the Dropbox admin options page.
 *
 * @copyright Copyright (C) 2011-2014 Awesoft Pty. Ltd. All rights reserved.
 * @author Michael De Wildt (http://www.mikeyd.com.au/)
 * @license This program is free software; you can redistribute it and/or modify
 *          it under the terms of the GNU General Public License as published by
 *          the Free Software Foundation; either version 2 of the License, or
 *          (at your option) any later version.
 *
 *          This program is distributed in the hope that it will be useful,
 *          but WITHOUT ANY WARRANTY; without even the implied warranty of
 *          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *          GNU General Public License for more details.
 *
 *          You should have received a copy of the GNU General Public License
 *          along with this program; if not, write to the Free Software
 *          Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110, USA.
 */
$v = phpversion();
if ($pos = strpos($v, '-'))
    $v = substr($v, 0, $pos);
?>
<div class="wrap" id="wpb2d">
    <div class="icon32"><img width="36px" height="36px"	 src="<?php echo $uri ?>/Images/WordPressBackupToDropbox_64.png" alt="Wordpress Backup to Dropbox Logo"></div>
    <h2><?php _e('WordPress Backup to Dropbox', 'wpbtd'); ?></h2>
    <p class="description"><?php printf(__('Version %s', 'wpbtd'), BACKUP_TO_DROPBOX_VERSION) ?></p>
    <p>
        <?php _e(sprintf('
            <p>Gday,</p>
            <p>WordPress Backup to Dropbox is striving to be the #1 backup solution for WordPress and, in order to do so, it needs to use the latest technologies available.</p>
            <p>So, unfortunately your version of PHP (%s) is below version 5.2.16 that is the minimum required version to perform a reliable and successful backup.
            It is <em>STRONGLY</em> recommended that you upgrade to PHP 5.3 or higher because, <a href="%s">as of December 2010</a>, version 5.2 is no longer supported by the PHP community.
            Or, alternatively PHP >= 5.2.16.
            <p>If this is not possible, WPB2D 1.3 supports PHP < 5.2.16 and can be <a href="%s">downloaded here</a> and installed using the WordPress plugin uploader.
            Although this version works 100%%, and has the same premium extensions, it will only be supported with bug fix releases.</p>
            <p>Cheers,<br />Mikey</p>
            ',
            $v,
            'http://www.php.net/archive/2010.php#id2010-12-16-1',
            'http://downloads.wordpress.org/plugin/wordpress-backup-to-dropbox.1.3.zip'
        ), 'wpbtd'); ?>
    </p>
</div>
