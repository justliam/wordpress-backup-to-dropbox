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
include_once('class-file-list.php');
class WP_Backup {
	const SELECT_QUERY_LIMIT = 10;

	private $dropbox;
	private $config;
	private $database;
	private $output;

	public static function construct() {
		return new self();
	}

	public function __construct($dropbox = null, $wpdb = null, $output = null) {
		if (!$wpdb) global $wpdb;
		$this->database = $wpdb;
		$this->dropbox = $dropbox ? $dropbox : Dropbox_Facade::construct();
		$this->config = WP_Backup_Config::construct();
		$this->output = $output ? $output : WP_Backup_Extension_Manager::construct()->get_output();
	}

	public function backup_path($path) {
		$this->config->set_current_action(sprintf(__('Backing up WordPress path at (%s)', 'wpbtd'), $path));
		$processed_files = $this->config->get_processed_files();
		$file_list = new File_List();
		$next_check = 0;
		if (file_exists($path)) {
			$source = realpath($path);
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);
			foreach ($files as $fileInfo) {
				$file = $fileInfo->getPathname();

				if (time() > $next_check) {
					if (!$this->config->in_progress())
						return;

					$this->config->add_processed_files($processed_files);
					$next_check = time() + 5;
				}

				if ($file_list->is_excluded($file))
					continue;

				if (is_file($file)) {
					if (File_List::in_ignore_list(basename($file)))
						continue;

					if (in_array($file, $processed_files))
						continue;

					if (dirname($file) == $this->config->get_backup_dir())
						continue;

					$this->output->out($source, $file);

					$processed_files[] = $file;
				}
			}
			$this->output->end();
		}
	}

	/**
	 * Backs up the current WordPress database and saves it to
	 * @return string
	 */
	public function backup_database() {
		$db_error = __('Error while accessing database.', 'wpbtd');

		$tables = $this->database->get_results('SHOW TABLES', ARRAY_N);
		if ($tables === false) {
			throw new Exception($db_error . ' (ERROR_1)');
		}

		$dump_location = $this->config->get_backup_dir();

		if (!is_writable($dump_location)) {
			$msg = sprintf(__("A database backup cannot be created because WordPress does not have write access to '%s', please ensure this directory has write access.", 'wpbtd'), $dump_location);
			$this->config->log(WP_Backup_Config::BACKUP_STATUS_WARNING, $msg);
			return false;
		}

		$filename =  $this->get_sql_file_name();
		$handle = fopen($filename, 'w+');
		if (!$handle) {
			throw new Exception(__('Error creating sql dump file.', 'wpbtd') . ' (ERROR_2)');
		}

		$blog_time = strtotime(current_time('mysql'));

		$this->write_to_file($handle, "-- WordPress Backup to Dropbox SQL Dump\n");
		$this->write_to_file($handle, "-- Version " . BACKUP_TO_DROPBOX_VERSION . "\n");
		$this->write_to_file($handle, "-- http://wpb2d.com\n");
		$this->write_to_file($handle, "-- Generation Time: " . date("F j, Y", $blog_time) . " at " . date("H:i", $blog_time) . "\n\n");
		$this->write_to_file($handle, 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";' . "\n\n");

		//I got this out of the phpMyAdmin database dump to make sure charset is correct
		$this->write_to_file($handle, "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n");
		$this->write_to_file($handle, "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n");
		$this->write_to_file($handle, "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n");
		$this->write_to_file($handle, "/*!40101 SET NAMES utf8 */;\n\n");

		$this->write_to_file($handle, "--\n-- Create and use the backed up database\n--\n\n");
		$this->write_to_file($handle, "CREATE DATABASE " . DB_NAME . ";\n");
		$this->write_to_file($handle, "USE " . DB_NAME . ";\n\n");

		foreach ($tables as $t) {
			$table = $t[0];

			$this->write_to_file($handle, "--\n-- Table structure for table `$table`\n--\n\n");

			$table_create = $this->database->get_row("SHOW CREATE TABLE $table", ARRAY_N);
			if ($table_create === false) {
				throw new Exception($db_error . ' (ERROR_3)');
			}
			$this->write_to_file($handle, $table_create[1] . ";\n\n");

			$table_count = $this->database->get_var("SELECT COUNT(*) FROM $table");
			if ($table_count == 0) {
				$this->write_to_file($handle, "--\n-- Table `$table` is empty\n--\n\n");
				continue;
			} else {
				$this->write_to_file($handle, "--\n-- Dumping data for table `$table`\n--\n\n");
				for ($i = 0; $i < $table_count; $i = $i + self::SELECT_QUERY_LIMIT) {
					$table_data = $this->database->get_results("SELECT * FROM $table LIMIT " . self::SELECT_QUERY_LIMIT . " OFFSET $i", ARRAY_A);
					if ($table_data === false) {
						throw new Exception($db_error . ' (ERROR_4)');
					}

					$fields = '`' . implode('`, `', array_keys($table_data[0])) . '`';
					$this->write_to_file($handle, "INSERT INTO `$table` ($fields) VALUES \n");

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
					$this->write_to_file($handle, rtrim($out, ",\n") . ";\n\n");
				}
			}
		}

		if (!fclose($handle)) {
			throw new Exception(__('Error closing sql dump file.', 'wpbtd') . ' (ERROR_5)');
		}

		return true;
	}

	/**
	 * Write the contents of out to the handle provided. Raise an exception if this fails
	 * @throws Exception
	 * @param  $handle
	 * @param  $out
	 * @return void
	 */
	private function write_to_file($handle, $out) {
		if (!fwrite($handle, $out)) {
			throw new Exception(__('Error writing to sql dump file.', 'wpbtd') . ' (ERROR_6)');
		}
	}

	/**
	 * Schedules a backup to start now
	 * @return void
	 */
	public function backup_now() {
		wp_schedule_single_event(time(), 'execute_instant_drobox_backup');
	}

	/**
	 * Execute the backup
	 * @return bool
	 */
	public function execute() {
		$manager = WP_Backup_Extension_Manager::construct();
		$this->config->set_in_progress(true);
		try {

			$this->config->set_memory_limit();
			$this->config->set_time_limit();

			if (!$this->dropbox->is_authorized()) {
				$this->config->log(WP_Backup_Config::BACKUP_STATUS_FAILED, __('Your Dropbox account is not authorized yet.', 'wpbtd'));
				return;
			}

			$dump_location = $this->config->get_backup_dir();

			$sql_file_name = $this->get_sql_file_name();
			$processed_files = $this->config->get_processed_files();
			if (!in_array($sql_file_name, $processed_files)) {
				$this->config->set_current_action(__('Creating SQL backup', 'wpbtd'));
				$this->backup_database();
				$this->output->out(realpath(ABSPATH), $sql_file_name);
			}

			$manager->on_start();
			$this->backup_path(ABSPATH);

			if (dirname (WP_CONTENT_DIR) . '/' != ABSPATH)
				$this->backup_path(WP_CONTENT_DIR);

			if (file_exists($sql_file_name))
				unlink($sql_file_name);

			$manager->on_complete();
			$this->config->log(WP_Backup_Config::BACKUP_STATUS_FINISHED);

		} catch (Exception $e) {
			if ($e->getMessage() == 'Unauthorized')
				$this->config->log(WP_Backup_Config::BACKUP_STATUS_FAILED, __('The plugin is no longer authorized with Dropbox.', 'wpbtd'));
			else
				$this->config->log(WP_Backup_Config::BACKUP_STATUS_FAILED, "Exception - " . $e->getMessage());

			$manager->on_failure();

		}
		$this->config->set_last_backup_time(time());
		$this->config->set_in_progress(false);
		$this->config->clean_up();
	}

	/**
	 * Stops the backup
	 */
	public function stop() {
		$this->config->log(WP_Backup_Config::BACKUP_STATUS_WARNING, __('Backup stopped by user.', 'wpbtd'));
		$this->config->set_in_progress(false);
		$this->config->set_last_backup_time(time());
		$this->config->clean_up();
	}

	public function create_silence_file() {
		$silence = $this->config->get_backup_dir() . DIRECTORY_SEPARATOR . 'index.php';
		if (!file_exists($silence)) {
			$fh = @fopen($silence, 'w');
			if (!$fh) {
				throw new Exception(
					sprintf(
						__("WordPress does not have write access to '%s'. Please grant it write privileges before using this plugin."),
						$this->config->get_backup_dir()
					)
				);
			}
			fwrite($fh, "<?php\n// Silence is golden.\n");
			fclose($fh);
		}
	}

	public function create_dump_dir() {
		$dump_dir = $this->config->get_backup_dir();
		if (!file_exists($dump_dir)) {
			//It really pains me to use the error suppressor here but PHP error handling sucks :-(
			if (!@mkdir($dump_dir)) {
				throw new Exception(
				sprintf(
						__("A database backup cannot be created because WordPress does not have write access to '%s', please create the folder '%s' manually.", 'wpbtd'),
						dirname($dump_dir), basename($dump_dir)
					)
				);
			}
		}
		return $dump_dir;
	}

	private function get_sql_file_name() {
		return rtrim($this->config->get_backup_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . DB_NAME . '-backup.sql';
	}
}
