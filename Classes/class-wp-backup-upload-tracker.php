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
class WP_Backup_Upload_Tracker {

	private $db;

	public function __construct() {
		$this->db = WP_Backup_Registry::db();
	}

	public function track_upload($file, $upload_id, $offset) {
		WP_Backup_Registry::config()->die_if_stopped();

		WP_Backup_Registry::logger()->log(sprintf(
			__("Uploaded %sMB of %sMB", 'wpbtd'),
			round($offset / 1048576, 1),
			round(filesize($file) / 1048576, 1)
		));

		$result = $this->db->insert("{$this->db->prefix}wpb2d_processed_files", array(
			'file' => $file,
			'uploadid' => $upload_id,
			'offset' => $offset
		));

		if (!$result) {
			$this->db->update(
				"{$this->db->prefix}wpb2d_processed_files",
				array('uploadid' => $upload_id, 'offset' => $offset),
				array('file' => $file)
			);
		}
	}
}