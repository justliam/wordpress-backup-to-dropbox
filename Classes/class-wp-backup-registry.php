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
class WP_Backup_Registry {

	private static
		$logger,
		$config,
		$dropbox,
		$db
		;

	public static function logger() {
		if (!self::$logger)
			self::$logger = new WP_Backup_Logger();

		return self::$logger;
	}

	public static function config() {
		if (!self::$config)
			self::$config = new WP_Backup_Config();

		return self::$config;
	}

	public static function dropbox() {
		if (!self::$dropbox) {
			self::$dropbox = new Dropbox_Facade();
			self::$dropbox->init();
		}

		return self::$dropbox;
	}

	public static function db() {
		if (!self::$db) {
			global $wpdb;

			$wpdb->hide_errors();

			if (defined('WPB2D_TEST_MODE'))
				$wpdb->show_errors();

			self::$db = $wpdb;
		}

		return self::$db;
	}

	public static function setLogger($logger) {
		self::$logger = $logger;
	}

	public static function setConfig($config) {
		self::$config = $config;
	}

	public static function setDropbox($dropbox) {
		self::$dropbox = $dropbox;
	}

	public static function setDatabase($db) {
		self::$db = $db;
	}
}
