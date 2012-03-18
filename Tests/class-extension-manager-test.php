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
require_once '../Classes/class-extension-manager.php';

class Extension_Manager_Test extends PHPUnit_Framework_TestCase {

	private $mgr;

	public function setUp() {
		$this->mgr = Extension_Manager::construct();
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

	public function testInstall() {
		$this->mgr->install(1, 'extension-file.php');
		$this->assertEquals(array(
			1 => 'extension-file.php'
		), $this->mgr->get_installed());
	}

	public function testInit() {
		$this->assertFalse(function_exists('new_func'));

		$this->mgr->install(1, 'extension.php');
		$this->mgr->init();

		$this->assertTrue(function_exists('new_func'));
		$this->assertTrue(new_func());

		unlink(EXTENSIONS_DIR . 'extension.php');
	}

}