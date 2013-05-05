<?php
/**
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
abstract class WP_Backup_Extension {

	protected
		$dropbox,
		$dropbox_path,
		$config
		;

	private	$chunked_upload_threashold;

	public function set_dropbox_api($dropbox) {
		$this->dropbox = $dropbox;

		return $this;
	}

	public function set_config($config) {
		$this->config = $config;

		return $this;
	}

	public function set_chunked_upload_threashold($threashold) {
		$this->chunked_upload_threashold = $threashold;

		return $this;
	}

	public function get_chunked_upload_threashold() {
		if ($this->chunked_upload_threashold)
			return $this->chunked_upload_threashold;

		return CHUNKED_UPLOAD_THESHOLD;
	}

	abstract function start();
	abstract function end();
	abstract function complete();
	abstract function failure();

	abstract function get_menu();
	abstract function get_type();

	abstract function is_enabled();
	abstract function set_enabled($bool);
}