<?php
/**
 * This file contains the contents of the Dropbox admin options page.
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
try {
	$v = phpversion();
	if ($v < 5) {
		throw new Exception(sprintf(__('Your PHP version (%s) is too old for this plugin to function correctly please update to PHP 5.2 or higher.'), $v));
	}

	global $wpdb;

	$validation_errors = null;

	$dropbox = Dropbox_Facade::construct();
	$config = WP_Backup_Config::construct();
	$backup = new WP_Backup();

	$disable_backup_now = $config->get_option('in_progress');

	//We have a form submit so update the schedule and options
	if (array_key_exists('wpb2d_save_changes', $_POST)) {
		check_admin_referer('backup_to_dropbox_options_save');

		if (preg_match('/[^A-Za-z0-9-_.\/]/', $_POST['dropbox_location'])) {
			$error_msg = __('The sub directory must only contain alphanumeric characters.', 'wpbtd');
			$dropbox_location = $_POST['dropbox_location'];
			$store_in_subfolder = true;
		} else {
			$config
				->set_schedule($_POST['day'], $_POST['time'], $_POST['frequency'])
				->set_option('store_in_subfolder', $_POST['store_in_subfolder'] == "on")
				->set_option('dropbox_location', $_POST['dropbox_location']);
		}
	} else if (array_key_exists('unlink', $_POST)) {
		check_admin_referer('backup_to_dropbox_options_save');
		$dropbox->unlink_account();
	} else if (array_key_exists('clear_history', $_POST)) {
		check_admin_referer('backup_to_dropbox_options_save');
		$config->clear_history();
	}

	//Lets grab the schedule and the options to display to the user
	list($unixtime, $frequency) = $config->get_schedule();
	if (!$frequency) {
		$frequency = 'weekly';
	}

	if (!isset($error_msg)) {
		$dropbox_location = $config->get_option('dropbox_location');
		$store_in_subfolder = $config->get_option('store_in_subfolder');
	}

	$time = date('H:i', $unixtime);
	$day = date('D', $unixtime);
	?>
<link rel="stylesheet" type="text/css" href="<?php echo $uri ?>/JQueryFileTree/jqueryFileTree.css"/>
<script src="<?php echo $uri ?>/JQueryFileTree/jqueryFileTree.js" type="text/javascript" language="javascript"></script>
<script src="<?php echo $uri ?>/wp-backup-to-dropbox.js" type="text/javascript" language="javascript"></script>
<script type="text/javascript" language="javascript">
	jQuery(document).ready(function ($) {
		$('#frequency').change(function() {
			var len = $('#day option').size();
			if ($('#frequency').val() == 'daily') {
				$('#day').append($("<option></option>").attr("value", "").text('<?php _e('Daily', 'wpbtd'); ?>'));
				$('#day option:last').attr('selected', 'selected');
				$('#day').attr('disabled', 'disabled');
			} else if (len == 8) {
				$('#day').removeAttr('disabled');
				$('#day option:last').remove();
			}
		});

		//Display the file tree with a call back to update the clicked on check box and white list
		$('#file_tree').fileTree({
			root: '<?php echo addslashes(ABSPATH); ?>',
			script: ajaxurl,
			expandSpeed: 500,
			collapseSpeed: 500,
			multiFolder: false
		});

		$('#togglers .button').click(function() {
			switch ($(this).attr('rel')) {
			case "all":
				// clicking an unchecked, expanded directory triggers a collapse which is confusing
				// skip expanded directories when checking everything (they'll auto-check themselves)
				$('#file_tree .checkbox').not('.checked, .partial, .directory.expanded>.checkbox').click();
				break;
			case "none":
				$('#file_tree .checkbox.checked').click();
				break;
			case "invert":
				$('#file_tree .checkbox').not('.partial, .directory.expanded>.checkbox').click();
				break;
			}
		})

		$('#store_in_subfolder').click(function (e) {
			if ($('#store_in_subfolder').is(':checked')) {
				$('.dropbox_location').show('fast', function() {
					$('#dropbox_location').focus();
				});
			} else {
				$('#dropbox_location').val('');
				$('.dropbox_location').hide();
			}
		});
	});

	/**
	 * Display the Dropbox authorize url, hide the authorize button and then show the continue button.
	 * @param url
	 */
	function dropbox_authorize(url) {
		window.open(url);
		document.getElementById('continue').style.visibility = 'visible';
		document.getElementById('authorize').style.visibility = 'hidden';
	}
</script>
	<div class="wrap">
	<div class="icon32"><img width="36px" height="36px"
							 src="<?php echo $uri ?>/Images/WordPressBackupToDropbox_64.png"
							 alt="Wordpress Backup to Dropbox Logo"></div>
<h2><?php _e('WordPress Backup to Dropbox', 'wpbtd'); ?></h2>
<p class="description"><?php printf(__('Version %s', 'wpbtd'), BACKUP_TO_DROPBOX_VERSION) ?></p>
	<?php if ($dropbox->is_authorized()) {
		$account_info = $dropbox->get_account_info();
		$used = round(($account_info->quota_info->quota - ($account_info->quota_info->normal + $account_info->quota_info->shared)) / 1073741824, 1);
		$quota = round($account_info->quota_info->quota / 1073741824, 1);
	?>
	<h3><?php _e('Dropbox Account Details', 'wpbtd'); ?></h3>
	<form id="backup_to_dropbox_options" name="backup_to_dropbox_options"
		  action="admin.php?page=backup-to-dropbox" method="post">
	<p class="bump">
		<?php echo
				$account_info->display_name . ', ' .
				__('you have', 'wpbtd') . ' ' .
				$used .
				'<acronym title="' . __('Gigabyte', 'wpbtd') . '">GB</acronym> ' .
				__('of', 'wpbtd') . ' ' . $quota . 'GB (' . round(($used / $quota) * 100, 0) .
				'%) ' . __('free', 'wpbtd') ?>
	</p>
	<input type="submit" id="unlink" name="unlink" class="bump button-secondary" value="<?php _e('Unlink Account', 'wpbtd'); ?>">

	<h3><?php _e('Next Scheduled', 'wpbtd'); ?></h3>
		<?php
		$schedule = $config->get_schedule();
		if ($schedule) {
			?>
			<p style="margin-left: 10px;"><?php printf(__('Next backup scheduled for %s at %s', 'wpbtd'), date('Y-m-d', $schedule[ 0 ]), date('H:i:s', $schedule[ 0 ])) ?></p>
			<?php } else { ?>
			<p style="margin-left: 10px;"><?php _e('No backups are scheduled yet. Please select a day, time and frequency below. ', 'wpbtd') ?></p>
			<?php } ?>
		<h3><?php _e('History', 'wpbtd'); ?></h3>
		<?php
		$backup_history = array_reverse($config->get_history());
		if ($backup_history) {
			echo '<ol class="history_box">';
			foreach ($backup_history as $backup_time) {

				if (is_array($backup_time))
					continue;

				$blog_time = strtotime(date('Y-m-d H', strtotime(current_time('mysql'))) . ':00:00');
				$blog_time += $backup_time - strtotime(date('Y-m-d H') . ':00:00');

				$backup_date = date('l F j, Y', $blog_time);
				$backup_time_str = date('H:i:s', $blog_time);

				echo '<li>' . sprintf(__('Backup completed on %s at %s.'), $backup_date, $backup_time_str) . '</li>';
			}
			echo '</ol>';
			echo '<input type="submit" id="clear_history" name="clear_history"" class="bump button-secondary" value="' . __('Clear history', 'wpbtd') . '">';
		} else {
			echo '<p style="margin-left: 10px;">' . __('No history', 'wpbtd') . '</p>';
		}
		?>
	<h3><?php _e('Settings', 'wpbtd'); ?></h3>
	<table class="form-table">
		<tbody>
		<tr valign="top">
			<th scope="row"><label
					for="dropbox_location"><?php _e("Store backup in a subfolder of the wpb2d app folder", 'wpbtd'); ?></label>
			</th>
			<td>
				<input name="store_in_subfolder" type="checkbox" id="store_in_subfolder"
					   <?php echo $store_in_subfolder ? 'checked="checked"' : ''; ?> >

				<span class="dropbox_location <?php if (!$store_in_subfolder) echo 'hide' ?>">
					<input name="dropbox_location" type="text" id="dropbox_location"
						   value="<?php echo $dropbox_location; ?>" class="regular-text code">
					<?php if (isset($error_msg)) { ?>
					<br/><span class="description"
							   style="color: red"><?php echo $error_msg ?></span>
					<?php } ?>
				</span>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row"><label for="time"><?php _e('Day and Time', 'wpbtd'); ?></label></th>
			<td>
				<select id="day" name="day" <?php echo ($frequency == 'daily') ? 'disabled="disabled"' : '' ?>>
					<option value="Mon" <?php echo $day == 'Mon' ? ' selected="selected"'
							: "" ?>><?php _e('Monday', 'wpbtd'); ?></option>
					<option value="Tue" <?php echo $day == 'Tue' ? ' selected="selected"'
							: "" ?>><?php _e('Tuesday', 'wpbtd'); ?></option>
					<option value="Wed" <?php echo $day == 'Wed' ? ' selected="selected"'
							: "" ?>><?php _e('Wednesday', 'wpbtd'); ?></option>
					<option value="Thu" <?php echo $day == 'Thu' ? ' selected="selected"'
							: "" ?>><?php _e('Thursday', 'wpbtd'); ?></option>
					<option value="Fri" <?php echo $day == 'Fri' ? ' selected="selected"'
							: "" ?>><?php _e('Friday', 'wpbtd'); ?></option>
					<option value="Sat" <?php echo $day == 'Sat' ? ' selected="selected"'
							: "" ?>><?php _e('Saturday', 'wpbtd'); ?></option>
					<option value="Sun" <?php echo $day == 'Sun' ? ' selected="selected"'
							: "" ?>><?php _e('Sunday', 'wpbtd'); ?></option>
					<?php if ($frequency == 'daily') { ?>
					<option value="" selected="selected"><?php _e('Daily', 'wpbtd'); ?></option>
					<?php } ?>
				</select> <?php _e('at', 'wpbtd'); ?>
				<select id="time" name="time">
					<option value="00:00" <?php echo $time == '00:00' ? ' selected="selected"' : "" ?>>00:00
					</option>
					<option value="01:00" <?php echo $time == '01:00' ? ' selected="selected"' : "" ?>>01:00
					</option>
					<option value="02:00" <?php echo $time == '02:00' ? ' selected="selected"' : "" ?>>02:00
					</option>
					<option value="03:00" <?php echo $time == '03:00' ? ' selected="selected"' : "" ?>>03:00
					</option>
					<option value="04:00" <?php echo $time == '04:00' ? ' selected="selected"' : "" ?>>04:00
					</option>
					<option value="05:00" <?php echo $time == '05:00' ? ' selected="selected"' : "" ?>>05:00
					</option>
					<option value="06:00" <?php echo $time == '06:00' ? ' selected="selected"' : "" ?>>06:00
					</option>
					<option value="07:00" <?php echo $time == '07:00' ? ' selected="selected"' : "" ?>>07:00
					</option>
					<option value="08:00" <?php echo $time == '08:00' ? ' selected="selected"' : "" ?>>08:00
					</option>
					<option value="09:00" <?php echo $time == '09:00' ? ' selected="selected"' : "" ?>>09:00
					</option>
					<option value="10:00" <?php echo $time == '10:00' ? ' selected="selected"' : "" ?>>10:00
					</option>
					<option value="11:00" <?php echo $time == '11:00' ? ' selected="selected"' : "" ?>>11:00
					</option>
					<option value="12:00" <?php echo $time == '12:00' ? ' selected="selected"' : "" ?>>12:00
					</option>
					<option value="13:00" <?php echo $time == '13:00' ? ' selected="selected"' : "" ?>>13:00
					</option>
					<option value="14:00" <?php echo $time == '14:00' ? ' selected="selected"' : "" ?>>14:00
					</option>
					<option value="15:00" <?php echo $time == '15:00' ? ' selected="selected"' : "" ?>>15:00
					</option>
					<option value="16:00" <?php echo $time == '16:00' ? ' selected="selected"' : "" ?>>16:00
					</option>
					<option value="17:00" <?php echo $time == '17:00' ? ' selected="selected"' : "" ?>>17:00
					</option>
					<option value="18:00" <?php echo $time == '18:00' ? ' selected="selected"' : "" ?>>18:00
					</option>
					<option value="19:00" <?php echo $time == '19:00' ? ' selected="selected"' : "" ?>>19:00
					</option>
					<option value="20:00" <?php echo $time == '20:00' ? ' selected="selected"' : "" ?>>20:00
					</option>
					<option value="21:00" <?php echo $time == '21:00' ? ' selected="selected"' : "" ?>>21:00
					</option>
					<option value="22:00" <?php echo $time == '22:00' ? ' selected="selected"' : "" ?>>22:00
					</option>
					<option value="23:00" <?php echo $time == '23:00' ? ' selected="selected"' : "" ?>>23:00
					</option>
				</select>
				<span class="description"><?php _e('The day and time the backup to Dropbox is to be performed.', 'wpbtd'); ?></span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="frequency"><?php _e('Frequency', 'wpbtd'); ?></label></th>
			<td>
				<select id="frequency" name="frequency">
					<option value="daily" <?php echo $frequency == 'daily' ? ' selected="selected"' : "" ?>>
						<?php _e('Daily', 'wpbtd') ?>
					</option>
					<option value="weekly" <?php echo $frequency == 'weekly' ? ' selected="selected"' : "" ?>>
						<?php _e('Weekly', 'wpbtd') ?>
					</option>
					<option value="fortnightly" <?php echo $frequency == 'fortnightly' ? ' selected="selected"'
							: "" ?>>
						<?php _e('Fortnightly', 'wpbtd') ?>
					</option>
					<option value="monthly" <?php echo $frequency == 'monthly' ? ' selected="selected"' : "" ?>>
						<?php _e('Every 4 weeks', 'wpbtd') ?>
					</option>
					<option value="two_monthly" <?php echo $frequency == 'two_monthly' ? ' selected="selected"'
							: "" ?>>
						<?php _e('Every 8 weeks', 'wpbtd') ?>
					</option>
					<option value="three_monthly" <?php echo $frequency == 'three_monthly' ? ' selected="selected"'
							: "" ?>>
						<?php _e('Every 12 weeks', 'wpbtd') ?>
					</option>
				</select>
				<span class="description"><?php _e('How often the backup to Dropbox is to be performed.', 'wpbtd'); ?></span>
			</td>
		</tr>
		</tbody>
	</table>
	<!--[if !IE | gt IE 7]><!-->
	<h3><?php _e('Excluded Files and Directories', 'wpbtd'); ?></h3>
	<p style='margin-left: 10px;'>
		<span class="description">
			<?php _e('Select the files and directories that you wish to exclude from your backup. You can expand directories with contents by clicking its name.', 'wpbtd') ?><br />
			<strong><?php _e('Please Note:', 'wpbtd'); ?></strong>&nbsp;<?php _e('Your SQL dump file will always be backed up regardless of what is selected below.', 'wpbtd'); ?>
		</span>
	</p>
	<div id="file_tree">
		<div id="circleG" class="start">
			<div id="circleG_1" class="circleG"></div>
			<div id="circleG_2" class="circleG"></div>
			<div id="circleG_3" class="circleG"></div>
		</div>
		<div class="loading start"><?php _e('Loading...') ?></div>
	</div>
	<div id="togglers"><?php _e("Exclude:", 'wpbtd'); ?>
		<span class="button" rel="all" href="#"><?php _e("All", 'wpbtd'); ?></span>
		<span class="button" rel="none" href="#"><?php _e("None", 'wpbtd'); ?></span>
		<span class="button" rel="invert" href="#"><?php _e("Inverse", 'wpbtd'); ?></span>
	</div>
	<!--<![endif]-->
	<p class="submit">
		<input type="submit" id="wpb2d_save_changes" name="wpb2d_save_changes" class="button-primary" value="<?php _e('Save Changes', 'wpbtd'); ?>">
	</p>
		<?php wp_nonce_field('backup_to_dropbox_options_save'); ?>
	</form>
		<?php

	} else {
		//We need to re authenticate this user
		$url = $dropbox->get_authorize_url();
		?>
	<h3><?php _e('Thank you for installing WordPress Backup to Dropbox!', 'wpbtd'); ?></h3>
	<p><?php _e('In order to use this plugin you will need to authorized it with your Dropbox account.', 'wpbtd'); ?></p>
	<p><?php _e('Please click the authorize button below and follow the instructions inside the pop up window.', 'wpbtd'); ?></p>
		<?php if (array_key_exists('continue', $_POST) && !$dropbox->is_authorized()) { ?>
		<p style="color: red"><?php _e('There was an error authorizing the plugin with your Dropbox account. Please try again.', 'wpbtd'); ?></p>
			<?php } ?>
	<p>
	<form id="backup_to_dropbox_continue" name="backup_to_dropbox_continue"
		  action="options-general.php?page=backup-to-dropbox" method="post">
		<input type="button" name="authorize" id="authorize" value="<?php _e('Authorize', 'wpbtd'); ?>"
			   onclick="dropbox_authorize('<?php echo $url ?>')"/><br/>
		<input style="visibility: hidden;" type="submit" name="continue" id="continue"
			   value="<?php _e('Continue', 'wpbtd'); ?>"/>
	</form>
	</p>
		<?php

	}
} catch (Exception $e) {
	echo '<h3>Error</h3>';
	echo '<p>' . __('There was a fatal error loading WordPress Backup to Dropbox. Please fix the problems listed and reload the page.', 'wpbtd') . '</h3>';
	echo '<p>' . __('If the problem persists please re-install WordPress Backup to Dropbox.', 'wpbtd') . '</h3>';
	echo '<p><strong>' . __('Error message:') . '</strong> ' . $e->getMessage() . '</p>';

	if ($dropbox)
		$dropbox->unlink_account();
}
?>
</div>
