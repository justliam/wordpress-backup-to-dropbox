<?php
/**
 * This file contains the contents of the Dropbox admin monitor page.
 *
 * @copyright Copyright (C) 2011-2012 Michael De Wildt. All rights reserved.
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
$config = WP_Backup_Config::construct();
$backup = new WP_Backup();

if (array_key_exists('stop_backup', $_POST)) {
	check_admin_referer('backup_to_dropbox_monitor_stop');
	$backup->stop();
} else if (array_key_exists('start_backup', $_POST)) {
	check_admin_referer('backup_to_dropbox_monitor_stop');
	$backup->backup_now();
	$started = true;
}

?>
<script type="text/javascript" language="javascript">
	function reload() {
		jQuery('.files').hide();
		jQuery.post(ajaxurl, { action : 'progress' }, function(data) {
			if (data.length) {
				jQuery('#progress').html(data);
				jQuery('.view-files').on('click', function() {
					$files = jQuery(this).next();

					$files.toggle();
					$files.find('li').each(function() {
						$this = jQuery(this);
						$this.css(
							'background',
							'url(<?php echo $uri ?>/JQueryFileTree/images/' + $this.text().slice(-3).replace(/^\.+/,'') + '.png) left top no-repeat'
						);
					});

				});
			}
		});
		<?php if ($config->get_option('in_progress') || isset($started)): ?>
			setTimeout("reload()", 15000);
		<?php endif; ?>
	}
	jQuery(document).ready(function ($) {
		reload();
	});
</script>
<div class="wrap">
	<div class="icon32"><img width="36px" height="36px"
								 src="<?php echo $uri ?>/Images/WordPressBackupToDropbox_64.png"
								 alt="WordPress Backup to Dropbox Logo"></div>
	<h2><?php _e('WordPress Backup to Dropbox', 'wpbtd'); ?></h2>
	<p class="description"><?php printf(__('Version %s', 'wpbtd'), BACKUP_TO_DROPBOX_VERSION) ?></p>
	<h3><?php _e('Backup Log', 'wpbtd'); ?></h3>
	<div id="progress">
		<div id="circleG">
			<div id="circleG_1" class="circleG"></div>
			<div id="circleG_2" class="circleG"></div>
			<div id="circleG_3" class="circleG"></div>
		</div>
		<div class="loading"><?php _e('Loading...') ?></div>
	</div>
	<form id="backup_to_dropbox_options" name="backup_to_dropbox_options" action="admin.php?page=backup-to-dropbox-monitor" method="post">
		<?php if ($config->get_option('in_progress') || isset($started)): ?>
			<input type="submit" id="stop_backup" name="stop_backup" class="button-secondary" value="<?php _e('Stop Backup', 'wpbtd'); ?>">
		<?php else: ?>
			<input type="submit" id="start_backup" name="start_backup" class="button-secondary" value="<?php _e('Start Backup', 'wpbtd'); ?>">
		<?php endif; ?>

		<?php wp_nonce_field('backup_to_dropbox_monitor_stop'); ?>
	</form>
</div>
