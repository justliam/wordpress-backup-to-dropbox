<?php
/**
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
abstract class WP_Backup_Extension {
	const TYPE_DEFAULT = 1;
	const TYPE_OUTPUT = 2;

	protected
		$dropbox,
		$dropbox_path,
		$config
		;

	private	$chunked_upload_threashold;

	public function __construct() {
		$this->dropbox = WP_Backup_Registry::dropbox();
		$this->config  = WP_Backup_Registry::config();
	}

	public function set_chunked_upload_threashold($threashold) {
		$this->chunked_upload_threashold = $threashold;

		return $this;
	}

	public function get_chunked_upload_threashold() {
		if ($this->chunked_upload_threashold !== null)
			return $this->chunked_upload_threashold;

		return CHUNKED_UPLOAD_THREASHOLD;
	}

	abstract function complete();
	abstract function failure();

	abstract function get_menu();
	abstract function get_type();

	abstract function is_enabled();
	abstract function set_enabled($bool);
}