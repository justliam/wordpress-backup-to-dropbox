<?php
/**
 * A class with functions the perform a backup of WordPress
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
class WP_Backup_Output {

	private $config;
	private $dropbox;
	private $cache;

	public function __construct($dropbox = false, $cache = true) {
		$this->dropbox = $dropbox ? $dropbox : new Dropbox_Facade();
		$this->config = new WP_Backup_Config();
		$this->cache = $cache;
	}

	private function get_cached_val($key, $val) {
		static $cached_vals = array();
		if (!isset($cached_vals[$key]))
			$cached_vals[$key] = $val;
		return $cached_vals[$key];
	}

	public function get_last_backup_time() {
		$val = $this->config->get_option('last_backup_time');
		if (!$this->cache)
			return $val;

		return $this->get_cached_val('last_backup_time', $val);
	}

	public function get_dropbox_location() {
		$val = $this->config->get_option('dropbox_location');
		if (!$this->cache)
			return $val;

		return $this->get_cached_val('dropbox_location', $val);
	}

	public function get_max_file_size() {
		$val = $this->config->get_max_file_size();
		if (!$this->cache)
			return $val;

		return $this->get_cached_val('max_file_size', $val);
	}

	public function out($source, $file) {
		$dropbox_location = $this->get_dropbox_location();
		$last_backup_time = $this->get_last_backup_time();
		$uploaded_files = $this->config->get_uploaded_files();

		if (filesize($file) > $this->get_max_file_size()) {
			$this->config->log(WP_Backup_Config::BACKUP_STATUS_WARNING,
						sprintf(__("file '%s' exceeds 40 percent of your PHP memory limit. The limit must be increased to back up this file.", 'wpbtd'), basename($file)));
			return;
		}

		if (in_array($file, $uploaded_files))
			return;

		$dropbox_path = $dropbox_location . DIRECTORY_SEPARATOR . str_replace($source . DIRECTORY_SEPARATOR, '', $file);
		if (PHP_OS == 'WINNT') {
			//The dropbox api requires a forward slash as the directory separator
			$dropbox_path = str_replace(DIRECTORY_SEPARATOR, '/', $dropbox_path);
		}

		$directory_contents = $this->dropbox->get_directory_contents(dirname($dropbox_path));
		if (!in_array(basename($file), $directory_contents) || filemtime($file) > $last_backup_time) {
			try {
				$this->config->set_current_action(__('Uploading'), $file);
				$this->dropbox->upload_file($dropbox_path, $file);
			} catch (Exception $e) {
				if ($e->getMessage() == 'Unauthorized')
					throw $e;

				$msg = sprintf(__("Could not upload '%s' due to the following error: %s", 'wpbtd'), $file, $e->getMessage());
				$this->config->log(WP_Backup_Config::BACKUP_STATUS_WARNING, $msg);
			}
		}
	}
}