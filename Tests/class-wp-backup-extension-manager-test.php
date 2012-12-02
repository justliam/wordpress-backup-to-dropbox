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
require_once 'mock-wp-functions.php';

class WP_Backup_Extension_Manager_Test extends PHPUnit_Framework_TestCase {

	private $mgr;

	public function setUp() {
		reset_globals();
		$this->mgr = WP_Backup_Extension_Manager::construct();
		WP_Backup::create_dump_dir();
	}

	public function tearDown() {
		@unlink(EXTENSIONS_DIR . 'extension.php');
		unlink(WP_Backup_Config::get_backup_dir() . '/index.php');
		rmdir(WP_Backup_Config::get_backup_dir());
	}

	public function testConstruct() {
		$this->assertEquals(array(), $this->mgr->get_installed());
	}

	public function testGetExtensions() {
		$extensions = $this->mgr->get_extensions();
		$this->assertEquals(array(
			'extensionid' => 1,
			'name' => 'name',
			'description' => 'description',
			'file' => 'extension.php',
			'price' => 'price',
			'purchased' => true,
		), $extensions[0]);
	}

	public function testInstallGetInstalled() {
		$this->mgr->install('Test extension', 'extension-file.php');
		$this->assertEquals(array(
			'Test extension' => 'extension-file.php'
		), $this->mgr->get_installed());
	}

	public function testInit() {
		$this->assertFalse(function_exists('new_func'));

		$this->mgr->install('Test extension', 'extension.php');
		$this->mgr->init();

		$this->assertTrue(class_exists('Test_Extension'));
		$this->assertTrue(Test_Extension::on_complete());
		$this->assertTrue(Test_Extension::on_failure());
		$this->assertTrue(Test_Extension::get_menu());
		$this->assertEquals(2, Test_Extension::get_type());
		$this->assertTrue(Test_Extension::is_enabled());
		$this->assertTrue(Test_Extension::set_enabled(true));
	}

	public function testGetOutput() {
		$this->assertInstanceOf('WP_Backup_Output', $this->mgr->get_output());
		$this->mgr->install('Test extension', 'extension.php');
		$this->mgr->init();
		$this->assertInstanceOf('Test_Extension', $this->mgr->get_output());
	}

	public function testAddMenuItems() {
		$this->mgr->install('Test extension', 'extension.php');
		$this->mgr->init();
		$this->mgr->add_menu_items();
		$this->assertEquals('get_menu', Test_Extension::$lastCalled);
	}

	public function testOnStart() {
		$this->mgr->install('Test extension', 'extension.php');
		$this->mgr->init();
		$this->mgr->on_start();
		$this->assertEquals('on_start', Test_Extension::$lastCalled);
	}

	public function testOnComplete() {
		$this->mgr->install('Test extension', 'extension.php');
		$this->mgr->init();
		$this->mgr->on_complete();
		$this->assertEquals('on_complete', Test_Extension::$lastCalled);
	}

	public function testOnFailure() {
		$this->mgr->install('Test extension', 'extension.php');
		$this->mgr->init();
		$this->mgr->on_failure();
		$this->assertEquals('on_failure', Test_Extension::$lastCalled);
	}
}