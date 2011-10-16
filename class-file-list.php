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
include_once( 'class-wp-backup.php' );
class File_List {

	const EXCLUDED = 0;
	const INCLUDED = 1;
	const PARTIAL = 2;

    /**
     * A list of directories to mark as partial
     * @var null
     */
    private $partial_directories = array();

	/**
     * A list of files that are not allowed to be backed up
     * @var null
     */
    private $excluded_files = array();

    /**
     * These files cannot be uploaded to Dropbox
     * @var array
     */
    private static $ignored_files = array( '.DS_Store', 'Thumbs.db', 'desktop.ini' );

    /**
     * Construct the file list
     * @param $wpdb
     */
    public function __construct( $wpdb ) {
        $this->database = $wpdb;

        $file_list = get_option( 'backup-to-dropbox-file-list' );
        if ( $file_list === false ) {
	        $this->partial_directories = array();
	        $this->excluded_files = array();
            add_option( 'backup-to-dropbox-file-list', array( $this->partial_directories, $this->excluded_files ), null, 'no' );
        } else {
	        list( $this->partial_directories, $this->excluded_files ) = $file_list;
        }
    }

    /**
	 * Return the state of a file in the list the SQL dump is always included
     * @param  $path
     * @return bool
     */
    public function get_file_state( $path ) {
        $parent_path = dirname( $path ) . '/';
	    if ( $path == dirname( ABSPATH ) . '/' ) {
            return self::PARTIAL;
        } else if ( strstr( $path, DB_NAME . '-backup.sql' ) ) {
            return self::INCLUDED;
	    } else if ( in_array( $path, $this->excluded_files ) ) {
			return self::EXCLUDED;
	    } else if ( in_array( $path, $this->partial_directories ) ) {
	        $parent_state = $this->get_file_state( $parent_path );
	        if ( $parent_state == self::INCLUDED ) {
		        $this->remove_from_partial( $path );
		        $this->remove_from_excluded( $path );
		        return self::INCLUDED;
	        }
	        return self::PARTIAL;
        }
	    
		$state = $this->get_file_state( $parent_path );
	    if ( $state == self::PARTIAL ) {
		    $this->remove_from_partial( $path );
		    $this->remove_from_excluded( $path );
		    return self::INCLUDED;
	    }
	    return $state;
    }

	/**
	 * @param $full_path string
	 * @return string
	 */
	function get_check_box_class( $full_path ) {
		$state = $this->get_file_state( $full_path );
		switch ( $state ) {
			case WP_Backup::EXCLUDED:
				$class = 'checked';
				break;
			case WP_Backup::PARTIAL:
				$class = 'partial';
				break;
			default: //INCLUDED so do not check
				$class = '';
				break;
		}
		return $class;
	}

	/**
	 * Adds a file to the excluded list if it does not already exist
	 * @param $file
	 * @return void
	 */
	private function add_to_excluded( $file ) {
		if ( !in_array( $file, $this->excluded_files ) ) {
			$this->excluded_files[] = $file;
		}
	}

	/**
	 * Adds a file to the partial list if it does not already exist
	 * @param $file
	 * @return void
	 */
	private function add_to_partial( $file ) {
		if ( !in_array( $file, $this->partial_directories ) ) {
			$this->partial_directories[] = $file;
		}
	}

    /**
     * Accepts a JSON encoded list of files and directories and adds their states to the appropriate lists
     * @param  $json_list
     * @return void
     */
    public function set_file_list( $json_list ) {
        $new_list = json_decode( stripslashes( $json_list ), true );
	    foreach ( $new_list as $fl ) {
		    list ( $file, $state ) = $fl;
		    if ( $state == self::PARTIAL ) {
				$this->add_to_partial( $file );
			    $this->remove_from_excluded( $file );
			} else if ( $state == self::EXCLUDED) {
				$this->add_to_excluded( $file );
			    $this->remove_from_partial( $file );
			} else {
				$this->remove_from_excluded( $file );
			    $this->remove_from_partial( $file );
			}
	    }
    }

	/**
	 * Removes a file from the excluded list if it exists
	 * @param $file
	 * @return void
	 */
	private function remove_from_excluded( $file ) {
	    if ( in_array( $file, $this->excluded_files ) ) {
			$i = array_search( $file, $this->excluded_files );
			unset( $this->excluded_files[$i] );
		}
	}

	/**
	 * Removes a file from the partial list if it exists
	 * @param $file
	 * @return void
	 */
	private function remove_from_partial( $file ) {
		if ( in_array( $file, $this->partial_directories ) ) {
			$i = array_search( $file, $this->partial_directories );
			unset( $this->partial_directories[$i] );
		}
	}

	/**
	 * Saves the file list
	 * @return void
	 */
	public function save() {
        update_option( 'backup-to-dropbox-file-list', array( $this->partial_directories, $this->excluded_files ) );
    }

	/**
	 * @param $dir
	 * @return int
	 */
	private function get_directory_state( $dir ) {
		$files = scandir( $dir );
		natcasesort( $files );
		foreach ( $files as $file ) {
			$state = $this->get_file_state( $file );
			if ( $state == self::PARTIAL || $state == self::EXCLUDED ) {
				return self::PARTIAL;
			}
		}
		return self::INCLUDED;
	}

    /**
     * Some files cannot be uploaded to Dropbox so check them here
     * @static
     * @param $file
     * @return bool
     */
    public static function in_ignore_list( $file ) {
        return in_array( $file, self::$ignored_files );
    }
}
