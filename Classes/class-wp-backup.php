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
class WP_Backup {
	private $dropbox;
	private $config;
	private $output;

	public static function construct() {
		return new self();
	}

	public function __construct($dropbox = null, $output = null) {
		$this->dropbox = $dropbox ? $dropbox : Dropbox_Facade::construct();
		$this->output = $output ? $output : WP_Backup_Extension_Manager::construct()->get_output();
		$this->config = WP_Backup_Config::construct();
	}

	public function backup_path($path, $dropbox_path = null, $always_include = array()) {
		if (!$this->config->get_option('in_progress'))
			return;

		if (!$dropbox_path)
			$dropbox_path = get_blog_root_dir();

		$file_list = new File_List();

		$processed_files = $this->config->get_processed_files();
		$current_processed_files = $uploaded_files = array();

		$next_check = time() + 5;
		$total_files = $this->config->get_option('total_file_count');
		if ($total_files < 1500) //I doub't very much a wp installation can get smaller then this
			$total_files = 1500;

		$processed_file_count = count($processed_files);

		if (file_exists($path)) {
			$source = realpath($path);
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);
			foreach ($files as $file_info) {
				$file = $file_info->getPathname();

				if (time() > $next_check) {
					if (!$this->config->get_option('in_progress', true)) {
						$msg = __('Backup stopped by user.', 'wpbtd');
						WP_Backup_Logger::log($msg);
						die($msg);
					}

					$percent_done = round(($processed_file_count / $total_files) * 100, 0);
					if ($percent_done > 99)
						$percent_done = 99;

					$this->config->add_processed_files($current_processed_files);

					WP_Backup_Logger::log(sprintf(__('Approximately %s%% complete.', 'wpbtd'),	$percent_done), $uploaded_files);

					$next_check = time() + 5;
					$uploaded_files = $current_processed_files = array();
				}

				if (!in_array($file, $always_include) && $file_list->is_excluded($file))
					continue;

				if ($file_list->in_ignore_list($file))
					continue;

				if (is_file($file)) {
					if (in_array($file, $processed_files))
						continue;

					if (dirname($file) == $this->config->get_backup_dir() && !in_array($file, $always_include))
						continue;

					if ($this->output->out($dropbox_path, $file)) {
						$uploaded_files[] = array(
							'file' => str_replace($dropbox_path . DIRECTORY_SEPARATOR, '', $file),
							'mtime' => filemtime($file),
						);
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

		$this->config->set_time_limit();
		$this->config->set_memory_limit();

		try {

			if (!$this->dropbox->is_authorized()) {
				WP_Backup_Logger::log(__('Your Dropbox account is not authorized yet.', 'wpbtd'));
				return;
			}

			$core = new WP_Backup_Database_Core();
			$core->execute();

			$plugins = new WP_Backup_Database_Plugins();
			$plugins->execute();

			$manager->on_start();

			//Backup the content dir first
			$processed_files = $this->backup_path(WP_CONTENT_DIR, dirname(WP_CONTENT_DIR), array(
				$core->get_file(),
				$plugins->get_file()
			));

			//Now backup the blog root
			$processed_files += $this->backup_path(get_blog_root_dir());

			//Record the number of files processed to make the progress meter more accurate
			$this->config->set_option('total_file_count', $processed_files);

			//Remove the backed up SQL files
			$core->remove_file();
			$plugins->remove_file();

			//Call end hooks
			$this->output->end();
			$manager->on_complete();

			//Update log file with stats
			WP_Backup_Logger::log(__('Backup complete.', 'wpbtd'));
			WP_Backup_Logger::log(sprintf(__('A total of %s files were processed.'), $processed_files));
			WP_Backup_Logger::log(sprintf(
				__('A total of %dMB of memory was used to complete this backup.', 'wpbtd'),
				(memory_get_usage(true) / 1048576)
			));

			//Process the log file using the default backup output
			$root = false;
			if (get_class($this->output) != 'WP_Backup_Output') {
				$this->output = new WP_Backup_Output();
				$root = true;
			}

			$this->output->out(get_blog_root_dir(), WP_Backup_Logger::get_log_file(), $root);

			$this->config
				->complete()
				->log_finished_time()
				;

		} catch (Exception $e) {
			if ($e->getMessage() == 'Unauthorized')
				WP_Backup_Logger::log(__('The plugin is no longer authorized with Dropbox.', 'wpbtd'));
			else
				WP_Backup_Logger::log("A fatal error occured: " . $e->getMessage());

			$manager->on_failure();
			$this->stop();
		}
	}

	public function backup_now() {
		if (defined('WPB2D_TEST_MODE'))
			execute_drobox_backup();
		else
			wp_schedule_single_event(time(), 'execute_instant_drobox_backup');
	}

	public function stop() {
		$this->config->complete();
	}

	private static function create_silence_file() {
		$silence = WP_Backup_Config::get_backup_dir() . DIRECTORY_SEPARATOR . 'index.php';
		if (!file_exists($silence)) {
			$fh = @fopen($silence, 'w');
			if (!$fh) {
				throw new Exception(
					sprintf(
						__("WordPress does not have write access to '%s'. Please grant it write privileges before using this plugin."),
						WP_Backup_Config::get_backup_dir()
					)
				);
			}
			fwrite($fh, "<?php\n// Silence is golden.\n");
			fclose($fh);
		}
	}

	public static function create_dump_dir() {
		$dump_dir = WP_Backup_Config::get_backup_dir();
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
		self::create_silence_file();
	}
}
