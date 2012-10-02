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
<style type="text/css">
	.backup_error {
		color: red;
	}
	.backup_ok {
		color: green;
	}
	.backup_warning {
		color: orange;
	}
	#progress {
		max-height: 400px;
		overflow-y: scroll;
		margin: 0 0 10px 0;
	}
	ul {
		margin: 0;
	}
	.files {
		display: none;
		margin-left: 58px;
	}
	.files li {
		margin: 5px 0;
		padding-left: 20px;
	}
	.view-files {
		text-decoration: none;
	}

	.loading {
		padding: 5px;
		clear: both;
	}

	#circleG {
		width: 150px;
	}

	.circleG {
		background-color: #FFFFFF;
		float: left;
		height: 15px;
		margin-left: 8px;
		width: 15px;
		-webkit-animation-name: bounce_circleG;
		-webkit-border-radius: 10px;
		-webkit-animation-duration: 1.9500000000000002s;
		-webkit-animation-iteration-count: infinite;
		-webkit-animation-direction: linear;
		-moz-animation-name: bounce_circleG;
		-moz-border-radius: 10px;
		-moz-animation-duration: 1.9500000000000002s;
		-moz-animation-iteration-count: infinite;
		-moz-animation-direction: linear;
		opacity: 0.3;
		-o-animation-name: bounce_circleG;
		border-radius: 10px;
		-o-animation-duration: 1.9500000000000002s;
		-o-animation-iteration-count: infinite;
		-o-animation-direction: linear;
		-ms-animation-name: bounce_circleG;
		-ms-animation-duration: 1.9500000000000002s;
		-ms-animation-iteration-count: infinite;
		-ms-animation-direction: linear;
		opacity: 0.3
	}

	#circleG_1 {
		-webkit-animation-delay: 0.39s;
		-moz-animation-delay: 0.39s;
		-o-animation-delay: 0.39s;
		-ms-animation-delay: 0.39s;
	}

	#circleG_2 {
		-webkit-animation-delay: 0.91s;
		-moz-animation-delay: 0.91s;
		-o-animation-delay: 0.91s;
		-ms-animation-delay: 0.91s;
	}

	#circleG_3 {
		-webkit-animation-delay: 1.17s;
		-moz-animation-delay: 1.17s;
		-o-animation-delay: 1.17s;
		-ms-animation-delay: 1.17s;
	}

	@-webkit-keyframes bounce_circleG {
		0% {
			opacity: 0.3
		}

		50% {
			opacity: 1;
			background-color: #000000
		}

		100% {
			opacity: 0.3
		}

	}

	@-moz-keyframes bounce_circleG {
		0% {
			opacity: 0.3
		}

		50% {
			opacity: 1;
			background-color: #000000
		}

		100% {
			opacity: 0.3
		}

	}

	@-o-keyframes bounce_circleG {
		0% {
			opacity: 0.3
		}

		50% {
			opacity: 1;
			background-color: #000000
		}

		100% {
			opacity: 0.3
		}
	}

	@-ms-keyframes bounce_circleG {
		0% {
			opacity: 0.3
		}

		50% {
			opacity: 1;
			background-color: #000000
		}

		100% {
			opacity: 0.3
		}
	}
</style>
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
