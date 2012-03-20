<?php
/**
 * A mock dropbox facade class
 *
 * @copyright Copyright (C) 2011-2012 Michael De Wildt. All rights reserved.
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
class Mock_Dropbox_Facade {

	/**
	 * @var bool
	 */
	public $is_authorized = true;

	/**
	 * @var bool
	 */
	public $throw_unauthorized = false;

    /**
     * @var array
     */
    private $files_processed = array();

    /**
     * @var int
     */
    private $peak_memory_usage = 0;

    /**
	 * @var
	 */
	private $check_real_path;

	/**
	 * @param bool $check_real_path
	 */
	public function __construct( $check_real_path = true ) {
		$this->check_real_path = $check_real_path;
	}

    /**
     * @return bool
     */
    public function is_authorized() {
        return $this->is_authorized;
    }

    /**
     * @return array
     */
    public function get_files_processes() {
        return $this->files_processed;
    }

    /**
     * @return void
     */
    public function reset_files_processed() {
        $this->files_processed = array();
    }

    /**
     * @return string
     */
    public function get_memory_usage() {
        return $this->peak_memory_usage . "M";
    }

    /**
     * @param  $dir
     * @param  $file
     * @return void
     */
    public function upload_file( $dir, $file ) {
		if ( $this->throw_unauthorized ) {
			throw new Exception( 'Unauthorized' );
		}


        //Exclude any hidden os files
        $arr = explode( '/', $file );
        $f = $arr[count( $arr ) - 1];
        if ( $f != '.htaccess' && substr( $f, 0, 1 ) == '.' ) {
            return;
        }
        if ( !file_exists( $file ) ) {
            throw new Exception( __( 'backup file does not exist.' ) );
        }

        $fh = fopen( $file, 'r' );

        /* random string */
        $boundary = 'R50hrfBj5JYyfR3vF3wR96GPCC9Fd2q2pVMERvEaOE3D8LZTgLLbRpNwXek3';

        $headers = array(
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
        );

        $body = "--" . $boundary . "\r\n";
        $body .= "Content-Disposition: form-data; name=file; filename=$file\r\n";
        $body .= "Content-type: application/octet-stream\r\n";
        $body .= "\r\n";
        $body .= stream_get_contents( $fh );
        $body .= "\r\n";
        $body .= "--" . $boundary . "--";

        // Dropbox requires the filename to also be part of the regular arguments, so it becomes
        // part of the signature.
        $uri = '?file=' . $file;

        $this->files_processed[] = $file;

        $memory_usage = memory_get_peak_usage( true ) / 1048576;
        if ( $memory_usage > $this->peak_memory_usage ) {
            $this->peak_memory_usage = $memory_usage;
        }

        fclose( $fh );
        sleep( 1 );
    }

    /**
     * @param  $dir
     * @param  $file
     * @return bool
     */
    public function file_exists( $dir, $file ) {
        return true;
    }

    /**
     * Return the users Dropbox info
     * @return array
     */
    public function get_account_info() {
        $account_info['quota_info']['normal'] = 1073741824 * 40; //40GB
        $account_info['quota_info']['quota'] = 1073741824 * 50; //50GB
        $account_info['display_name'] = 'Michael De Wildt';
        return $account_info;
    }

	/**
	 * @param  $path
	 * @return array
	 */
    function get_directory_contents( $path ) {
		if ( !$this->check_real_path ) {
			return array();
		}
        $directory_contents = array();

        $files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( str_replace( 'Dropbox', './', $path ) ), RecursiveIteratorIterator::SELF_FIRST );
        $directory_contents[$path] = array();
        foreach ( $files as $file ) {
            $directory_contents[] = basename( $file );
        }

        return $directory_contents;
    }
}
