<?php
/**
 * A facade class with wrapping functions to administer a dropbox account
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

class Dropbox_Facade {

	const RETRY_COUNT = 3;
	const CONSUMER_KEY = '0kopgx3zvfd0876';
	const CONSUMER_SECRET = 'grpp5f0dai90bon';

	private $dropbox = null;
	private $tokens = null;

	public static function construct() {
		return new self();
	}

	public function __construct() {
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

	public function get_authorize_url() {
		$oauth = new Dropbox_OAuth_PEAR( self::CONSUMER_KEY, self::CONSUMER_SECRET );
		$this->tokens['request'] = $oauth->getRequestToken();
		$this->save_tokens();
		return $oauth->getAuthorizeUrl();
	}

	public function get_account_info() {
		return $this->dropbox->getAccountInfo();
	}

	private function save_tokens() {
		update_option( 'backup-to-dropbox-tokens', $this->tokens );
	}

	public function upload_file( $path, $file ) {
		if ( !file_exists( $file ) ) {
			throw new Exception( sprintf(__( 'backup file (%s) does not exist.', 'wpbtd' ), $file)  );
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
			throw new Exception( sprintf( __( 'HTTP Status %s received from Dropbox while uploading file.', 'wpbtd' ),
										  $file,
										  $ret[ 'httpStatus' ] ) );
		}
		return true;
	}

	public function delete_file($file) {
		if ( !file_exists( $file ) ) {
			throw new Exception( sprintf(__( 'backup file (%s) does not exist.', 'wpbtd' ), $file)  );
		}
		$ret = $this->dropbox->delete($file);
		if ( $ret[ 'httpStatus' ] == 401 ) {
			throw new Exception( 'Unauthorized' );
		} else if ( $ret[ 'httpStatus' ] != 200 ) {
			throw new Exception( sprintf( __( 'HTTP Status %s received from Dropbox while deleting file.', 'wpbtd' ),
										  $file,
										  $ret[ 'httpStatus' ] ) );
		}
	}

	public function get_directory_contents( $path ) {
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

	public function unlink_account() {
		delete_option( 'backup-to-dropbox-tokens' );
		$this->tokens['access'] = false;
	}
}
