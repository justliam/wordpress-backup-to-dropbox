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
class WP_Backup_Processed_Files {

	private
		$db,
		$processed_files
		;

	public function __construct($wpdb = null) {
		if (!$wpdb) global $wpdb;

		$this->db = $wpdb;
	}

	public function get_file_count() {
		return count($this->get_files());
	}

	public function get_file($file_name) {
		foreach ($this->get_files() as $file) {
			if ($file->file == $file_name)
				return $file;
		}
	}

	public function add_files($new_files) {
		foreach ($new_files as $file) {

			$file_details = new stdClass;
			$file_details->file = $file;

			$this->processed_files[] = $file_details;
			$this->db->insert($this->db->prefix . 'wpb2d_processed_files', array(
				'file' => $file
			));
		}

		return $this;
	}

	public function track_upload($file, $upload_id, $offset) {
		$this->db->update(
			$this->db->prefix . 'wpb2d_options',
			array('upliadid' => $upload_id, 'offset' => $offset),
			array('file' => $file)
		);
	}

	private function get_files() {
		if (!$this->processed_files)
			$this->processed_files = $this->db->get_results("SELECT * FROM {$this->db->prefix}wpb2d_processed_files");

		return $this->processed_files;
	}
}