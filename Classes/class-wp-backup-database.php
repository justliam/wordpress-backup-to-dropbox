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
abstract class WP_Backup_Database {
	const SELECT_QUERY_LIMIT = 10;
	const WAIT_TIMEOUT = 600; //10 minutes

	private $handle;
	private $type;

	protected $database;
	protected $config;

	abstract function execute();

	public function __construct($type) {
		$this->type = $type;
		$this->database = WP_Backup_Registry::db();
		$this->config = WP_Backup_Registry::config();

		$this->set_wait_timeout();
	}

	public function remove_file() {
		$files = glob($this->get_file(false) . '*');
		if ($files) {
			foreach ($files as $file)
				unlink($file);
		}
	}

	private function set_wait_timeout() {
		$this->database->query("SET SESSION wait_timeout=" . self::WAIT_TIMEOUT);
	}

	public function get_file($secret = true) {
		if (!$this->type)
			throw new Exception();

		$file = rtrim($this->config->get_backup_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . DB_NAME . "-backup-{$this->type}.sql";

		$files = glob($file . '*');
		if ($files) {
			foreach ($files as $file) {
				if (preg_match('/(.?)' . preg_quote("-backup-{$this->type}.sql") . '(.?)/', $file))
					return $file;
			}
		}

		if ($secret) {
			$file .= '.' . WP_Backup_Registry::get_secret(DB_NAME);
		}

		return $file;
	}

	protected function exists() {
		$files = glob($this->get_file(false) . '*');
		if (is_array($files))
			return count($files) > 0;
	}

	protected function write_db_dump_header() {
		$this->handle = fopen($this->get_file(), 'w+');
		if (!$this->handle)
			throw new Exception(__('Error creating sql dump file.', 'wpbtd'));

		$dump_location = $this->config->get_backup_dir();

		if (!is_writable($dump_location)) {
			$msg = sprintf(__("A database backup cannot be created because WordPress does not have write access to '%s', please ensure this directory has write access.", 'wpbtd'), $dump_location);
			WP_Backup_Registry::logger()->log($msg);
			return false;
		}

		$blog_time = strtotime(current_time('mysql'));

		$this->write_to_file("-- WordPress Backup to Dropbox SQL Dump\n");
		$this->write_to_file("-- Version " . BACKUP_TO_DROPBOX_VERSION . "\n");
		$this->write_to_file("-- http://wpb2d.com\n");
		$this->write_to_file("-- Generation Time: " . date("F j, Y", $blog_time) . " at " . date("H:i", $blog_time) . "\n\n");
		$this->write_to_file('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";' . "\n\n");

		//I got this out of the phpMyAdmin database dump to make sure charset is correct
		$this->write_to_file("/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n");
		$this->write_to_file("/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n");
		$this->write_to_file("/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n");
		$this->write_to_file("/*!40101 SET NAMES utf8 */;\n\n");

		$this->write_to_file("--\n-- Create and use the backed up database\n--\n\n");
		$this->write_to_file("CREATE DATABASE IF NOT EXISTS " . DB_NAME . ";\n");
		$this->write_to_file("USE " . DB_NAME . ";\n\n");
	}

	protected function backup_database_tables($tables) {
		$db_error = __('Error while accessing database.', 'wpbtd');
		foreach ($tables as $table) {
			$this->write_to_file("--\n-- Table structure for table `$table`\n--\n\n");

			$table_create = $this->database->get_row("SHOW CREATE TABLE $table", ARRAY_N);
			if ($table_create === false) {
				throw new Exception($db_error . ' (ERROR_3)');
			}
			$this->write_to_file($table_create[1] . ";\n\n");

			$table_count = $this->database->get_var("SELECT COUNT(*) FROM $table");
			if ($table_count == 0) {
				$this->write_to_file("--\n-- Table `$table` is empty\n--\n\n");
				continue;
			} else {
				$this->write_to_file("--\n-- Dumping data for table `$table`\n--\n\n");
				for ($i = 0; $i < $table_count; $i = $i + self::SELECT_QUERY_LIMIT) {
					$table_data = $this->database->get_results("SELECT * FROM $table LIMIT " . self::SELECT_QUERY_LIMIT . " OFFSET $i", ARRAY_A);
					if ($table_data === false) {
						throw new Exception($db_error . ' (ERROR_4)');
					}

					$fields = '`' . implode('`, `', array_keys($table_data[0])) . '`';
					$this->write_to_file("INSERT INTO `$table` ($fields) VALUES\n");

					$out = '';
					foreach ($table_data as $data) {
						$data_out = '(';
						foreach ($data as $value) {
							$value = addslashes($value);
							$value = str_replace("\n", "\\n", $value);
							$value = str_replace("\r", "\\r", $value);
							$data_out .= "'$value', ";
						}
						$out .= rtrim($data_out, ' ,') . "),\n";
					}
					$this->write_to_file(rtrim($out, ",\n") . ";\n\n");
				}
			}
		}
	}

	protected function write_to_file($out) {
		if (fwrite($this->handle, $out) === false)
			throw new Exception(__('Error writing to sql dump file.', 'wpbtd'));
	}

	protected function close_file() {
		if (!fclose($this->handle))
			throw new Exception(__('Error closing sql dump file.', 'wpbtd'));

		return true;
	}
}