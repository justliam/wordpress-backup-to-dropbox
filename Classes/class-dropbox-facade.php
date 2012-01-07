<?php
/**
 * A facade class with wrapping functions to administer a dropbox account
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

class Dropbox_Facade {

	const RETRY_COUNT = 3;
	const CONSUMER_KEY = '0kopgx3zvfd0876';
	const CONSUMER_SECRET = 'grpp5f0dai90bon';

	/**
	 * @var Dropbox_API
	 */
	private $dropbox = null;

	/**
	 * The users Dropbox tokens
	 * @var object
	 */
	private $tokens = null;

	/**
	 * Creates a new instance of the Dropbox facade by connecting to Dropbox with the application tokens and then create
	 * a new instance of the Dropbox api for use by ths facade.
	 *
	 * @return \Dropbox_Facade
	 */
	function __construct() {
		$this->tokens = get_option( 'backup-to-dropbox-tokens' );
		if ( !$this->tokens ) {
			$this->tokens = array( 'access' => false, 'request' => false );
			add_option( 'backup-to-dropbox-tokens', $this->tokens, null, 'no' );
		} else {
			//Get the users drop box credentials
			$oauth = new Dropbox_OAuth_PEAR( self::CONSUMER_KEY, self::CONSUMER_SECRET );

			//If we have not got an access token then we need to grab one
			if ( $this->tokens['access'] == false ) {
				try {
					$oauth->setToken( $this->tokens['request'] );
					$this->tokens['access'] = $oauth->getAccessToken();
					$this->save_tokens();
				} catch ( Exception $e ) {
					//Authorization failed so we are still pending
					$this->tokens['access'] = false;
				}
			} else {
				$oauth->setToken( $this->tokens['access'] );
			}
			$this->dropbox = new Dropbox_API( $oauth );
		}
	}

	/**
	 * If we have successfully retrieved the users email and password from the wp options table then this uer is authorized
	 * @return bool
	 */
	public function is_authorized() {
		if ( !$this->tokens['access'] ) {
			return false;
		}
		$info = $this->get_account_info();
		if ( isset( $info['error'] ) ) {
			$this->tokens = null;
			$this->save_tokens();
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Returns the Dropbox authorization url
	 * @return string
	 */
	public function get_authorize_url() {
		$oauth = new Dropbox_OAuth_PEAR( self::CONSUMER_KEY, self::CONSUMER_SECRET );
		$this->tokens['request'] = $oauth->getRequestToken();
		$this->save_tokens();
		return $oauth->getAuthorizeUrl();
	}

	/**
	 * Return the users Dropbox info
	 * @return array
	 */
	public function get_account_info() {
		return $this->dropbox->getAccountInfo();
	}

	private function save_tokens() {
		update_option( 'backup-to-dropbox-tokens', $this->tokens );
	}

	/**
	 * Uploads a file to Dropbox
	 * @param  $file
	 * @return bool
	 */
	function upload_file( $path, $file ) {
		if ( !file_exists( $file ) ) {
			throw new Exception( __( 'backup file does not exist.', 'wpbtd' ) );
		}
		$retries = 0;
		$ret = false;
		$e = null;
		while ( $retries < self::RETRY_COUNT ) {
			try {
				$ret = $this->dropbox->putFile( $path, $file );
				break;
			} catch ( Exception $e ) {
				$retries++;
				sleep( 1 );
			}
		}
		if ( !$ret ) {
			throw $e;
		} else if ( $ret[ 'httpStatus' ] == 401 ) {
			throw new Exception( 'Unauthorized' );
		} else if ( $ret[ 'httpStatus' ] != 200 ) {
			throw new Exception( sprintf( __( 'Error while uploading %s to Dropbox. HTTP Status: %s', 'wpbtd' ),
										  $file,
										  $ret[ 'httpStatus' ] ) );
		}
		return true;
	}

	/**
	 * Grabs the contents of a directory from Dropbox. The contents are cached to limit the amount of requests in one
	 * execution.
	 *
	 * @param  $path - The location of the file on this server
	 * @return array
	 */
	function get_directory_contents( $path ) {
		static $directory_cache = array();
		if ( !isset( $directory_cache[$path] ) ) {
			$directory_cache[$path] = array();
			try {
				$meta_data = $this->dropbox->getMetaData( $path );
				foreach ( $meta_data['contents'] as $val ) {
					if ( !$val['is_dir'] ) {
						$directory_cache[$path][] = basename( $val['path'] );
					}
				}
				//No need to do anything with the exception because the dir does not exist
			} catch ( Dropbox_Exception_NotFound $e ) {}
		}
		return $directory_cache[$path];
	}

	/**
	 * @return void
	 */
	public function unlink_account() {
		delete_option( 'backup-to-dropbox-tokens' );
		$this->tokens['access'] = false;
	}
}
