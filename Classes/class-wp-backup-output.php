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

	const MAX_ERRORS = 10;

	private $dropbox;
	private $config;
	private $last_backup_time;
	private $dropbox_location;
	private $max_file_size;
	private $error_count;

	public function __construct($dropbox = false, $config = false) {
		$this->dropbox = $dropbox ? $dropbox : new Dropbox_Facade();
		$this->config = $config ? $config : new WP_Backup_Config();

		$this->last_backup_time = $this->config->get_option('last_backup_time');

		$this->dropbox_location = null;
		if ($this->config->get_option('store_in_subfolder'))
			$this->dropbox_location = $this->config->get_option('dropbox_location');

		$this->max_file_size = $this->config->get_max_file_size();
	}

	public function out($source, $file) {

		if ($this->error_count > self::MAX_ERRORS) {
			throw new Exception(sprintf(__('The backup is having trouble uploading files to Dropbox, it has failed %s times and is aborting the backup.'), self::MAX_ERRORS));
		}

		if (filesize($file) > $this->max_file_size) {
			$this->config->log(WP_Backup_Config::BACKUP_STATUS_WARNING,
						sprintf(__("file '%s' exceeds 40 percent of your PHP memory limit. The limit must be increased to back up this file.", 'wpbtd'), basename($file)));
			return;
		}

		$dropbox_path = $this->dropbox_location . DIRECTORY_SEPARATOR . str_replace($source . DIRECTORY_SEPARATOR, '', $file);
		if (PHP_OS == 'WINNT') {
			//The dropbox api requires a forward slash as the directory separator
			$dropbox_path = str_replace(DIRECTORY_SEPARATOR, '/', $dropbox_path);
		}

		try {

			$directory_contents = $this->dropbox->get_directory_contents(dirname($dropbox_path));
			if (!in_array(basename($file), $directory_contents) || filemtime($file) > $this->last_backup_time)
				$this->dropbox->upload_file($dropbox_path, $file);

		} catch (Exception $e) {
			$msg = sprintf(__("There was an error uploading '%s' to Dropbox", 'wpbtd'), $file);
			$this->config->log(WP_Backup_Config::BACKUP_STATUS_WARNING, $msg);
			$this->error_count++;
		}
	}

	public function end() {}
}