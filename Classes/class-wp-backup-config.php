<?php
/**
 * A class with functions the perform a backup of WordPress
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
class WP_Backup_Config {
	const MAX_HISTORY_ITEMS = 20;

	public static function construct() {
		return new self();
	}

	public function __construct() {
		if (!is_array(get_option('backup-to-dropbox-log'))) {
			add_option('backup-to-dropbox-log', array(), null, 'no');
		}

		if (!is_array(get_option('backup-to-dropbox-history'))) {
			add_option('backup-to-dropbox-history', array(), null, 'no');
		}

		$options = get_option('backup-to-dropbox-options');
		if (!$options) {
			$options = array(
				'dropbox_location' => null,
				'in_progress' => false,
				'store_in_subfolder' => false,
				'total_file_count' => 0,
			);
			add_option('backup-to-dropbox-options', $options, null, 'no');
		}

		$actions = get_option('backup-to-dropbox-actions');
		if (!$actions) {
			add_option('backup-to-dropbox-actions', array(), null, 'no');
		}

		$files = get_option('backup-to-dropbox-processed-files');
		if (!$files) {
			add_option('backup-to-dropbox-processed-files', array(), null, 'no');
		}
	}

	public static function get_backup_dir() {
		return WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'backups';
	}

	public function set_option($option, $value) {
		$options = get_option('backup-to-dropbox-options');
		$options[$option] = $value;
		update_option('backup-to-dropbox-options', $options);
		return $this;
	}

	public function get_option($option, $no_cache = false) {
		if ($no_cache)
			wp_cache_flush();

		$options = get_option('backup-to-dropbox-options');
		return isset($options[$option]) ? $options[$option] : false;
	}

	public function get_actions() {
		return get_option('backup-to-dropbox-actions');
	}

	public function get_processed_files() {
		return get_option('backup-to-dropbox-processed-files');
	}

	public function add_processed_files($new_files) {
		$files = $this->get_processed_files();
		if (!is_array($files))
			$files = array();

		update_option('backup-to-dropbox-processed-files', array_merge($files, $new_files));
		return $this;
	}

	public function set_time_limit() {
		@set_time_limit(0);
		return $this;
	}

	public function set_memory_limit() {
		if (function_exists('memory_get_usage'))
			@ini_set('memory_limit', -1);

		return $this;
	}

	public function is_scheduled() {
		return wp_get_schedule('execute_instant_drobox_backup') !== false;
	}

	public function set_schedule($day, $time, $frequency) {
		$blog_time = strtotime(date('Y-m-d H', strtotime(current_time('mysql'))) . ':00:00');

		//Grab the date in the blogs timezone
		$date = date('Y-m-d', $blog_time);

		//Check if we need to schedule the backup in the future
		$time_arr = explode(':', $time);
		$current_day = date('D', $blog_time);
		if ($day && ($current_day != $day)) {
			$date = date('Y-m-d', strtotime("next $day"));
		} else if ((int)$time_arr[0] <= (int)date('H', $blog_time)) {
			if ($day) {
				$date = date('Y-m-d', strtotime("+7 days", $blog_time));
			} else {
				$date = date('Y-m-d', strtotime("+1 day", $blog_time));
			}
		}

		$timestamp = wp_next_scheduled('execute_periodic_drobox_backup');
		if ($timestamp) {
			wp_unschedule_event($timestamp, 'execute_periodic_drobox_backup');
		}

		//This will be in the blogs timezone
		$scheduled_time = strtotime($date . ' ' . $time);

		//Convert the selected time to that of the server
		$server_time = strtotime(date('Y-m-d H') . ':00:00') + ($scheduled_time - $blog_time);

		wp_schedule_event($server_time, $frequency, 'execute_periodic_drobox_backup');
		return $this;
	}

	public function get_schedule() {
		$time = wp_next_scheduled('execute_periodic_drobox_backup');
		$frequency = wp_get_schedule('execute_periodic_drobox_backup');
		if ($time && $frequency) {
			//Convert the time to the blogs timezone
			$blog_time = strtotime(date('Y-m-d H', strtotime(current_time('mysql'))) . ':00:00');
			$blog_time += $time - strtotime(date('Y-m-d H') . ':00:00');
			$schedule = array($blog_time, $frequency);
		}
		return $schedule;
	}

	public function clear_history() {
		update_option('backup-to-dropbox-history', array());
	}

	public function get_history() {
		return get_option('backup-to-dropbox-history');
	}

	public function get_dropbox_path($source, $file, $root = false) {
		$dropbox_location = null;
		if ($this->get_option('store_in_subfolder'))
			$dropbox_location = $this->get_option('dropbox_location');

		if ($root)
			return $dropbox_location;

		$source = rtrim($source, DIRECTORY_SEPARATOR);

		return ltrim(dirname(str_replace($source, $dropbox_location, $file)), DIRECTORY_SEPARATOR);
	}

	public function log_finished_time() {
		$history = $this->get_history();
		$history[] = time();

		if (count($history) > self::MAX_HISTORY_ITEMS)
			array_shift($history);

		update_option('backup-to-dropbox-history', $history);
		return $this;
	}

	public function complete() {
		wp_clear_scheduled_hook('monitor_dropbox_backup_hook');
		wp_clear_scheduled_hook('run_dropbox_backup_hook');
		wp_clear_scheduled_hook('execute_instant_drobox_backup');

		update_option('backup-to-dropbox-processed-files', array());

		$this->set_option('in_progress', false);
		$this->set_option('is_running', false);

		return $this;
	}
}