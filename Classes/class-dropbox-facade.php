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

	const CONSUMER_KEY = 'u1i8xniul59ggxs';
	const CONSUMER_SECRET = '0ssom5yd1ybebhy';
	const RETRY_COUNT = 3;

	private static $instance = null;

	private $dropbox = null;
	private $tokens = null;
	private $oauth = null;
	private $account_info_cache = null;
	private $directory_cache = array();

	public static function construct() {
		if (!self::$instance)
			self::$instance = new self();

		return self::$instance;
	}

	public function __construct() {

		if (!extension_loaded('curl')) {
			throw new Exception(sprintf(
				__('The cURL extension is not loaded. %sPlease ensure its installed and activated.%s', 'wpbtd'),
				'<a href="http://php.net/manual/en/curl.installation.php">',
				'</a>'
			));
		}

        $this->oauth = new OAuth_Consumer_Curl(self::CONSUMER_KEY, self::CONSUMER_SECRET);
		$this->tokens = get_option('backup-to-dropbox-tokens');

		//Convert array to stdClass for the new API
		if ($this->tokens && is_array($this->tokens['access'])) {
			$accessToken = new stdClass;
			$accessToken->oauth_token = $this->tokens['access']["token"];
			$accessToken->oauth_token_secret = $this->tokens['access']["token_secret"];
			$this->tokens['access'] = $accessToken;
			$this->tokens['state'] = 'access';
		}

		try {
			$this->init();

			//If we are in the access state and are still not authorized then unlink and re init
			if ($this->tokens['state'] == 'access' && !$this->is_authorized())
				throw new Exception;

		} catch (Exception $e) {
			$this->unlink_account();
			$this->init();

		}
	}

	private function init() {

		//If we have not tokens then lets setup a new request
		if (!$this->tokens) {
			$this->tokens = array(
				'access' => false,
				'request' => $this->oauth->getRequestToken(),
				'state' => 'request',
			);
			add_option('backup-to-dropbox-tokens', $this->tokens, null, 'no');
			$this->oauth->setToken($this->tokens['request']);
		}

		//If there is no state then assume we have access
		if (!isset($this->tokens['state']))
			$this->tokens['state'] = 'access';

		//Consume the set tokens
		if ($this->tokens['state'] == 'access') {
			$this->oauth->setToken($this->tokens['access']);
		} else if ($this->tokens['state'] == 'request') {
			$this->oauth->setToken($this->tokens['request']);
			//If we have not got an access token then we need to grab one
			try {
				$this->tokens['access'] = $this->oauth->getAccessToken();
				$this->tokens['state'] = 'access';
				$this->oauth->setToken($this->tokens['access']);
			} catch (Exception $e) {
				//Authorization failed so we are still pending
				$this->oauth->resetToken();
				$this->tokens['request'] = $this->oauth->getRequestToken();
				$this->tokens['state'] = 'request';

				$this->oauth->setToken($this->tokens['request']);
			}
			$this->save_tokens();
		}

		$this->dropbox = new API($this->oauth);
	}

	public function is_authorized() {
		try {
			$this->get_account_info();
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function get_authorize_url() {
		return $this->oauth->getAuthoriseUrl();
	}

	public function get_account_info() {
		if (!isset($this->account_info_cache)) {
			$response = $this->dropbox->accountInfo();
			$this->account_info_cache = $response['body'];
		}

		return $this->account_info_cache;
	}

	private function save_tokens() {
		update_option('backup-to-dropbox-tokens', $this->tokens);
	}

	public function upload_file($path, $file) {
		$i = 0;
		while ($i++ < self::RETRY_COUNT) {
			try {
				return $this->dropbox->putFile($file, null, $path);
			} catch (Exception $e) {}
		}
		throw $e;
	}

	public function chunk_upload_file($path, $file) {
		return $this->dropbox->chunkedUpload($file, null, $path);
	}

	public function delete_file($file) {
		return $this->dropbox->delete($file);
	}

	public function create_directory($path) {
		try {
			$this->dropbox->create($path);
		} catch (Exception $e) {}
	}

	public function get_directory_contents($path) {
		if (!isset($this->directory_cache[$path])) {
			try {
				$this->directory_cache[$path] = array();
				$response = $this->dropbox->metaData($path);

				foreach ($response['body']->contents as $val) {
					if (!$val->is_dir) {
						$this->directory_cache[$path][] = basename($val->path);
					}
				}
			} catch (Exception $e) {
				$this->create_directory($path);
			}
		}
		return $this->directory_cache[$path];
	}

	public function unlink_account() {
		$this->tokens = false;

		$this->oauth->resetToken();
		delete_option('backup-to-dropbox-tokens');

		$this->init();
	}
}
