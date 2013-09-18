<?php
/**
 * This file contains the contents of the Dropbox admin options page.
 *
 * @copyright Copyright (C) 2011-2013 Michael De Wildt. All rights reserved.
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
wpb2d_style();

$uri = rtrim(WP_PLUGIN_URL, '/') . '/wordpress-backup-to-dropbox';
?>
<div class="wrap">
    <div class="icon32"><img width="36px" height="36px"  src="<?php echo $uri ?>/Images/WordPressBackupToDropbox_64.png" alt="Wordpress Backup to Dropbox Logo"></div>
    <h2><?php _e('WordPress Backup to Dropbox', 'wpbtd'); ?></h2>
    <p class="description"><?php printf(__('Version %s', 'wpbtd'), BACKUP_TO_DROPBOX_VERSION) ?></p>

    <?php include "wpb2d-$view.php"; ?>
</div>