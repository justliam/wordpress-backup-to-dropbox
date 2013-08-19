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
class WP_Backup {
	private $dropbox;
	private $config;
	private $output;

	public static function construct() {
		return new self();
	}

	public function __construct($output = null) {
		$this->config = WP_Backup_Registry::config();
		$this->dropbox = WP_Backup_Registry::dropbox();
		$this->output = $output ? $output : WP_Backup_Extension_Manager::construct()->get_output();

		$this->db_core = new WP_Backup_Database_Core();
		$this->db_plugins = new WP_Backup_Database_Plugins();
	}

	public function backup_path($path, $dropbox_path = null, $always_include = array()) {
		if (!$this->config->get_option('in_progress'))
			return;

		if (!$dropbox_path)
			$dropbox_path = get_sanitized_home_path();

		$file_list = new File_List();

		$current_processed_files = $uploaded_files = array();

		$next_check = time() + 5;
		$total_files = $this->config->get_option('total_file_count');
		if ($total_files < 1800) //I doub't very much a wp installation can get smaller then this
			$total_files = 1800;

		$processed_files = new WP_Backup_Processed_Files();

		$processed_file_count = $processed_files->get_file_count();

		if (file_exists($path)) {
			$source = realpath($path);
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);
			foreach ($files as $file_info) {
				$file = $file_info->getPathname();

				if (time() > $next_check) {
					$this->config->die_if_stopped();

					$percent_done = round(($processed_file_count / $total_files) * 100, 0);
					if ($percent_done > 99)
						$percent_done = 99;

					if ($percent_done < 1)
						$percent_done = 1;

					$processed_files->add_files($current_processed_files);

					WP_Backup_Registry::logger()->log(sprintf(__('Approximately %s%% complete.', 'wpbtd'),	$percent_done), $uploaded_files);

					$next_check = time() + 5;
					$uploaded_files = $current_processed_files = array();
				}

				if (!in_array($file, $always_include) && $file_list->is_excluded($file))
					continue;

				if ($file_list->in_ignore_list($file))
					continue;

				if (is_file($file)) {
					$processed_file = $processed_files->get_file($file);
					if ($processed_file && $processed_file->offset == 0)
						continue;

					if (dirname($file) == $this->config->get_backup_dir() && !in_array($file, $always_include))
						continue;

					if ($this->output->out($dropbox_path, $file, $processed_file)) {
						$uploaded_files[] = array(
							'file' => str_replace($dropbox_path . DIRECTORY_SEPARATOR, '', Dropbox_Facade::remove_secret($file)),
							'mtime' => filemtime($file),
						);

						if ($processed_file && $processed_file->offset > 0)
							$processed_files->file_complete($file);
					}

					$current_processed_files[] = $file;
					$processed_file_count++;
				}
			}

			return $processed_file_count;
		}
	}

	public function execute() {
		$manager = WP_Backup_Extension_Manager::construct();
		$logger = WP_Backup_Registry::logger();

		$this->config->set_time_limit();
		$this->config->set_memory_limit();

		try {

			if (!$this->dropbox->is_authorized()) {
				$logger->log(__('Your Dropbox account is not authorized yet.', 'wpbtd'));
				return;
			}

			if ($this->output->start()) {
				//Create the SQL backups

				$logger->log(__('Creating SQL backup.', 'wpbtd'));

				$this->db_core->execute();
				$this->db_plugins->execute();

				$logger->log(__('SQL backup complete. Starting file backup.', 'wpbtd'));

				//Backup the content dir first
				$processed_files = $this->backup_path(WP_CONTENT_DIR, dirname(WP_CONTENT_DIR), array(
					$this->db_core->get_file(),
					$this->db_plugins->get_file()
				));

				//Now backup the blog root
				$processed_files += $this->backup_path(get_sanitized_home_path());

				//End any output extensions
				$this->output->end();

				//Record the number of files processed to make the progress meter more accurate
				$this->config->set_option('total_file_count', $processed_files);
			}

			$manager->complete();

			//Update log file with stats
			$logger->log(__('Backup complete.', 'wpbtd'));
			$logger->log(sprintf(__('A total of %s files were processed.', 'wpbtd'), $processed_files));
			$logger->log(sprintf(
				__('A total of %dMB of memory was used to complete this backup.', 'wpbtd'),
				(memory_get_usage(true) / 1048576)
			));

			$this->output->clean_up();

			//Process the log file using the default backup output
			$root = false;
			if (get_class($this->output) != 'WP_Backup_Output') {
				$this->output = new WP_Backup_Output();
				$root = true;
			}

			$this->output->set_root($root)->out(get_sanitized_home_path(), $logger->get_log_file());

			$this->config
				->complete()
				->log_finished_time()
				;

		} catch (Exception $e) {
			if ($e->getMessage() == 'Unauthorized') {
				$logger->log(__('The plugin is no longer authorized with Dropbox.', 'wpbtd'));
			} else {
				$logger->log(__('A fatal error occured: ', 'wpbtd') . $e->getMessage());
			}

			$manager->failure();
			$this->stop();
		}

		$this->clean_up();
	}

	public function backup_now() {
		if (defined('WPB2D_TEST_MODE'))
			execute_drobox_backup();
		else
			wp_schedule_single_event(time(), 'execute_instant_drobox_backup');
	}

	public function stop() {
		$this->config->complete();
		$this->clean_up();
	}

	private function clean_up() {
		$this->db_core->remove_file();
		$this->db_plugins->remove_file();
	}

	private static function create_silence_file() {
		$silence = WP_Backup_Registry::config()->get_backup_dir() . DIRECTORY_SEPARATOR . 'index.php';
		if (!file_exists($silence)) {
			$fh = @fopen($silence, 'w');
			if (!$fh) {
				throw new Exception(
					sprintf(
						__("WordPress does not have write access to '%s'. Please grant it write privileges before using this plugin."),
						WP_Backup_Registry::config()->get_backup_dir()
					)
				);
			}
			fwrite($fh, "<?php\n// Silence is golden.\n");
			fclose($fh);
		}
	}

	public static function create_dump_dir() {
		$dump_dir = WP_Backup_Registry::config()->get_backup_dir();
		$error_message  = sprintf(__("WordPress Backup to Dropbox requires write access to '%s', please ensure it exists and has write permissions.", 'wpbtd'), $dump_dir);

		if (!file_exists($dump_dir)) {
			//It really pains me to use the error suppressor here but PHP error handling sucks :-(
			if (!@mkdir($dump_dir)) {
				throw new Exception($error_message);
			}
		} else if (!is_writable($dump_dir)) {
			throw new Exception($error_message);
		}

		self::create_silence_file();
	}
}
