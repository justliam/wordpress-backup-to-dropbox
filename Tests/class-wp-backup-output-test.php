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
require_once '../Classes/class-wp-backup-extension-manager.php';
require_once '../Classes/class-wp-backup-output.php';
require_once '../Classes/class-wp-backup-config.php';

class WP_Backup_Output_Test extends PHPUnit_Framework_TestCase {

	private $out;
	private $dropbox;

	public function setUp() {
		$this->dropbox = Mockery::mock('Dropbox_Facade');
		$this->out = new WP_Backup_Output($this->dropbox);
	}

	public function testOut() {
		WP_Backup_Config::construct()->set_option()
		$this
			->dropbox
			->shouldReceive('get_directory_contents')
			->with()
			;
		$this->out->out( dirname( __FILE__ ), basename( __FILE__ ) );
	}
}