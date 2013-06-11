<?php
/**
 * A class with functions the perform a backup of WordPress
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
class WP_Backup_Processed_Files {

	private
		$db,
		$processed_files
		;

	public function __construct() {
		$this->db = WP_Backup_Registry::db();

		$ret = $this->db->get_results("SELECT * FROM {$this->db->prefix}wpb2d_processed_files");
		if (is_array($ret))
			$this->processed_files = $ret;
	}

	public function get_file_count() {
		return count($this->processed_files);
	}

	public function get_file($file_name) {
		foreach ($this->processed_files as $file) {
			if ($file->file == $file_name)
				return $file;
		}
	}

	public function file_complete($file) {
		$this->update_file($file, 0, 0);
	}

	public function update_file($file, $upload_id, $offset) {
		$exists = $this->db->get_var(
			$this->db->prepare("SELECT * FROM {$this->db->prefix}wpb2d_processed_files WHERE file = %s", $file)
		);

		if (is_null($exists)) {
			$this->db->insert("{$this->db->prefix}wpb2d_processed_files", array(
				'file' => $file,
				'uploadid' => $upload_id,
				'offset' => $offset,
			));
		} else {
			$this->db->update(
				"{$this->db->prefix}wpb2d_processed_files",
				array('uploadid' => $upload_id, 'offset' => $offset),
				array('file' => $file)
			);
		}

		//Update the cached version
		for($i = 0; $i < count($this->processed_files); $i++) {
			if ($this->processed_files[$i]->file == $file) {
				$this->processed_files[$i]->offset = $offset;
				$this->processed_files[$i]->uploadid = $upload_id;
				break;
			}
		}
	}

	public function add_files($new_files) {
		foreach ($new_files as $file) {

			if ($this->get_file($file))
				continue;

			$file_details = new stdClass;
			$file_details->file = $file;
			$file_details->offset = null;
			$file_details->uploadid = null;

			$this->processed_files[] = $file_details;
			$this->db->insert($this->db->prefix . 'wpb2d_processed_files', array(
				'file' => $file
			));
		}

		return $this;
	}
}