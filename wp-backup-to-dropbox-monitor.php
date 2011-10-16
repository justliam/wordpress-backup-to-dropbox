<?php
/**
 * This file contains the contents of the Dropbox admin monitor page.
 *
 * @copyright Copyright (C) 2011 Michael De Wildt. All rights reserved.
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
global $wpdb;
$backup = new WP_Backup( null, $wpdb );
if ( !$backup->in_progress() ) {
	$backup->backup_now();
}
?>
<script type="text/javascript" language="javascript">
	function reload() {
		jQuery('#progress').load(ajaxurl, { action:'progress' });
		setTimeout("reload()",1000);
	}
    jQuery(document).ready(function ($) {
		reload();
    });
</script>
<div class="wrap">
	<div class="icon32"><img width="36px" height="36px"
								 src="<?php echo $uri ?>/Images/WordPressBackupToDropbox_64.png"
								 alt="Wordpress Backup to Dropbox Logo"></div>
	<h2><?php _e( 'WordPress Backup to Dropbox', 'wpbtd' ); ?></h2>
	<p class="description"><?php printf( __( 'Version %s', 'wpbtd' ), BACKUP_TO_DROPBOX_VERSION ) ?></p>
	<h3><?php _e( 'Backup Progress', 'wpbtd' ); ?></h3>
	<div id="progress"></div>

	<a href="options-general.php?page=backup-to-dropbox"><?php _e( 'Back to options', 'wpbtd' ); ?></a>
</div>