<?php
/**
 * @copyright Copyright (C) 2011-2013 Michael De Wild. All rights reserved.
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

	private $objectCache = array();

	public static function construct() {
		return new self();
	}

	public function __construct() {
		foreach (glob(EXTENSIONS_DIR . 'class-*.php') as $extension) {
			include_once $extension;

			$this->get_instance(
				str_replace('-', '_',
				preg_replace('/.php$/', '',
				preg_replace('/^class-/', '',
			basename($extension)))));
		}
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

	public function is_installed($name) {
		return $this->get_instance($name);
	}

	public function install($name) {
		if (!defined('FS_METHOD'))
			define('FS_METHOD', 'direct');

		WP_Filesystem();

		$params = array(
			'name' => $name,
			'site' => get_site_url(),
			'version' => BACKUP_TO_DROPBOX_VERSION,
		);

		$download_file = download_url("{$this->get_url()}/download?" . http_build_query($params));

		$writeableMsg = __("this might be because 'wp-content/plugins/wordpress-backup-to-dropbox/Extensions' is not writeable.");

		if (is_wp_error($download_file)) {
			$errorMsg = $download_file->get_error_messages();
			throw new Exception(__('There was an error downloading your premium extension') . ", $writeableMsg ({$errorMsg[0]})");
		}

		$result = unzip_file($download_file, EXTENSIONS_DIR);
		if (is_wp_error($result)) {
			$errorMsg = $result->get_error_messages();
			if ($errorMsg[0] == "Incompatible Archive.") {
				$errorMsg[0] = file_get_contents($download_file);
			}

			unlink($download_file);
			throw new Exception(__('There was an error installing your premium extension') . ", $writeableMsg ({$errorMsg[0]})");
		}

		unlink($download_file);

		include EXTENSIONS_DIR . 'class-' . str_replace(' ', '-', strtolower($name)) . '.php';

		return $this->get_instance($name);
	}

	public function get_output() {
		foreach ($this->objectCache as $obj) {
			if ($obj && $obj->get_type() == WP_Backup_Extension::TYPE_OUTPUT && $obj->is_enabled()) {
				return $obj;
			}
		}
		return $this->get_instance('WP_Backup_Output');
	}

	public function add_menu_items() {
		foreach ($this->objectCache as $obj) {
			$title = $obj->get_menu();
			$slug = $this->get_menu_slug($obj);
			$func = $this->get_menu_func($obj);

			add_submenu_page('backup-to-dropbox', $title, $title, 'activate_plugins', $slug, $func);
		}
	}

	public function complete() {
		$this->call('complete');
	}

	public function failure() {
		$this->call('failure');
	}

	public function get_menu_slug($obj) {
		return 'backup-to-dropbox-' . str_replace('_', '-', strtolower(get_class($obj)));
	}

	public function get_menu_func($obj) {
		return 'backup_to_dropbox_' . strtolower(get_class($obj));
	}

	private function call($func) {
		foreach ($this->objectCache as $obj) {
			if ($obj && $obj->is_enabled()) {
				$obj->$func();
			}
		}
	}

	private function get_instance($name) {
		$class = str_replace(' ', '_', ucwords($name));

		if (!isset($this->objectCache[$class])) {
			if (!class_exists($class)) {
				return false;
			}

			$this->objectCache[$class] = new $class();
		}

		return $this->objectCache[$class];
	}
}