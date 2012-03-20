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
class WP_Output {

	private $config;

	public function __construct() {
		$this->config = WP_Backup_Config::construct();
	}

	private function get_max_file_size() {
		$memory_limit_string = ini_get( 'memory_limit' );
		$memory_limit = ( preg_replace( '/\D/', '', $memory_limit_string ) * 1048576 );

		$suhosin_memory_limit_string = ini_get( 'suhosin.memory_limit' );
		$suhosin_memory_limit = ( preg_replace( '/\D/', '', $suhosin_memory_limit_string ) * 1048576 );

		if ( $suhosin_memory_limit && $suhosin_memory_limit < $memory_limit ) {
			$memory_limit = $suhosin_memory_limit;
		}
		return $memory_limit / 2.5;
	}

	public function out( $file ) {

		$options = $this->config->get_options();
		$last_backup_time = $options['last_backup_time'];
		$uploaded_files = $this->config->get_uploaded_files();

		if ( filesize( $file ) > $this->get_max_file_size() ) {
			$this->config->log( WP_Backup_Config::BACKUP_STATUS_WARNING,
						sprintf( __( "file '%s' exceeds 40 percent of your PHP memory limit. The limit must be increased to back up this file.", 'wpbtd' ), basename( $file ) ) );
			continue;
		}

		if ( in_array( $file, $uploaded_files ) )
			continue;

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
				$this->config->log( WP_Backup_Config::BACKUP_STATUS_WARNING, $msg );
			}
		}
	}
}