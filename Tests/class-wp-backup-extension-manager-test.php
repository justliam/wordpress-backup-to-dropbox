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

	public function setUp() {
		reset_globals();
		WP_Backup::create_dump_dir();
	}



	public function tearDown() {
		@unlink(EXTENSIONS_DIR . 'extension.php');
		unlink(WP_Backup_Config::get_backup_dir() . '/index.php');
		rmdir(WP_Backup_Config::get_backup_dir());
		Mockery::close();
	}

	public function testConstruct() {
		$db = Mockery::mock()
			->shouldReceive('get_results')
			->with("SELECT * FROM wp_wpb2d_premium_extensions", 1)
			->once()

			->mock()
			;

		$db->prefix = 'wp_';

		$mgr = new WP_Backup_Extension_Manager($db);

		$this->assertEquals(array(), $mgr->get_installed());
	}

	public function testGetExtensions() {
		$mgr = new WP_Backup_Extension_Manager(Mockery::mock());

		$extensions = $mgr->get_extensions();
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
		$db = Mockery::mock()
			->shouldReceive('insert')
			->with('wp_wpb2d_premium_extensions', array(
				'name' => 'Test extension' ,
				'file' => 'extension-file.php'
			))
			->andReturn(true)
			->once()

			->shouldReceive('get_results')
			->with("SELECT * FROM wp_wpb2d_premium_extensions", 1)
			->andReturn(array(
				'Test extension' => 'extension-file.php'
			))
			->once()

			->mock()
			;

		$db->prefix = 'wp_';

		$mgr = new WP_Backup_Extension_Manager($db);
		$mgr->install('Test extension', 'extension-file.php');
		$this->assertEquals(array(
			'Test extension' => 'extension-file.php'
		), $mgr->get_installed());
	}

	public function testInitAndCallbacks() {
		$db = Mockery::mock()
			->shouldReceive('insert')
			->with('wp_wpb2d_premium_extensions', array(
				'name' => 'Test extension' ,
				'file' => 'extension.php'
			))
			->andReturn(true)
			->twice()

			->shouldReceive('get_results')
			->with("SELECT * FROM wp_wpb2d_premium_extensions", 1)
			->andReturn(array(
				'Test extension' => 'extension.php'
			))
			->once()

			->mock()
			;

		$db->prefix = 'wp_';

		$mgr = new WP_Backup_Extension_Manager($db);

		$this->assertFalse(function_exists('new_func'));

		$mgr->install('Test extension', 'extension.php');
		$mgr->init();

		$this->assertTrue(class_exists('Test_Extension'));
		$this->assertTrue(Test_Extension::on_complete());
		$this->assertTrue(Test_Extension::on_failure());
		$this->assertTrue(Test_Extension::get_menu());
		$this->assertEquals(2, Test_Extension::get_type());
		$this->assertTrue(Test_Extension::is_enabled());
		$this->assertTrue(Test_Extension::set_enabled(true));
		$this->assertInstanceOf('Test_Extension', $mgr->get_output());

		$mgr->add_menu_items();
		$this->assertEquals('get_menu', Test_Extension::$lastCalled);

		$mgr->on_start();
		$this->assertEquals('on_start', Test_Extension::$lastCalled);

		$mgr->on_complete();
		$this->assertEquals('on_complete', Test_Extension::$lastCalled);

		$mgr->on_failure();
		$this->assertEquals('on_failure', Test_Extension::$lastCalled);
	}
}