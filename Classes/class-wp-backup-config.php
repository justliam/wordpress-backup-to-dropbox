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

	private $options = array();
	private $schedule = array();
	private $log = array();
	private $actions = array();

	public function __construct( $wpdb = null ) {
		if ( !$wpdb ) {
			global $wpdb;
		}

		$this->database = $wpdb;

		$this->history = get_option( 'backup-to-dropbox-history' );
		if ( !$this->history ) {
			add_option( 'backup-to-dropbox-history', array(), null, 'no' );
			$this->history = array();
		}

		$this->options = get_option( 'backup-to-dropbox-options' );
		if ( !$this->options ) {
			$this->options = array( 'wp-content/backups', 'WordPressBackup' );
			add_option( 'backup-to-dropbox-options', $this->options, null, 'no' );
		}

		$time = wp_next_scheduled( 'execute_periodic_drobox_backup' );
		$frequency = wp_get_schedule( 'execute_periodic_drobox_backup' );
		if ( $time && $frequency ) {
			//Convert the time to the blogs timezone
			$blog_time = strtotime( date( 'Y-m-d H', strtotime( current_time( 'mysql' ) ) ) . ':00:00' );
			$blog_time += $time - strtotime( date( 'Y-m-d H' ) . ':00:00' );
			$this->schedule = array( $blog_time, $frequency );
		}

		if ( !get_option( 'backup-to-dropbox-in-progress' ) ) {
			add_option( 'backup-to-dropbox-in-progress', 'no', null, 'no' );
		}

		$this->actions = get_option( 'backup-to-dropbox-actions' );
		if ( !$this->actions ) {
			add_option( 'backup-to-dropbox-actions', array(), null, 'no' );
		}
	}

	/**
	 * Get the dropbox backup options if we don't have any options set the defaults
	 * @return array - Dump location, Dropbox location, Keep local, Backup count
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Returns the backup history of this wordpress installation
	 * @return array - time, status, message
	 */
	public function get_history() {
		$hist = $this->history;
		if ( !is_array( $hist ) ) {
			$hist = array();
		}
		krsort( $hist );
		return $hist;
	}

	/**
	 * If safe_mode is enabled then we need warn the user that the script may not finish
	 * @throws Exception
	 * @return int
	 */
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

	/**
	 * Sets the last action that was performed during backup
	 * @return bool
	 */
	public function set_current_action( $msg, $file ) {
		$actions = get_option( 'backup-to-dropbox-actions' );
		if ( !is_array( $actions ) ) {
			$actions = array();
		}
		$actions[time()] = array( $msg, $file );
		update_option( 'backup-to-dropbox-current-action', $actions );
	}

	/**
	 * Returns true if a backup is in progress
	 * @return bool
	 */
	public function in_progress() {
		return get_option( 'backup-to-dropbox-in-progress' ) == 'yes';
	}

	/**
	 * Returns true if a backup is in the shedlue
	 * @return bool
	 */
	public function is_sheduled() {
		return
			wp_get_schedule( 'monitor_dropbox_backup_hook' ) !== false ||
			wp_get_schedule( 'execute_instant_drobox_backup' ) !== false;
	}

	/**
	 * Sets if this backup is in progress
	 * @return bool
	 */
	public function set_in_progress($bool) {
		update_option( 'backup-to-dropbox-in-progress', $bool ? 'yes' : 'no' );
	}

	/**
	 * Returns a tuple of the last action time and the file processed
	 * @return array
	 */
	public function get_current_action() {
		return get_option( 'backup-to-dropbox-current-action' );
	}

	/**
	 * Clears the history
	 * @return array
	 */
	public function clear_history() {
		$this->history = array();
		update_option( 'backup-to-dropbox-history', $this->history );
	}

	/**
	 * Sets the day, time and frequency a wordpress backup is to be performed
	 * @param  $day
	 * @param  $time
	 * @param  $frequency
	 * @return void
	 */
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

		$this->schedule = array( $scheduled_time, $frequency );
	}

	/**
	 * Return the backup schedule
	 * @return array - day, time, frequency
	 */
	public function get_schedule() {
		return $this->schedule;
	}

	/**
	 * Set the dropbox backup options
	 * @param  $dump_location - Local backup location
	 * @param  $dropbox_location - Dropbox backup location
	 * @return array()
	 */
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

			$this->options = array( $dump_location, $dropbox_location );
			update_option( 'backup-to-dropbox-options', $this->options );
		}

		return $errors;
	}

	/**
	 * Clears the backup hooks
	 */
	private function clear_hooks()
	{
		wp_clear_scheduled_hook( 'monitor_dropbox_backup_hook' );
		wp_clear_scheduled_hook( 'run_dropbox_backup_hook' );
	}
}