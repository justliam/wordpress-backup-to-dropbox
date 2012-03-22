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
require_once '../Classes/class-wp-output.php';
require_once '../Classes/class-wp-backup-config.php';

class WP_Output_Test extends PHPUnit_Framework_TestCase {

	private $out;

	public function setUp() {
		$mockDropbox = Mockery::mock('Dropbox_Facade');
		$this->out = new WP_Output($mockDropbox);
	}

	public function testGetMaxFileSize() {
		$memory_limit_string = ini_get('memory_limit');
		$memory_limit = preg_replace('/\D/', '', $memory_limit_string) * 1048576;
		$this->assertEquals($memory_limit / 2.5, $this->out->get_max_file_size());
	}

	public function testOut() {
		$this->out->out( dirname( __FILE__ ), basename( __FILE__ ) );
	}
}