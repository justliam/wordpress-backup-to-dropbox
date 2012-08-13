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
	private $dropbox;
	private $config;
	private $output;

	public function __construct($dropbox = null, $output = null) {
		$this->dropbox = $dropbox ? $dropbox : Dropbox_Facade::construct();
		$this->output = $output ? $output : WP_Backup_Extension_Manager::construct()->get_output();
		$this->config = WP_Backup_Config::construct();
	}

	public function backup_path($path) {
		$this->config->set_current_action(sprintf(__('Backing up WordPress path at (%s)', 'wpbtd'), $path));
		$processed_files = $this->config->get_processed_files();
		$file_list = new File_List();
		$next_check = 0;
		if (file_exists($path)) {
			$source = realpath($path);
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);
			foreach ($files as $file_info) {
				$file = $file_info->getPathname();

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

					if (dirname($file) == $this->config->get_backup_dir() && !(substr(basename($file), -4, 4) == '.sql' || substr(basename($file), -8, 8) == '.sql.zip'))
						continue;

					$this->output->out($source, $file);

					$processed_files[] = $file;
				}
			}
			$this->output->end();
		}
	}

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

			$core = new WP_Backup_Database_Core();
			$core->execute();

			$plugins = new WP_Backup_Database_Plugins();
			$plugins->execute();

			$manager->on_start();

			$this->backup_path(ABSPATH);
			if (dirname (WP_CONTENT_DIR) . '/' != ABSPATH)
				$this->backup_path(WP_CONTENT_DIR);

			$core->remove_file();
			$plugins->remove_file();

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

	public function backup_now() {
		wp_schedule_single_event(time(), 'execute_instant_drobox_backup');
	}

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
}
