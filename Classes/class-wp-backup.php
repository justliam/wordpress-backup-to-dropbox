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
class WP_Backup {

	const BACKUP_STATUS_STARTED = 0;
	const BACKUP_STATUS_FINISHED = 1;
	const BACKUP_STATUS_WARNING = 2;
	const BACKUP_STATUS_FAILED = 3;

	private $dropbox;
	private $config;
	private $database;

	public function __construct( $dropbox, $config, $wpdb = null ) {
		if ( !$wpdb ) global $wpdb;
		$this->database = $wpdb;
		$this->dropbox = $dropbox;
		$this->config = $config;
	}

	/**
	 * Backs up the WordPress blog by checking if each file exists in Dropbox and has changed since the last backup.
	 * Files that are too big to be uploaded due to memory restrictions or fails to upload to Dropbox are skipped
	 * and a warning is logged.
	 *
	 * @param $max_execution_time
	 * @param $dropbox_location
	 * @param $max_execution_time
	 * @return string - Path to the database dump
	 */
	public function backup_path( $path, $dropbox_location ) {
		//Grab the memory limit setting in the php ini to ensure we do not exceed it
		$memory_limit_string = ini_get( 'memory_limit' );
		$memory_limit = ( preg_replace( '/\D/', '', $memory_limit_string ) * 1048576 );

		$suhosin_memory_limit_string = ini_get( 'suhosin.memory_limit' );
		$suhosin_memory_limit = ( preg_replace( '/\D/', '', $suhosin_memory_limit_string ) * 1048576 );

		if ( $suhosin_memory_limit && $suhosin_memory_limit < $memory_limit ) {
			$memory_limit = $suhosin_memory_limit;
		}
		$max_file_size = $memory_limit / 2.5;

		$options = $this->config->get_options();
		$last_backup_time = $options['last_backup_time'];

		$uploaded_files = $this->config->get_uploaded_files();

		$file_list = new File_List( $this->database );
		if ( file_exists( $path ) ) {
			$source = realpath( $path );
			$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source ), RecursiveIteratorIterator::SELF_FIRST );
			foreach ( $files as $file ) {
				if (!$this->config->in_progress())
					return false;

				$file = realpath( $file );
				if ( is_file( $file ) ) {
					$trimmed_file = basename( $file );

					if ( File_List::in_ignore_list( $trimmed_file ) )
						continue;

					if ( in_array( $file, $uploaded_files ) )
						continue;

					if ( $file_list->get_file_state( $file ) == File_List::EXCLUDED )
						continue;

					if ( filesize( $file ) > $max_file_size ) {
						$this->config->log( self::BACKUP_STATUS_WARNING,
									sprintf( __( "file '%s' exceeds 40 percent of your PHP memory limit. The limit must be increased to back up this file.", 'wpbtd' ), basename( $file ) ) );
						continue;
					}

					$dropbox_path = $dropbox_location . DIRECTORY_SEPARATOR . str_replace( $source . DIRECTORY_SEPARATOR, '', $file );
					if ( PHP_OS == 'WINNT' ) {
						//The dropbox api requires a forward slash as the directory separator
						$dropbox_path = str_replace( DIRECTORY_SEPARATOR, '/', $dropbox_path );
					}

					$directory_contents = $this->dropbox->get_directory_contents( dirname( $dropbox_path ) );
					if ( !in_array( $trimmed_file, $directory_contents ) || filemtime( $file ) > $last_backup_time ) {
						try {
							$this->config->set_current_action( __( 'Uploading' ), $file );
							$this->dropbox->upload_file( $dropbox_path, $file );
						} catch ( Exception $e ) {

							if ( $e->getMessage() == 'Unauthorized' )
								throw $e;

							$msg = sprintf( __( "Could not upload '%s' due to the following error: %s", 'wpbtd' ), $file, $e->getMessage() );
							$this->config->log( self::BACKUP_STATUS_WARNING, $msg );
						}
					}
				}
			}
		}
	}

	/**
	 * Backs up the current WordPress database and saves it to
	 * @return string
	 */
	public function backup_database() {
		$db_error = __( 'Error while accessing database.', 'wpbtd' );

		$tables = $this->database->get_results( 'SHOW TABLES', ARRAY_N );
		if ( $tables === false ) {
			throw new Exception( $db_error . ' (ERROR_1)' );
		}

		$options = $this->config->get_options();
		$dump_location = $options['dump_location'];

		if ( !is_writable( $dump_location ) ) {
			$msg = sprintf(__( "A database backup cannot be created because WordPress does not have write access to '%s', please create the folder '%s' manually.", 'wpbtd'),
							dirname( $dump_location ), basename( $dump_location ));
			$this->config->log( self::BACKUP_STATUS_WARNING, $msg );
			return false;
		}
		$filename =  $this->get_sql_file_name();
		$handle = fopen( $filename, 'w+' );
		if ( !$handle ) {
			throw new Exception( __( 'Error creating sql dump file.', 'wpbtd' ) . ' (ERROR_2)' );
		}

		$blog_time = strtotime( current_time( 'mysql' ) );

		$this->write_to_file( $handle, "-- WordPress Backup to Dropbox SQL Dump\n" );
		$this->write_to_file( $handle, "-- Version " . BACKUP_TO_DROPBOX_VERSION . "\n" );
		$this->write_to_file( $handle, "-- http://www.mikeyd.com.au/wordpress-backup-to-dropbox/\n" );
		$this->write_to_file( $handle, "-- Generation Time: " . date( "F j, Y", $blog_time ) . " at " . date( "H:i", $blog_time ) . "\n\n" );
		$this->write_to_file( $handle, 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";' . "\n\n" );

		//I got this out of the phpMyAdmin database dump to make sure charset is correct
		$this->write_to_file( $handle, "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n" );
		$this->write_to_file( $handle, "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n" );
		$this->write_to_file( $handle, "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n" );
		$this->write_to_file( $handle, "/*!40101 SET NAMES utf8 */;\n\n" );

		$this->write_to_file( $handle, "--\n-- Create and use the backed up database\n--\n\n" );
		$this->write_to_file( $handle, "CREATE DATABASE " . DB_NAME . ";\n" );
		$this->write_to_file( $handle, "USE " . DB_NAME . ";\n\n" );

		foreach ( $tables as $t ) {
			$table = $t[0];

			$this->write_to_file( $handle, "--\n-- Table structure for table `$table`\n--\n\n" );

			$table_create = $this->database->get_row( "SHOW CREATE TABLE $table", ARRAY_N );
			if ( $table_create === false ) {
				throw new Exception( $db_error . ' (ERROR_3)' );
			}
			$this->write_to_file( $handle, $table_create[1] . ";\n\n" );

			$table_data = $this->database->get_results( "SELECT * FROM $table", ARRAY_A );
			if ( $table_data === false ) {
				throw new Exception( $db_error . ' (ERROR_4)' );
			}

			if ( empty( $table_data ) ) {
				$this->write_to_file( $handle, "--\n-- Table `$table` is empty\n--\n\n" );
				continue;
			}

			$this->write_to_file( $handle, "--\n-- Dumping data for table `$table`\n--\n\n" );

			$fields = '`' . implode( '`, `', array_keys( $table_data[0] ) ) . '`';
			$this->write_to_file( $handle, "INSERT INTO `$table` ($fields) VALUES \n" );

			$out = '';
			foreach ( $table_data as $data ) {
				$data_out = '(';
				foreach ( $data as $value ) {
					$value = addslashes( $value );
					$value = str_replace( "\n", "\\n", $value );
					$value = str_replace( "\r", "\\r", $value );
					$data_out .= "'$value', ";
				}
				$out .= rtrim( $data_out, ' ,' ) . "),\n";
			}
			$this->write_to_file( $handle, rtrim( $out, ",\n" ) . ";\n" );
		}

		if ( !fclose( $handle ) ) {
			throw new Exception( __( 'Error closing sql dump file.', 'wpbtd' ) . ' (ERROR_5)' );
		}

		return true;
	}

	/**
	 * Write the contents of out to the handle provided. Raise an exception if this fails
	 * @throws Exception
	 * @param  $handle
	 * @param  $out
	 * @return void
	 */
	private function write_to_file( $handle, $out ) {
		if ( !fwrite( $handle, $out ) ) {
			throw new Exception( __( 'Error writing to sql dump file.', 'wpbtd' ) . ' (ERROR_6)' );
		}
	}

	/**
	 * Schedules a backup to start now
	 * @return void
	 */
	public function backup_now() {
		wp_schedule_single_event( time(), 'execute_instant_drobox_backup' );
	}

	/**
	 * Execute the backup
	 * @return bool
	 */
	public function execute() {
		$this->config->set_in_progress( true );
		try {

			$this->config->set_time_limit();

			$this->config->log( WP_Backup::BACKUP_STATUS_STARTED );

			if ( !$this->dropbox->is_authorized() ) {
				$this->config->log( WP_Backup::BACKUP_STATUS_FAILED, __( 'Your Dropbox account is not authorized yet.', 'wpbtd' ) );
				return;
			}

			$options = $this->config->get_options();
			$dump_location = $options['dump_location'];
			$dropbox_location = $options['dropbox_location'];

			$sql_file_name = $this->get_sql_file_name();
			$uploaded_files = $this->config->get_uploaded_files();
			if ( !in_array($sql_file_name, $uploaded_files) ) {
				$this->config->set_current_action( __( 'Creating SQL backup', 'wpbtd' ) );
				$this->backup_database();
			}

			$this->backup_path( ABSPATH, $dropbox_location );
			if ( defined( 'WP_CONTENT_DIR' ) && !strstr( WP_CONTENT_DIR, ABSPATH ) ) {
				$this->backup_path( WP_CONTENT_DIR, $dropbox_location . '/wp-content' );
			}

			$this->config->log( WP_Backup::BACKUP_STATUS_FINISHED );
			$this->config->set_current_action( __( 'Backup complete.', 'wpbtd' ) );
			$this->config->clean_up();

			unlink( $sql_file_name );

		} catch ( Exception $e ) {
			if ($e->getMessage() == 'Unauthorized')
				$this->config->log( self::BACKUP_STATUS_FAILED, __( 'The plugin is no longer authorized with Dropbox.', 'wpbtd' ) );
			else
				$this->config->log( WP_Backup::BACKUP_STATUS_FAILED, "Exception - " . $e->getMessage() );
		}
		$this->config->set_last_backup_time( time() );
		$this->config->set_in_progress( false );
	}

	/**
	 * Stops the backup
	 */
	public function stop() {
		$this->config->log( WP_Backup::BACKUP_STATUS_WARNING, __( 'Backup stopped by user.', 'wpbtd' ) );
		$this->config->set_in_progress( false );
		$this->config->set_last_backup_time( time() );
		$this->config->clean_up();
	}

	/**
	 * Creates the dump directory if it does not already exist
	 * @throws Exception
	 * @return string
	 */
	public function create_dump_dir() {
		$options = $this->config->get_options();
		$dump_dir = ABSPATH . $options['dump_location'];
		if ( !file_exists( $dump_dir ) ) {
			//It really pains me to use the error suppressor here but PHP error handling sucks :-(
			if ( !@mkdir( $dump_dir ) ) {
				throw new Exception(
				sprintf(
						__( "A database backup cannot be created because WordPress does not have write access to '%s', please create the folder '%s' manually.", 'wpbtd'),
						dirname( $dump_dir ), basename( $dump_dir )
					)
				);
			}
		}
		return $dump_dir;
	}

	private function get_sql_file_name() {
		$options = $this->config->get_options();
		return ABSPATH . rtrim( $options['dump_location'], DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . DB_NAME . '-backup.sql';
	}
}
