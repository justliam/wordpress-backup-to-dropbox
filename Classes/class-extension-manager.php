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
class Extension_Manager {

	private $url = 'http://wpb2d.com';
	private $key = 'c7d97d59e0af29b2b2aa3ca17c695f96';

	public function __construct() {
		if ( !get_option( 'backup-to-dropbox-premium-extensions' ) )
			add_option( 'backup-to-dropbox-premium-extensions', array(), null, 'no' );
	}

	public function get_key() {
		return $this->key;
	}

	public function get_url() {
		return $this->url;
	}

	public function get_install_url() {
		return 'admin.php?page=backup-to-dropbox-premium';
	}

	public function get_buy_url() {
		return $this->url . '/buy';
	}

	public function get_extensions() {
		$response = wp_remote_get( "{$this->url}/extensions?key={$this->key}&site=" . get_site_url() );
		if ( is_wp_error( $response )  )
			throw new Exception( __( 'There was an error getting the list of premium extensions' ) );

		return json_decode( $response['body'], true );
	}

	public function get_installed() {
		$extensions = get_option( 'backup-to-dropbox-premium-extensions' );
		if ( !is_array( $extensions) )
			return array();

		return $extensions;
	}

	public function install( $extensionId ) {
		WP_Filesystem();
		$download_file = download_url( "{$this->url}/download?key={$this->key}&extensionId=$extensionId&site=" . get_site_url() );
		$result = unzip_file( $download_file, WP_CONTENT_DIR . '/plugins/wordpress-backup-to-dropbox/PremiumExtensions/' );
		unlink( $download_file );
		if ( is_wp_error( $result ) ) {
			$errorMsg = $result->get_error_messages();
			throw new Exception( __( 'There was an error installing your premium extension' ) . ' - ' . $errorMsg[0] );
		}
		$this->add_extension( $extensionId );
	}

	private function add_extension( $extensionId ) {
		$extensions = get_option( 'backup-to-dropbox-premium-extensions' );
		$extensions[] = $extensionId;
		update_option( 'backup-to-dropbox-premium-extensions', $extensions );
	}
}