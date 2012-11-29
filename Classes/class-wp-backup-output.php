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
	private $error_count;

	public function __construct($dropbox = false, $config = false) {
		$this->dropbox = $dropbox ? $dropbox : Dropbox_Facade::construct();
		$this->config = $config ? $config : WP_Backup_Config::construct();

		$this->last_backup_time = array_pop($this->config->get_history());

		$this->dropbox_location = null;
		if ($this->config->get_option('store_in_subfolder'))
			$this->dropbox_location = $this->config->get_option('dropbox_location');
	}

	public function out($source, $file) {

		if ($this->error_count > self::MAX_ERRORS) {
			throw new Exception(sprintf(__('The backup is having trouble uploading files to Dropbox, it has failed %s times and is aborting the backup.'), self::MAX_ERRORS));
		}

		$dropbox_path = $this->dropbox_location;
		$dir = dirname(str_replace($source . DIRECTORY_SEPARATOR, '', $file));
		if ($dir != '.')
			$dropbox_path .= DIRECTORY_SEPARATOR . $dir;

		if (PHP_OS == 'WINNT') {
			//The dropbox api requires a forward slash as the directory separator
			$dropbox_path = str_replace(DIRECTORY_SEPARATOR, '/', $dropbox_path);
		}

		try {
			$directory_contents = $this->dropbox->get_directory_contents($dropbox_path);
			if (!in_array(basename($file), $directory_contents) || filemtime($file) > $this->last_backup_time) {
				if (filesize($file) > Dropbox_Facade::CHUNKED_UPLOAD_THREASHOLD)
					return $this->dropbox->chunk_upload_file($dropbox_path, $file);
				else
					return $this->dropbox->upload_file($dropbox_path, $file);
			}

		} catch (Exception $e) {
			$this->config->log_error(sprintf(__("Error uploading '%s' to Dropbox: %s", 'wpbtd'), $file, strip_tags($e->getMessage())));
			$this->error_count++;
		}
	}

	public function end() {}
}