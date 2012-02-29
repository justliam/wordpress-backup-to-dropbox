<?php
/*
Plugin Name: WordPress Backup to Dropbox
Plugin URI: http://wpb2d.com
Description: Keep your valuable WordPress website, its media and database backed up to Dropbox in minutes with this sleek, easy to use plugin.
Version: 0.9.4
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
define( 'USE_BUNDLED_PEAR', true );
define( 'BACKUP_TO_DROPBOX_VERSION', '0.9.4' );

require_once( 'Dropbox_API/autoload.php' );
require_once( 'Classes/class-file-list.php' );
require_once( 'Classes/class-dropbox-facade.php' );
require_once( 'Classes/class-wp-backup-config.php' );
require_once( 'Classes/class-wp-backup.php' );

//We need to set the PEAR_Includes folder in the path
if (USE_BUNDLED_PEAR)
	set_include_path( dirname( __FILE__ ) . '/PEAR_Includes' . PATH_SEPARATOR . get_include_path() );
else
	set_include_path( get_include_path() . PATH_SEPARATOR . dirname( __FILE__ ) . '/PEAR_Includes' );

/**
 * A wrapper function that adds an options page to setup Dropbox Backup
 * @return void
 */
function backup_to_dropbox_admin_menu() {
	$imgUrl = rtrim( WP_PLUGIN_URL, '/' ) . '/wordpress-backup-to-dropbox/Images/WordPressBackupToDropbox_16.png';

	$text = __( 'Backup', 'wpbtd' );
	add_utility_page( $text, $text, 'edit_plugins', 'backup-to-dropbox', 'backup_to_dropbox_admin_menu_contents', $imgUrl);

	$text = __( 'Settings', 'wpbtd' );
	add_submenu_page( 'backup-to-dropbox', $text, $text, 'edit_plugins', 'backup-to-dropbox', 'backup_to_dropbox_admin_menu_contents' );

	$backup = new WP_Backup_Config();
	$text = $backup->is_scheduled() ? __( 'Monitor Backup', 'wpbtd' ) : __( 'Backup Now', 'wpbtd' );
	add_submenu_page( 'backup-to-dropbox', $text, $text, 'edit_plugins', 'backup-to-dropbox-monitor', 'backup_to_dropbox_monitor' );
}

/**
 * A wrapper function that includes the backup to Dropbox options page
 * @return void
 */
function backup_to_dropbox_admin_menu_contents() {
	$uri = rtrim( WP_PLUGIN_URL, '/' ) . '/wordpress-backup-to-dropbox';
	include( 'Views/wp-backup-to-dropbox-options.php' );
}

/**
 * A wrapper function that includes the backup to Dropbox monitor page
 * @return void
 */
function backup_to_dropbox_monitor() {
	$dropbox = new Dropbox_Facade();
	if ( !$dropbox->is_authorized() ) {
		backup_to_dropbox_admin_menu_contents();
	} else {
		$uri = rtrim( WP_PLUGIN_URL, '/' ) . '/wordpress-backup-to-dropbox';
		include( 'Views/wp-backup-to-dropbox-monitor.php' );
	}
}

/**
 * A wrapper function for the file tree AJAX request
 * @return void
 */
function backup_to_dropbox_file_tree() {
	include( 'Views/wp-backup-to-dropbox-file-tree.php' );
	die();
}

/**
 * A wrapper function for the progress AJAX request
 * @return void
 */
function backup_to_dropbox_progress() {
	include( 'Views/wp-backup-to-dropbox-progress.php' );
	die();
}

/**
 * A wrapper function that executes the backup
 * @return void
 */
function execute_drobox_backup() {
	wp_schedule_single_event( time(), 'run_dropbox_backup_hook' );
	wp_schedule_event( time(), 'every_min', 'monitor_dropbox_backup_hook' );
}

/**
 * @return void
 */
function monitor_dropbox_backup() {
	$config = new WP_Backup_Config();
	$action = $config->get_current_action();

	//5 mins to allow for socket timeouts and long uploads
	if ( $config->in_progress() && ( $action['time'] < strtotime( current_time( 'mysql' ) ) - 300  ) ) {
		$config->log( WP_Backup::BACKUP_STATUS_WARNING, __( 'The backup process appears to have gone away. Resuming backup.' ) );
		wp_schedule_single_event( time(), 'run_dropbox_backup_hook' );
	}
}

/**
 * @return void
 */
function run_dropbox_backup() {
	$backup = new WP_Backup( new Dropbox_Facade(), new WP_Backup_Config() );
	$backup->execute();
}

/**
 * Adds a set of custom intervals to the cron schedule list
 * @param  $schedules
 * @return array
 */
function backup_to_dropbox_cron_schedules( $schedules ) {
	$new_schedules = array(
		'every_min' => array(
			'interval' => 60,
			'display' => 'every_min'
		),
		'daily' => array(
			'interval' => 86400,
			'display' => 'Weekly'
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
	return array_merge( $schedules, $new_schedules );
}

//WordPress filters and actions
add_filter( 'cron_schedules', 'backup_to_dropbox_cron_schedules' );
add_action( 'monitor_dropbox_backup_hook', 'monitor_dropbox_backup' );
add_action( 'run_dropbox_backup_hook', 'run_dropbox_backup' );
add_action( 'execute_periodic_drobox_backup', 'execute_drobox_backup' );
add_action( 'execute_instant_drobox_backup', 'execute_drobox_backup' );
add_action( 'admin_menu', 'backup_to_dropbox_admin_menu' );
add_action( 'wp_ajax_file_tree', 'backup_to_dropbox_file_tree' );
add_action( 'wp_ajax_progress', 'backup_to_dropbox_progress' );

//i18n language text domain
load_plugin_textdomain( 'wpbtd', true, 'wordpress-backup-to-dropbox/Languages/' );
