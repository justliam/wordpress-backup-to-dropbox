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
define( 'BACKUP_TO_DROPBOX_VERSION', 'UnitTest' );
define( 'ABSPATH', dirname(__FILE__) . '/' );
define( 'WP_CONTENT_DIR', ABSPATH );
define( 'DB_NAME', 'TestDB' );
define( 'EXTENSIONS_DIR', dirname( WP_CONTENT_DIR ) . '/PremiumExtensions/' );

date_default_timezone_set( 'Australia/NSW' );

global $options;
global $schedule;
global $current_time;
global $remote_url;

$options = array();
$next_schedule = array();
$schedule = array();

function reset_globals() {
	global $options;
	global $schedule;
	global $current_time;
	unset( $options );
	unset( $schedule );
	unset( $current_time );
}

function wp_remote_get( $url ) {
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

function download_url( $url ) {
	$file = 'Out/file.zip';
	$fh = fopen($file, 'a');
	fwrite($fh, 'WRITE');
	fclose($fh);
	return $file;
}

function unzip_file( $file, $dir ) {
	$fh = fopen($dir . 'extension.php', 'w');
	fwrite($fh, "<?php\n");
	fwrite($fh, 'function new_func() { return true; }');
	fclose($fh);
	return true;
}

function is_wp_error( $val ) {
	return false;
}

function get_site_url() {
	return 'http://test.com';
}

function get_option( $key ) {
	global $options;
	return array_key_exists( $key, $options ) ? $options[$key] : false;
}

function add_option( $key, $value, $not_used, $load ) {
	if ( $load != 'no' ) {
		throw new Exception( 'Load should be no' );
	}
	update_option( $key, $value );
}

function update_option( $key, $value ) {
	global $options;
	$options[$key] = $value;
}

function wp_clear_scheduled_hook( $hook ) {
	global $schedule;
	unset( $schedule[$hook] );
}

function wp_next_scheduled( $key ) {
	global $schedule;
	return array_key_exists( $key, $schedule ) ? $schedule[$key][0] : false;
}

function wp_get_schedule( $key ) {
	global $schedule;
	return array_key_exists( $key, $schedule ) ? $schedule[$key][1] : false;
}

function __( $str ) {
	return $str;
}

function set_current_time( $time ) {
	global $current_time;
	$current_time = $time;
}

function current_time( $str ) {
	if ( $str != 'mysql' ) {
		throw new Exception( 'Current time var must be mysql' );
	}
	global $current_time;
	if ( $current_time ) {
		return $current_time;
	}
	return date( 'Y-m-d H:i:s' );
}

function wp_schedule_event( $server_time, $frequency, $hook ) {
	global $schedule;
	$schedule[$hook] = array( $server_time, $frequency );
}

function wp_schedule_single_event( $server_time, $key ) {
	global $schedule;
	$schedule[$key] = array( $server_time );
	return true;
}

function wp_unschedule_event( $server_time, $key ) {
	global $schedule;
	if ( !array_key_exists( $key, $schedule ) ) {
		throw new Exception( "Key '$key' does not exist" );
	}
	if ( $schedule[$key][0] != $server_time ) {
		throw new Exception( "Invalid timestamp '$server_time' not equal to '{$schedule[$key][0]}'" );
	}
	return $schedule[$key];
}