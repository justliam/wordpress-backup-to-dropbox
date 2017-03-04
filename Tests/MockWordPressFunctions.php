<?php
/**
 * @copyright Copyright (C) 2011-2013 Michael De Wild. All rights reserved.
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
require '../vendor/autoload.php';

$loader = new \Mockery\Loader;
$loader->register();

define('ARRAY_A', true);
define('ARRAY_N', true);
define('BACKUP_TO_DROPBOX_VERSION', 99);
define('ABSPATH', dirname(dirname(__FILE__)));
define('WP_CONTENT_DIR', ABSPATH);
define('DB_NAME', 'TestDB');
define('EXTENSIONS_DIR', WP_CONTENT_DIR . '/Classes/Extension/');
define('BACKUP_TO_DROPBOX_MEMORY_LIMIT', 150);
define('CHUNKED_UPLOAD_THREASHOLD', 10485760); //10 MB

date_default_timezone_set('Australia/NSW');
spl_autoload_register('wpb2d_autoload');

global $options;
global $schedule;
global $current_time;
global $remote_url;
global $submenu;

$options = array();
$next_schedule = array();
$schedule = array();

function wpb2d_autoload($className)
{
    global $wpb2d_dir;

    if (!$wpb2d_dir) {
        $wpb2d_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
    }

    $fileName = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

    if (preg_match('/^WPB2D/', $fileName)) {
        $fileName = 'Classes' . str_replace('WPB2D', '', $fileName);
    } elseif (preg_match('/^Dropbox/', $fileName)) {
        $fileName = 'Dropbox' . DIRECTORY_SEPARATOR . $fileName;
    } else {
        return false;
    }

    $path = $wpb2d_dir . DIRECTORY_SEPARATOR . $fileName;

    if (file_exists($path)) {
        require_once $path;
    }
}

function reset_globals()
{
    global $options;
    global $schedule;
    global $current_time;
    $options = array();
    $schedule = array();
    $current_time = array();

    WPB2D_Factory::reset();
}

function add_submenu_page() {
    global $submenu;

    $submenu = func_get_args();
}

function set_option($name, $value)
{
    global $options;

    $options[$name] = $value;
}

function update_option($name, $value)
{
    set_option($name, $value);
}

function get_option($name)
{
    global $options;

    return isset($options[$name]) ? $options[$name] : null;
}

function get_sanitized_home_path()
{
    return ABSPATH;
}

function wp_remote_get($url)
{
    global $remote_url;
    $remote_url = $url;

    $ret['body'] = json_encode(array(
        array(
            'extensionid' => 1,
            'name' => 'name',
            'description' => 'description',
            'file' => 'extension.php',
            'price' => 'price',
            'purchased' => true,
        )
    ));

    return $ret;
}

function WP_Filesystem() {}

function download_url($url)
{
    $file = ABSPATH . '/Tests/file.zip';
    $fh = fopen($file, 'a');
    fwrite($fh, 'WRITE');
    fclose($fh);

    return $file;
}

function unzip_file($file, $dir)
{
    $fh = fopen($dir . 'TestExtension.php', 'w');
    fwrite($fh, "<?php\n");
    fwrite($fh, 'class WPB2D_Extension_TestExtension extends WPB2D_Extension_Base {');
    fwrite($fh, 'public static $lastCalled;');
    fwrite($fh, 'public function start() { self::$lastCalled = "start"; return true; }');
    fwrite($fh, 'public function complete() { self::$lastCalled = "complete"; return true; }');
    fwrite($fh, 'public function failure() { self::$lastCalled = "failure"; return true; }');
    fwrite($fh, 'public function get_menu() { self::$lastCalled = "get_menu"; return true; }');
    fwrite($fh, 'public function get_type() { self::$lastCalled = "get_type"; return self::TYPE_OUTPUT; }');
    fwrite($fh, 'public function is_enabled() { self::$lastCalled = "is_enabled"; return true; }');
    fwrite($fh, 'public function set_enabled($bool) { self::$lastCalled = "set_enabled"; return $bool; }');
    fwrite($fh, '}');
    fclose($fh);

    return true;
}

function is_wp_error($val)
{
    return false;
}

function get_site_url()
{
    return 'http://test.com';
}

function get_bloginfo($key)
{
    return "Mikey's blog";
}

function wp_clear_scheduled_hook($hook)
{
    global $schedule;
    unset($schedule[$hook]);
}

function wp_next_scheduled($key)
{
    global $schedule;

    return array_key_exists($key, $schedule) ? $schedule[$key][0] : false;
}

function wp_get_schedule($key)
{
    global $schedule;

    return array_key_exists($key, $schedule) ? $schedule[$key][1] : false;
}

function __($str, $domain)
{
    return $str;
}

function _n($singular, $plural, $count, $domain)
{
    if ($count > 1) {
        return $plural;
    }

    return $singular;
}

function set_current_time($time)
{
    global $current_time;
    $current_time = $time;
}

function current_time($str)
{
    if ($str != 'mysql') {
        throw new Exception('Current time var must be mysql');
    }
    global $current_time;
    if ($current_time) {
        return $current_time;
    }

    return date('Y-m-d H:i:s');
}

function wp_schedule_event($server_time, $frequency, $hook)
{
    global $schedule;
    $schedule[$hook] = array($server_time, $frequency);
}

function wp_schedule_single_event($server_time, $key)
{
    global $schedule;
    $schedule[$key] = array($server_time);

    return true;
}

function wp_unschedule_event($server_time, $key)
{
    global $schedule;
    if (!array_key_exists($key, $schedule)) {
        throw new Exception("Key '$key' does not exist");
    }
    if ($schedule[$key][0] != $server_time) {
        throw new Exception("Invalid timestamp '$server_time' not equal to '{$schedule[$key][0]}'");
    }

    return $schedule[$key];
}
