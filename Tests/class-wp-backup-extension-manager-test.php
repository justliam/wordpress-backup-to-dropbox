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

		WP_Backup_Registry::setDropbox(Mockery::mock('Dropbox'));
	}

	public function tearDown() {
		Mockery::close();

		unlink(EXTENSIONS_DIR . 'extension.php');
	}

	public function testConstruct() {
		$db = Mockery::mock()
			->shouldReceive('get_results')
			->with("SELECT * FROM wp_wpb2d_premium_extensions")
			->once()

			->mock()
			;

		$db->prefix = 'wp_';

		WP_Backup_Registry::setDatabase($db);

		$mgr = new WP_Backup_Extension_Manager();

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
		$ext = new stdClass;
		$ext->name = 'Test extension';
		$ext->file = 'extension.php';

		$db = Mockery::mock()
			->shouldReceive('insert')
			->with('wp_wpb2d_premium_extensions', array(
				'name' => 'Test extension' ,
				'file' => 'extension.php'
			))
			->andReturn(true)
			->once()

			->shouldReceive('get_results')
			->with("SELECT * FROM wp_wpb2d_premium_extensions")
			->andReturn(array($ext))
			->once()

			->mock()
			;

		$db->prefix = 'wp_';

		WP_Backup_Registry::setDatabase($db);

		$mgr = new WP_Backup_Extension_Manager();
		$mgr->install('Test extension', 'extension.php');

		$extensions = $mgr->get_installed();


		$this->assertEquals($ext->name, $extensions[0]->name);
		$this->assertEquals($ext->file, $extensions[0]->file);
	}

	public function testInitAndCallbacks() {
		$ext = new stdClass;
		$ext->name = 'Test extension';
		$ext->file = 'extension.php';

		$db = Mockery::mock()
			->shouldReceive('insert')
			->with('wp_wpb2d_premium_extensions', array(
				'name' => 'Test extension' ,
				'file' => 'extension.php'
			))
			->andReturn(true)
			->twice()

			->shouldReceive('get_results')
			->with("SELECT * FROM wp_wpb2d_premium_extensions")
			->andReturn(array($ext))
			->once()

			->mock()
			;

		$db->prefix = 'wp_';

		WP_Backup_Registry::setDatabase($db);

		$mgr = new WP_Backup_Extension_Manager();

		$this->assertFalse(function_exists('new_func'));

		$mgr->install('Test extension', 'extension.php');
		$mgr->init();

		$this->assertTrue(class_exists('Test_Extension'));
		$this->assertTrue(Test_Extension::complete());
		$this->assertTrue(Test_Extension::failure());
		$this->assertTrue(Test_Extension::get_menu());
		$this->assertEquals(WP_Backup_Extension::TYPE_OUTPUT, Test_Extension::get_type());
		$this->assertTrue(Test_Extension::is_enabled());
		$this->assertTrue(Test_Extension::set_enabled(true));
		$this->assertInstanceOf('Test_Extension', $mgr->get_output());

		$mgr->add_menu_items();
		$this->assertEquals('get_menu', Test_Extension::$lastCalled);

		$mgr->complete();
		$this->assertEquals('complete', Test_Extension::$lastCalled);

		$mgr->failure();
		$this->assertEquals('failure', Test_Extension::$lastCalled);
	}
}