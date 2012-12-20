<?php
/*
Plugin Name: WordPress Backup to Dropbox
Plugin URI: http://wpb2d.com
Description: Keep your valuable WordPress website, its media and database backed up to Dropbox in minutes with this sleek, easy to use plugin.
Version: 1.4.2
Author: Michael De Wildt
Author URI: http://www.mikeyd.com.au
License: Copyright 2011  Michael De Wildt  (email : michael.dewildt@gmail.com)

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License, version 2, as
		published by the Free Software Foundation.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
define('BACKUP_TO_DROPBOX_VERSION', '1.4.2');
define('EXTENSIONS_DIR', implode(array(WP_CONTENT_DIR, 'plugins', 'wordpress-backup-to-dropbox', 'Extensions'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
define('CHUNKED_UPLOAD_THREASHOLD', 10485760); //10 MB

require_once('Dropbox/Dropbox/API.php');
require_once('Dropbox/Dropbox/OAuth/Consumer/ConsumerAbstract.php');
require_once('Dropbox/Dropbox/OAuth/Consumer/Curl.php');

require_once('Classes/class-file-list.php');
require_once('Classes/class-dropbox-facade.php');
require_once('Classes/class-wp-backup-config.php');
require_once('Classes/class-wp-backup.php');
require_once('Classes/class-wp-backup-database.php');
require_once('Classes/class-wp-backup-database-core.php');
require_once('Classes/class-wp-backup-database-plugins.php');
require_once('Classes/class-wp-backup-output.php');
require_once('Classes/class-wp-backup-extension.php');
require_once('Classes/class-wp-backup-extension-manager.php');
require_once('Classes/class-wp-backup-logger.php');

WP_Backup_Extension_Manager::construct()->init();

/**
 * A wrapper function that adds an options page to setup Dropbox Backup
 * @return void
 */
function backup_to_dropbox_admin_menu() {
	//Register stylesheet
	wp_register_style('wpb2d-style', plugins_url('wp-backup-to-dropbox.css', __FILE__) );

	wp_enqueue_style('wpb2d-style');

	$imgUrl = rtrim(WP_PLUGIN_URL, '/') . '/wordpress-backup-to-dropbox/Images/WordPressBackupToDropbox_16.png';

	$text = __('WPB2D', 'wpbtd');
	add_utility_page($text, $text, 'activate_plugins', 'backup-to-dropbox', 'backup_to_dropbox_admin_menu_contents', $imgUrl);

	$text = __('Backup Settings', 'wpbtd');
	add_submenu_page('backup-to-dropbox', $text, $text, 'activate_plugins', 'backup-to-dropbox', 'backup_to_dropbox_admin_menu_contents');

	$text = __('Backup Log', 'wpbtd');
	add_submenu_page('backup-to-dropbox', $text, $text, 'activate_plugins', 'backup-to-dropbox-monitor', 'backup_to_dropbox_monitor');

	WP_Backup_Extension_Manager::construct()->add_menu_items();

	$text = __('Premium Extensions', 'wpbtd');
	add_submenu_page('backup-to-dropbox', $text, $text, 'activate_plugins', 'backup-to-dropbox-premium', 'backup_to_dropbox_premium');
}

/**
 * A wrapper function that includes the backup to Dropbox options page
 * @return void
 */
function backup_to_dropbox_admin_menu_contents() {
	$uri = rtrim(WP_PLUGIN_URL, '/') . '/wordpress-backup-to-dropbox';
	include('Views/wp-backup-to-dropbox-options.php');
}

/**
 * A wrapper function that includes the backup to Dropbox monitor page
 * @return void
 */
function backup_to_dropbox_monitor() {
	if (!Dropbox_Facade::construct()->is_authorized()) {
		backup_to_dropbox_admin_menu_contents();
	} else {
		$uri = rtrim(WP_PLUGIN_URL, '/') . '/wordpress-backup-to-dropbox';
		include('Views/wp-backup-to-dropbox-monitor.php');
	}
}

/**
 * A wrapper function that includes the backup to Dropbox premium page
 * @return void
 */
function backup_to_dropbox_premium() {
	$uri = rtrim(WP_PLUGIN_URL, '/') . '/wordpress-backup-to-dropbox';
	include('Views/wp-backup-to-dropbox-premium.php');
}

/**
 * A wrapper function for the file tree AJAX request
 * @return void
 */
function backup_to_dropbox_file_tree() {
	include('Views/wp-backup-to-dropbox-file-tree.php');
	die();
}

/**
 * A wrapper function for the progress AJAX request
 * @return void
 */
function backup_to_dropbox_progress() {
	include('Views/wp-backup-to-dropbox-progress.php');
	die();
}

/**
 * A wrapper function that executes the backup
 * @return void
 */
function execute_drobox_backup() {
	@umask(0000);

	WP_Backup_Logger::delete_log();
	WP_Backup_Logger::log(sprintf(__('Backup started on %s.', 'wpbtd'), date("l F j, Y", strtotime(current_time('mysql')))));

	if (ini_get('safe_mode')) {
		$time = ini_get('max_execution_time');
		WP_Backup_Logger::log(sprintf(
			__("%sSafe mode%s is enabled on your server so the PHP time and memory limits cannot be set by the backup process. Your time limit is %s and your memory limit is %s, so if your backup fails it's highly probable that these settings are too low.", 'wpbtd'),
			'<a href="http://php.net/manual/en/features.safe-mode.php">',
			'</a>',
			$time ? $time . __('seconds', 'wpbtd') : __('unlimited', 'wpbtd'),
			ini_get('memory_limit')
		));
	}

	WP_Backup_Config::construct()->set_option('in_progress', true);

	if (defined('WPB2D_TEST_MODE')) {
		run_dropbox_backup();
	} else {
		wp_schedule_single_event(time(), 'run_dropbox_backup_hook');
		wp_schedule_event(time(), 'every_min', 'monitor_dropbox_backup_hook');
	}
}

/**
 * @return void
 */
function monitor_dropbox_backup() {
	$config = WP_Backup_Config::construct();
	$mtime = filemtime(WP_Backup_Logger::get_log_file());

	//5 mins to allow for socket timeouts and long uploads
	if ($config->get_option('in_progress') && ($mtime < time() - 300)) {
		WP_Backup_Logger::log(sprintf(__('There has been no backup activity for a long time. Attempting to resume the backup.' , 'wpbtd'), 5));
		$config->set_option('is_running', false);

		wp_schedule_single_event(time(), 'run_dropbox_backup_hook');
	}
}

/**
 * @return void
 */
function run_dropbox_backup() {
	$options = WP_Backup_Config::construct();
	if (!$options->get_option('is_running')) {
		$options->set_option('is_running', true);
		WP_Backup::construct()->execute();
	}
}

/**
 * Adds a set of custom intervals to the cron schedule list
 * @param  $schedules
 * @return array
 */
function backup_to_dropbox_cron_schedules($schedules) {
	$new_schedules = array(
		'every_min' => array(
			'interval' => 60,
			'display' => 'every_min'
		),
		'daily' => array(
			'interval' => 86400,
			'display' => 'Daily'
		),
		'weekly' => array(
			'interval' => 604800,
			'display' => 'Weekly'
		),
		'fortnightly' => array(
			'interval' => 1209600,
			'display' => 'Fortnightly'
		),
		'monthly' => array(
			'interval' => 2419200,
			'display' => 'Once Every 4 weeks'
		),
		'two_monthly' => array(
			'interval' => 4838400,
			'display' => 'Once Every 8 weeks'
		),
		'three_monthly' => array(
			'interval' => 7257600,
			'display' => 'Once Every 12 weeks'
		),
	);
	return array_merge($schedules, $new_schedules);
}

//Delete unused options from previous versions
delete_option('backup-to-dropbox-actions');
delete_option('backup-to-dropbox-file-list');
delete_option('backup-to-dropbox-log');

//WordPress filters and actions
add_filter('cron_schedules', 'backup_to_dropbox_cron_schedules');
add_action('monitor_dropbox_backup_hook', 'monitor_dropbox_backup');
add_action('run_dropbox_backup_hook', 'run_dropbox_backup');
add_action('execute_periodic_drobox_backup', 'execute_drobox_backup');
add_action('execute_instant_drobox_backup', 'execute_drobox_backup');
add_action('admin_menu', 'backup_to_dropbox_admin_menu');
add_action('wp_ajax_file_tree', 'backup_to_dropbox_file_tree');
add_action('wp_ajax_progress', 'backup_to_dropbox_progress');

//i18n language text domain
load_plugin_textdomain('wpbtd', true, 'wordpress-backup-to-dropbox/Languages/');
