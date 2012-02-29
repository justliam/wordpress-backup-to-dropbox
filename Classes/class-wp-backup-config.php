<?php
/**
 * A class with functions the perform a backup of WordPress
 *
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
include_once( 'class-file-list.php' );
class WP_Backup_Config {

	const MAX_HISTORY_ITEMS = 100;

	public function __construct() {
		$history = get_option( 'backup-to-dropbox-history' );
		if ( !is_array( $history ) ) {
			add_option( 'backup-to-dropbox-history', array(), null, 'no' );
			$history = array();
		}

		$dumpLocation = 'wp-content/backups';
		if (defined( 'WP_CONTENT_DIR' ) && WP_CONTENT_DIR)
			$dumpLocation = basename( WP_CONTENT_DIR ) . '/backups';

		$options = $this->get_options();
		if ( !is_array( $options ) || ( !isset( $options['dump_location'] ) || !isset( $options['dropbox_location'] ) ) ) {
			$options = array(
				'dump_location' => $dumpLocation,
				'dropbox_location' => 'WordPressBackup',
				'last_backup_time' => false,
				'in_progress' => false,
			);
			add_option( 'backup-to-dropbox-options', $options, null, 'no' );
		}

		$actions = $this->get_actions();
		if ( !is_array( $actions ) ) {
			add_option( 'backup-to-dropbox-actions', array(), null, 'no' );
		}
	}

	public function get_options() {
		return get_option( 'backup-to-dropbox-options' );
	}

	public function get_history() {
		$hist = get_option( 'backup-to-dropbox-history' );
		krsort( $hist );
		return $hist;
	}

	public function get_actions() {
		return get_option( 'backup-to-dropbox-actions' );
	}

	public function set_time_limit() {
		if ( ini_get( 'safe_mode' ) ) {
			if ( ini_get( 'max_execution_time' ) != 0 ) {
				$this->log( self::BACKUP_STATUS_WARNING,
							__( 'This php installation is running in safe mode so the time limit cannot be set.', 'wpbtd' ) . ' ' .
							sprintf( __( 'Click %s for more information.', 'wpbtd' ),
									 '<a href="http://www.mikeyd.com.au/2011/05/24/setting-the-maximum-execution-time-when-php-is-running-in-safe-mode/">' . __( 'here', 'wpbtd' ) . '</a>' ) );
				return ini_get( 'max_execution_time' ) - 5; //Lets leave 5 seconds of padding
			}
		} else {
			set_time_limit( 0 );
		}
		return 0;
	}

	public function set_current_action( $msg, $file = null ) {
		$actions = get_option( 'backup-to-dropbox-actions' );
		if ( !is_array( $actions ) ) {
			$actions = array();
		}
		$actions[] = array(
			'time' => strtotime( current_time( 'mysql' ) ),
			'message' => $msg,
			'file' => $file
		);
		update_option( 'backup-to-dropbox-actions', $actions );
	}

	public function in_progress() {
		$options = $this->get_options();
		return $options['in_progress'];
	}

	public function is_scheduled() {
		return
			wp_get_schedule( 'monitor_dropbox_backup_hook' ) !== false ||
			wp_get_schedule( 'execute_instant_drobox_backup' ) !== false;
	}

	public function set_in_progress( $bool ) {
		$options = $this->get_options();
		$options['in_progress'] = $bool;
		update_option( 'backup-to-dropbox-options', $options );
	}

	public function get_current_action() {
		return end( get_option( 'backup-to-dropbox-actions' ) );
	}

	public function clear_history() {
		update_option( 'backup-to-dropbox-history', array() );
	}

	public function set_schedule( $day, $time, $frequency ) {
		$blog_time = strtotime( date( 'Y-m-d H', strtotime( current_time( 'mysql' ) ) ) . ':00:00' );

		//Grab the date in the blogs timezone
		$date = date( 'Y-m-d', $blog_time );

		//Check if we need to schedule the backup in the future
		$time_arr = explode( ':', $time );
		$current_day = date( 'D', $blog_time );
		if ( $day && ( $current_day != $day ) ) {
			$date = date( 'Y-m-d', strtotime( "next $day" ) );
		} else if ( (int)$time_arr[0] <= (int)date( 'H', $blog_time ) ) {
			if ( $day ) {
				$date = date( 'Y-m-d', strtotime( "+7 days", $blog_time ) );
			} else {
				$date = date( 'Y-m-d', strtotime( "+1 day", $blog_time ) );
			}
		}

		$timestamp = wp_next_scheduled( 'execute_periodic_drobox_backup' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'execute_periodic_drobox_backup' );
		}

		//This will be in the blogs timezone
		$scheduled_time = strtotime( $date . ' ' . $time );

		//Convert the selected time to that of the server
		$server_time = strtotime( date( 'Y-m-d H' ) . ':00:00' ) + ( $scheduled_time - $blog_time );

		wp_schedule_event( $server_time, $frequency, 'execute_periodic_drobox_backup' );
	}

	public function get_schedule() {
		$time = wp_next_scheduled( 'execute_periodic_drobox_backup' );
		$frequency = wp_get_schedule( 'execute_periodic_drobox_backup' );
		if ( $time && $frequency ) {
			//Convert the time to the blogs timezone
			$blog_time = strtotime( date( 'Y-m-d H', strtotime( current_time( 'mysql' ) ) ) . ':00:00' );
			$blog_time += $time - strtotime( date( 'Y-m-d H' ) . ':00:00' );
			$schedule = array( $blog_time, $frequency );
		}
		return $schedule;
	}

	public function set_options( $dump_location, $dropbox_location ) {
		static $regex = '/[^A-Za-z0-9-_.\/]/';
		$errors = array();
		$error_msg = __( 'Invalid directory path. Path must only contain alphanumeric characters and the forward slash (\'/\') to separate directories.', 'wpbtd' );

		preg_match( $regex, $dump_location, $matches );
		if ( !empty( $matches ) ) {
			$errors['dump_location'] = array(
				'original' => $dump_location,
				'message' => $error_msg
			);
		}

		preg_match( $regex, $dropbox_location, $matches );
		if ( !empty( $matches ) ) {
			$errors['dropbox_location'] = array(
				'original' => $dropbox_location,
				'message' => $error_msg
			);
		}

		if ( empty( $errors ) ) {
			$dump_location = ltrim( $dump_location, '/' );
			$dropbox_location = ltrim( $dropbox_location, '/' );

			$dump_location = rtrim( $dump_location, '/' );
			$dropbox_location = rtrim( $dropbox_location, '/' );

			$dump_location = preg_replace( '/[\/]+/', '/', $dump_location );
			$dropbox_location = preg_replace( '/[\/]+/', '/', $dropbox_location );

			$options = $this->get_options();
			$options['dump_location'] = $dump_location;
			$options['dropbox_location'] = $dropbox_location;

			update_option( 'backup-to-dropbox-options', $options );
		}

		return $errors;
	}

	public function set_last_backup_time( $time ) {
		$options = $this->get_options();
		$options['last_backup_time'] = $time;
		update_option( 'backup-to-dropbox-options', $options );
	}

	public function get_uploaded_files() {
		$actions = $this->get_actions();
		$files = array();
		foreach ( $actions as $action ) {
			$files[] = $action['file'];
		}
		return $files;
	}

	public function clean_up() {
		wp_clear_scheduled_hook( 'monitor_dropbox_backup_hook' );
		wp_clear_scheduled_hook( 'run_dropbox_backup_hook' );
		update_option( 'backup-to-dropbox-actions', array() );
	}

	public function log( $status, $msg = null ) {
		$history = $this->get_history();
		if ( count( $history ) >= self::MAX_HISTORY_ITEMS ) {
			array_shift( $history );
		}
		$history[] = array( strtotime( current_time( 'mysql' ) ), $status, $msg );
		update_option( 'backup-to-dropbox-history', $history );
	}
}