<?php
/**
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
class WP_Backup_Logger {

	const LOGFILE = 'wpb2d-backup-log.txt';

	public static function log($msg, $files = null) {
		$fh = self::get_log_file_handle();
		$log = sprintf("%s: %s", date('H:i:s', strtotime(current_time('mysql'))), $msg) . "\n";

		if (!empty($files))
			$log .= "Uploaded Files:" . json_encode($files) . "\n";

		if (@fwrite($fh, $log) === false || @fclose($fh) === false)
			throw new Exception('Error writing to log file.');
	}

	public static function get_log() {
		$file = self::get_log_file();
		if (file_exists($file))
			return explode("\n", trim(file_get_contents($file)));
	}

	public static function delete_log() {
		@unlink(self::get_log_file());
	}

	public static function get_log_file() {
		return WP_Backup_Config::get_backup_dir() . DIRECTORY_SEPARATOR . self::LOGFILE;
	}

	private static function get_log_file_handle() {
		WP_Backup::create_dump_dir();

		$fh = @fopen(self::get_log_file(), 'a');
		if ($fh === false)
			throw new Exception('Error opening log file.');

		return $fh;
	}
}