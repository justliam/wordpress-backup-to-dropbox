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
include_once('class-wp-backup.php');
class File_List {

	private static $ignored_files = array( '.DS_Store', 'Thumbs.db', 'desktop.ini' );
	private $excluded_files;
	private $excluded_dirs;

	public static function construct() {
		return new self();
	}

	public function __construct() {
		WP_Backup_Config::construct()->set_memory_limit();

		delete_option('backup-to-dropbox-file-list');

		$file_list = get_option('backup-to-dropbox-excluded-files');
		if ($file_list === false) {
			$this->excluded_files = array();
			$this->excluded_dirs = array();
			add_option('backup-to-dropbox-excluded-files', array($this->excluded_dirs, $this->excluded_files), null, 'no');
		} else {
			list($this->excluded_dirs, $this->excluded_files) = $file_list;
		}
	}

	public function set_included($path) {
		if (is_dir($path))
			$this->include_dir(rtrim($path,'/'));
		else
			$this->include_file($path);
		$this->save();
	}

	public function set_excluded($path) {
		if (is_dir($path))
			$this->exclude_dir(rtrim($path,'/'));
		else
			$this->exclude_file($path);
		$this->save();
	}

	public function is_excluded($path) {
		if (is_dir($path))
			return $this->is_excluded_dir($path);
		else
			return $this->is_excluded_file($path);
	}

	private function exclude_file($file) {
		if (!in_array($file, $this->excluded_files))
			$this->excluded_files[] = $file;
	}

	private function exclude_dir($dir) {
		if (!in_array($dir, $this->excluded_dirs))
			$this->excluded_dirs[] = $dir;
	}

	private function include_file($file) {
		$key = array_search($file, $this->excluded_files);
		unset($this->excluded_files[$key]);
	}

	private function include_dir($dir) {
		$key = array_search($dir, $this->excluded_dirs);
		unset($this->excluded_dirs[$key]);
	}

	private function is_excluded_file($file) {
		if (!in_array($file, $this->excluded_files))
			return $this->is_excluded_dir(dirname($file));

		return true;
	}

	private function is_excluded_dir($dir) {
		if (empty($this->excluded_dirs))
			return false;

		if (in_array($dir, $this->excluded_dirs))
			return true;

		if ($dir == rtrim(ABSPATH,'/'))
			return false;

		return $this->is_excluded_dir(dirname($dir));
	}

	private function is_partial_dir($dir) {
		if (is_dir($dir)) {
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);
			foreach ($files as $file) {
				if ($file == $dir)
					continue;

				if (self::in_ignore_list(basename($file)))
					continue;

				if ($this->is_excluded($file))
					return true;
			}
		}
		return false;
	}

	public function get_checkbox_class($path) {
		$class = '';
		if ($this->is_excluded(rtrim($path, '/')))
			$class = 'checked';
		else if ($this->is_partial_dir($path))
			$class = 'partial';

		return $class;
	}

	public function save() {
		update_option('backup-to-dropbox-excluded-files', array($this->excluded_dirs, $this->excluded_files));
	}

	public static function in_ignore_list($file) {
		return in_array($file, self::$ignored_files);
	}
}
