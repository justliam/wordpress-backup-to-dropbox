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
		$this->out = new WP_Backup_Output($this->dropbox, false);

		$fh = fopen(__DIR__ . '/Out/file.txt', 'w');
		fwrite($fh, "file.txt");
		fclose($fh);
	}

	public function tearDown() {
		unlink(__DIR__ . '/Out/file.txt');
		WP_Backup_Config::construct()->clean_up();
	}

	public function testGetCachedVal() {
		$config = WP_Backup_Config::construct();
		$out = new WP_Backup_Output($this->dropbox);

		$config->set_option('last_backup_time', 111);
		$this->assertEquals(111, $out->get_last_backup_time());

		$config->set_option('last_backup_time', 222);
		$this->assertEquals(111, $out->get_last_backup_time());

		$config->set_option('dropbox_location', 'DropboxLocation');
		$this->assertEquals('DropboxLocation', $out->get_dropbox_location());

		$config->set_option('dropbox_location', 'Meh');
		$this->assertEquals('DropboxLocation', $out->get_dropbox_location());
	}

	public function testOutFileNotInDropbox() {
		$config = WP_Backup_Config::construct();
		$config->set_option('dropbox_location', 'DropboxLocation');

		$this
			->dropbox
			->shouldReceive('get_directory_contents')
			->once()
			->with('DropboxLocation/Out')
			->andReturn(array())
			->shouldReceive('upload_file')
			->once()
			->with('DropboxLocation/Out', 'file.txt')
			;

		$this->out->out(__DIR__, __DIR__ . '/Out/file.txt');

		$uploaded = $config->get_uploaded_files();
		$this->assertNotEmpty($uploaded);
		$this->assertEquals(__DIR__ . '/Out/file.txt', $uploaded[0]);
	}

	public function testOutFileInDropboxButOlder() {
		$config = WP_Backup_Config::construct();
		$config->set_option('dropbox_location', 'DropboxLocation');
		$config->set_option('last_backup_time', filemtime(__DIR__ . '/Out/file.txt') - 1);

		$this
			->dropbox
			->shouldReceive('get_directory_contents')
			->once()
			->with('DropboxLocation/Out')
			->andReturn(array('file.txt'))
			->shouldReceive('upload_file')
			->once()
			->with('DropboxLocation/Out', 'file.txt')
			;

		$this->out->out(__DIR__, __DIR__ . '/Out/file.txt');

		$uploaded = $config->get_uploaded_files();
		$this->assertNotEmpty($uploaded);
		$this->assertEquals(__DIR__ . '/Out/file.txt', $uploaded[0]);
	}

	public function testOutFileInDropboxAndNotUpdated() {
		$config = WP_Backup_Config::construct();
		$config->set_option('dropbox_location', 'DropboxLocation');
		$config->set_option('last_backup_time', filemtime(__DIR__ . '/Out/file.txt') + 1);

		$this
			->dropbox
			->shouldReceive('get_directory_contents')
			->once()
			->with('DropboxLocation/Out')
			->andReturn(array('file.txt'))
			;

		$this->out->out(__DIR__, __DIR__ . '/Out/file.txt');

		$uploaded = $config->get_uploaded_files();
		$this->assertEmpty($uploaded);
	}

	public function testOutFileUploadWarning() {
		$config = WP_Backup_Config::construct();
		$config->set_option('dropbox_location', 'DropboxLocation');

		$this
			->dropbox
			->shouldReceive('get_directory_contents')
			->once()
			->with('DropboxLocation/Out')
			->andReturn(array())
			->shouldReceive('upload_file')
			->andThrow(new Exception('Error'))
			;

		$this->out->out(__DIR__, __DIR__ . '/Out/file.txt');

		$uploaded = $config->get_uploaded_files();
		$history = $config->get_history();
		$this->assertEquals(
			"Could not upload '/Users/mikey/Documents/wpb2d/git/WordPress-Backup-to-Dropbox/Tests/Out/file.txt' due to the following error: Error",
			$history[0][2]
		);
	}
	public function testOutFileUploadUnauthorized() {
		$config = WP_Backup_Config::construct();
		$config->set_option('dropbox_location', 'DropboxLocation');

		$this
			->dropbox
			->shouldReceive('get_directory_contents')
			->once()
			->with('DropboxLocation/Out')
			->andReturn(array())
			->shouldReceive('upload_file')
			->andThrow(new Exception('Unauthorized'))
			;

		try {
			$this->out->out(__DIR__, __DIR__ . '/Out/file.txt');
		} catch (Exception $e) {
			return;
		}

		$this->fail('An expected exception has not been raised.');
	}
}