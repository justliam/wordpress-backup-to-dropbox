<?php
/**
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
class WP_Backup_Extension_Manager {
	private
		$key = 'c7d97d59e0af29b2b2aa3ca17c695f96',
		$objectCache,
		$installed,
		$db
		;

	public static function construct() {
		return new self();
	}

	public function __construct() {
		$this->db = WP_Backup_Registry::db();
	}

	public function get_key() {
		return $this->key;
	}

	public function get_url() {
		if (defined('WPB2D_URL'))
			return WPB2D_URL;

		return 'http://wpb2d.com';
	}

	public function get_install_url() {
		return 'admin.php?page=backup-to-dropbox-premium';
	}

	public function get_buy_url() {
		return $this->get_url() . '/buy';
	}

	public function get_extensions() {
		$params = array(
			'key' => $this->key,
			'site' => get_site_url(),
		);

		$response = wp_remote_get("{$this->get_url()}/extensions?" . http_build_query($params));
		if (is_wp_error($response))
			throw new Exception(__('There was an error getting the list of premium extensions'));

		return json_decode($response['body'], true);
	}

	public function get_installed() {
		if (!$this->installed) {
			$installed = $this->db->get_results("SELECT * FROM {$this->db->prefix}wpb2d_premium_extensions");

			$this->installed = array();

			if (is_array($installed)) {
				foreach ($installed as $extension) {
					if (file_exists(EXTENSIONS_DIR . $extension->file))
						$this->installed[] = $extension;
				}
			}
		}

		return $this->installed;
	}

	public function is_installed($name) {
		foreach ($this->get_installed() as $ext) {
			if (strtolower($ext->name) == strtolower($name))
				return true;
		}
	}

	public function install($name, $file) {
		if (!defined('FS_METHOD'))
			define('FS_METHOD', 'direct');

		WP_Filesystem();

		$params = array(
			'key' => $this->key,
			'name' => $name,
			'site' => get_site_url(),
			'version' => BACKUP_TO_DROPBOX_VERSION,
		);

		$download_file = download_url("{$this->get_url()}/download?" . http_build_query($params));

		if (is_wp_error($download_file)) {
			$errorMsg = $download_file->get_error_messages();
			throw new Exception(__('There was an error downloading your premium extension') . ' - ' . $errorMsg[0]);
		}

		$result = unzip_file($download_file, EXTENSIONS_DIR);
		if (is_wp_error($result)) {
			$errorMsg = $result->get_error_messages();
			if ($errorMsg[0] == "Incompatible Archive.")
				$errorMsg[0] = file_get_contents($download_file);

			unlink($download_file);
			throw new Exception(__('There was an error installing your premium extension') . ' - ' . $errorMsg[0]);
		}

		unlink($download_file);

		$this->activate($name, $file);
	}

	public function activate($name, $file) {
		$exists = $this->db->get_var(
			$this->db->prepare("SELECT * FROM {$this->db->prefix}wpb2d_premium_extensions WHERE name = %s", $name)
		);

		if (is_null($exists)) {
			$this->db->insert("{$this->db->prefix}wpb2d_premium_extensions", array(
				'name' => $name,
				'file' => $file,
			));
		}
	}

	public function init() {
		$installed = $this->get_installed();
		$active = array();
		foreach ($installed as $extension) {
			if (file_exists(EXTENSIONS_DIR . $extension->file)) {

				include_once EXTENSIONS_DIR . $extension->file;
				$this->activate($extension->name, $extension->file);
			}

		}
	}

	public function get_output() {
		$installed = $this->get_installed();
		foreach ($installed as $extension) {
			$obj = $this->get_instance($extension->name);
			if ($obj && $obj->get_type() == WP_Backup_Extension::TYPE_OUTPUT && $obj->is_enabled())
				return $obj;
		}
		return $this->get_instance('WP_Backup_Output');
	}

	public function add_menu_items() {
		return $this->call('get_menu', false);
	}

	public function complete() {
		$this->call('complete');
	}

	public function failure() {
		$this->call('failure');
	}

	private function call($func, $check_enabled = true) {
		$installed = $this->get_installed();
		foreach ($installed as $extension) {
			$obj = $this->get_instance($extension->name);
			if ($obj && ($check_enabled == false || $obj->is_enabled()))
				$obj->$func();
		}
	}

	private function get_instance($name) {
		$class = str_replace(' ', '_', ucwords($name));

		if (!isset($this->objectCache[$class])) {
			if (!class_exists($class))
				return false;

			$this->objectCache[$class] = new $class();
		}

		return $this->objectCache[$class];
	}
}