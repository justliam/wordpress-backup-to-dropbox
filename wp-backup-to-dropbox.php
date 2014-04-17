<?php
/*
Plugin Name: WordPress Backup to Dropbox
Plugin URI: http://wpb2d.com
Description: Keep your valuable WordPress website, its media and database backed up in Dropbox! Need help? Please email support@wpb2d.com
Version: 1.9
Author: Michael De Wildt
Author URI: http://www.mikeyd.com.au
License: Copyright 2011-2014 Awesoft Pty. Ltd. (email : michael.dewildt@gmail.com)

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
define('BACKUP_TO_DROPBOX_VERSION', '1.9');
define('BACKUP_TO_DROPBOX_DATABASE_VERSION', '2');
define('EXTENSIONS_DIR', str_replace('/', DIRECTORY_SEPARATOR, WP_CONTENT_DIR . '/plugins/wordpress-backup-to-dropbox/Classes/Extension/'));
define('CHUNKED_UPLOAD_THREASHOLD', 10485760); //10 MB
define('MINUMUM_PHP_VERSION', '5.2.16');
define('NO_ACTIVITY_WAIT_TIME', 300); //5 mins to allow for socket timeouts and long uploads

if (function_exists('spl_autoload_register')) {
    spl_autoload_register('wpb2d_autoload');
} else {
    require_once 'Dropbox/Dropbox/API.php';
    require_once 'Dropbox/Dropbox/OAuth/Consumer/ConsumerAbstract.php';
    require_once 'Dropbox/Dropbox/OAuth/Consumer/Curl.php';

    require_once 'Classes/Extension/Base.php';
    require_once 'Classes/Extension/Manager.php';
    require_once 'Classes/Extension/DefaultOutput.php';

    require_once 'Classes/Processed/Base.php';
    require_once 'Classes/Processed/Files.php';
    require_once 'Classes/Processed/DBTables.php';

    require_once 'Classes/DatabaseBackup.php';
    require_once 'Classes/FileList.php';
    require_once 'Classes/DropboxFacade.php';
    require_once 'Classes/Config.php';
    require_once 'Classes/BackupController.php';
    require_once 'Classes/Logger.php';
    require_once 'Classes/Factory.php';
    require_once 'Classes/UploadTracker.php';
}

function wpb2d_autoload($className)
{
    $fileName = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

    if (preg_match('/^WPB2D/', $fileName)) {
        $fileName = 'Classes' . str_replace('WPB2D', '', $fileName);
    } elseif (preg_match('/^Dropbox/', $fileName)) {
        $fileName = 'Dropbox' . DIRECTORY_SEPARATOR . $fileName;
    } else {
        return false;
    }

    $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . $fileName;

    if (file_exists($path)) {
        require_once $path;
    }
}

function wpb2d_style()
{
    //Register stylesheet
    wp_register_style('wpb2d-style', plugins_url('wp-backup-to-dropbox.css', __FILE__) );
    wp_enqueue_style('wpb2d-style');
}

/**
 * A wrapper function that adds an options page to setup Dropbox Backup
 * @return void
 */
function backup_to_dropbox_admin_menu()
{
    $imgUrl = rtrim(WP_PLUGIN_URL, '/') . '/wordpress-backup-to-dropbox/Images/WordPressBackupToDropbox_16.png';

    $text = __('WPB2D', 'wpbtd');
    add_menu_page($text, $text, 'activate_plugins', 'backup-to-dropbox', 'backup_to_dropbox_admin_menu_contents', $imgUrl, '80.0564');

    $text = __('Backup Settings', 'wpbtd');
    add_submenu_page('backup-to-dropbox', $text, $text, 'activate_plugins', 'backup-to-dropbox', 'backup_to_dropbox_admin_menu_contents');

    if (version_compare(PHP_VERSION, MINUMUM_PHP_VERSION) >= 0) {
        $text = __('Backup Monitor', 'wpbtd');
        add_submenu_page('backup-to-dropbox', $text, $text, 'activate_plugins', 'backup-to-dropbox-monitor', 'backup_to_dropbox_monitor');

        WPB2D_Extension_Manager::construct()->add_menu_items();

        $text = __('Premium Extensions', 'wpbtd');
        add_submenu_page('backup-to-dropbox', $text, $text, 'activate_plugins', 'backup-to-dropbox-premium', 'backup_to_dropbox_premium');
    }
}

/**
 * A wrapper function that includes the backup to Dropbox options page
 * @return void
 */
function backup_to_dropbox_admin_menu_contents()
{
    $uri = rtrim(WP_PLUGIN_URL, '/') . '/wordpress-backup-to-dropbox';

    if(version_compare(PHP_VERSION, MINUMUM_PHP_VERSION) >= 0) {
        include 'Views/wpb2d-options.php';
    } else {
        include 'Views/wpb2d-deprecated.php';
    }
}

/**
 * A wrapper function that includes the backup to Dropbox monitor page
 * @return void
 */
function backup_to_dropbox_monitor()
{
    if (!WPB2D_Factory::get('dropbox')->is_authorized()) {
        backup_to_dropbox_admin_menu_contents();
    } else {
        $uri = rtrim(WP_PLUGIN_URL, '/') . '/wordpress-backup-to-dropbox';
        include 'Views/wpb2d-monitor.php';
    }
}

/**
 * A wrapper function that includes the backup to Dropbox premium page
 * @return void
 */
function backup_to_dropbox_premium()
{
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-tabs');

    $uri = rtrim(WP_PLUGIN_URL, '/') . '/wordpress-backup-to-dropbox';
    include 'Views/wpb2d-premium.php';
}

/**
 * A wrapper function for the file tree AJAX request
 * @return void
 */
function backup_to_dropbox_file_tree()
{
    include 'Views/wpb2d-file-tree.php';
    die();
}

/**
 * A wrapper function for the progress AJAX request
 * @return void
 */
function backup_to_dropbox_progress()
{
    include 'Views/wpb2d-progress.php';
    die();
}

/**
 * A wrapper function that executes the backup
 * @return void
 */
function execute_drobox_backup()
{
    WPB2D_Factory::get('logger')->delete_log();
    WPB2D_Factory::get('logger')->log(sprintf(__('Backup started on %s.', 'wpbtd'), date("l F j, Y", strtotime(current_time('mysql')))));

    $time = ini_get('max_execution_time');
    WPB2D_Factory::get('logger')->log(sprintf(
        __('Your time limit is %s and your memory limit is %s'),
        $time ? $time . ' ' . __('seconds', 'wpbtd') : __('unlimited', 'wpbtd'),
        ini_get('memory_limit')
    ));

    if (ini_get('safe_mode')) {
        WPB2D_Factory::get('logger')->log(__("Safe mode is enabled on your server so the PHP time and memory limit cannot be set by the backup process. So if your backup fails it's highly probable that these settings are too low.", 'wpbtd'));
    }

    WPB2D_Factory::get('config')->set_option('in_progress', true);

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
function monitor_dropbox_backup()
{
    $config = WPB2D_Factory::get('config');
    $mtime = filemtime(WPB2D_Factory::get('logger')->get_log_file());

    if ($config->get_option('in_progress') && ($mtime < time() - NO_ACTIVITY_WAIT_TIME)) {
        WPB2D_Factory::get('logger')->log(sprintf(__('There has been no backup activity for a long time. Attempting to resume the backup.' , 'wpbtd'), 5));
        $config->set_option('is_running', false);

        wp_schedule_single_event(time(), 'run_dropbox_backup_hook');
    }
}

/**
 * @return void
 */
function run_dropbox_backup()
{
    $options = WPB2D_Factory::get('config');
    if (!$options->get_option('is_running')) {
        $options->set_option('is_running', true);
        WPB2D_BackupController::construct()->execute();
    }
}

/**
 * Adds a set of custom intervals to the cron schedule list
 * @param  $schedules
 * @return array
 */
function backup_to_dropbox_cron_schedules($schedules)
{
    $new_schedules = array(
        'every_min' => array(
            'interval' => 60,
            'display' => 'WPB2D - Monitor'
        ),
        'daily' => array(
            'interval' => 86400,
            'display' => 'WPB2D - Daily'
        ),
        'weekly' => array(
            'interval' => 604800,
            'display' => 'WPB2D - Weekly'
        ),
        'fortnightly' => array(
            'interval' => 1209600,
            'display' => 'WPB2D - Fortnightly'
        ),
        'monthly' => array(
            'interval' => 2419200,
            'display' => 'WPB2D - Once Every 4 weeks'
        ),
        'two_monthly' => array(
            'interval' => 4838400,
            'display' => 'WPB2D - Once Every 8 weeks'
        ),
        'three_monthly' => array(
            'interval' => 7257600,
            'display' => 'WPB2D - Once Every 12 weeks'
        ),
    );

    return array_merge($schedules, $new_schedules);
}

function wpb2d_install()
{
    $wpdb = WPB2D_Factory::db();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $table_name = $wpdb->prefix . 'wpb2d_options';
    dbDelta("CREATE TABLE $table_name (
        name varchar(50) NOT NULL,
        value varchar(255) NOT NULL,
        UNIQUE KEY name (name)
    );");

    $table_name = $wpdb->prefix . 'wpb2d_processed_files';
    dbDelta("CREATE TABLE $table_name (
        file varchar(255) NOT NULL,
        offset int NOT NULL DEFAULT 0,
        uploadid varchar(50),
        UNIQUE KEY file (file)
    );");

    $table_name = $wpdb->prefix . 'wpb2d_processed_dbtables';
    dbDelta("CREATE TABLE $table_name (
        name varchar(255) NOT NULL,
        count int NOT NULL DEFAULT 0,
        UNIQUE KEY name (name)
    );");

    $table_name = $wpdb->prefix . 'wpb2d_excluded_files';
    dbDelta("CREATE TABLE $table_name (
        file varchar(255) NOT NULL,
        isdir tinyint(1) NOT NULL,
        UNIQUE KEY file (file)
    );");

    //Ensure that there where no insert errors
    $errors = array();

    global $EZSQL_ERROR;
    if ($EZSQL_ERROR) {
        foreach ($EZSQL_ERROR as $error) {
            if (preg_match("/^CREATE TABLE {$wpdb->prefix}wpb2d_/", $error['query']))
                $errors[] = $error['error_str'];
        }

        delete_option('wpb2d-init-errors');
        add_option('wpb2d-init-errors', implode($errors, '<br />'), false, 'no');
    }

    //Only set the DB version if there are no errors
    if (empty($errors)) {
        WPB2D_Factory::get('config')->set_option('database_version', BACKUP_TO_DROPBOX_DATABASE_VERSION);
    }
}

function wpb2d_init()
{
    try {
        if (WPB2D_Factory::get('config')->get_option('database_version') < BACKUP_TO_DROPBOX_DATABASE_VERSION) {
            wpb2d_install();
        }

        if (!get_option('wpb2d-premium-extensions')) {
            add_option('wpb2d-premium-extensions', array(), false, 'no');
        }

    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

function get_sanitized_home_path()
{
    //Needed for get_home_path() function and may not be loaded
    require_once(ABSPATH . 'wp-admin/includes/file.php');

    //If site address and WordPress address differ but are not in a different directory
    //then get_home_path will return '/' and cause issues.
    $home_path = get_home_path();
    if ($home_path == '/') {
        $home_path = ABSPATH;
    }

    return rtrim(str_replace('/', DIRECTORY_SEPARATOR, $home_path), DIRECTORY_SEPARATOR);
}

//More cron shedules
add_filter('cron_schedules', 'backup_to_dropbox_cron_schedules');

//Backup hooks
add_action('monitor_dropbox_backup_hook', 'monitor_dropbox_backup');
add_action('run_dropbox_backup_hook', 'run_dropbox_backup');
add_action('execute_periodic_drobox_backup', 'execute_drobox_backup');
add_action('execute_instant_drobox_backup', 'execute_drobox_backup');

//Register database install
register_activation_hook(__FILE__, 'wpb2d_install');

add_action('admin_init', 'wpb2d_init');
add_action('admin_enqueue_scripts', 'wpb2d_style');

//i18n language text domain
load_plugin_textdomain('wpbtd', false, 'wordpress-backup-to-dropbox/Languages/');

if (is_admin()) {
    //WordPress filters and actions
    add_action('wp_ajax_file_tree', 'backup_to_dropbox_file_tree');
    add_action('wp_ajax_progress', 'backup_to_dropbox_progress');

    if (defined('MULTISITE') && MULTISITE) {
        add_action('network_admin_menu', 'backup_to_dropbox_admin_menu');
    } else {
        add_action('admin_menu', 'backup_to_dropbox_admin_menu');
    }
}
