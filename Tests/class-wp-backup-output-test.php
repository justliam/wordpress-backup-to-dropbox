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

class WP_Backup_Output_Test extends PHPUnit_Framework_TestCase {

	private $out;
	private $config;
	private $dropbox;

	public function setUp() {
		reset_globals();
		$this->dropbox = Mockery::mock('Dropbox_Facade');

		$this->config = WP_Backup_Config::construct();
		$this->config->set_option('store_in_subfolder', true);
		$this->config->set_option('dropbox_location', 'DropboxLocation');

		$this->out = new WP_Backup_Output($this->dropbox, $this->config);

		if (!file_exists(__DIR__ . '/Out'))
			mkdir(__DIR__ . '/Out');

		$fh = fopen(__DIR__ . '/Out/file.txt', 'w');
		fwrite($fh, "file.txt");
		fclose($fh);
	}

	public function tearDown() {
		@unlink(__DIR__ . '/Out/file.txt');
		@unlink(__DIR__ . '/Out/bigFile.txt');

		$this->config->complete();
		Mockery::close();
	}

	public function testOutFileNotInDropbox() {
		$this->config->set_option('last_backup_time', time());
		$this
			->dropbox

			->shouldReceive('get_directory_contents')
			->with('DropboxLocation/Out')
			->andReturn(array())
			->once()

			->shouldReceive('upload_file')
			->with('DropboxLocation/Out', __DIR__ . '/Out/file.txt')
			->once()
			;

		$this->out->out(__DIR__, __DIR__ . '/Out/file.txt');
	}

	public function testOutFileInDropboxButOlder() {
		$this->config->set_option('last_backup_time', filemtime(__DIR__ . '/Out/file.txt') - 1);

		$this
			->dropbox

			->shouldReceive('get_directory_contents')
			->with('DropboxLocation/Out')
			->andReturn(array('file.txt'))
			->once()

			->shouldReceive('upload_file')
			->with('DropboxLocation/Out', __DIR__ . '/Out/file.txt')
			->once()
			;

		$this->out->out(__DIR__, __DIR__ . '/Out/file.txt');
	}

	public function testOutFileInDropboxAndNotUpdated() {
		$this->config->complete();
		$this->config->log_finished_time();

		$this
			->dropbox

			->shouldReceive('get_directory_contents')
			->with('DropboxLocation/Out')
			->andReturn(array('file.txt'))
			->once()

			->shouldReceive('upload_file')
			->never()
			;

		$this->out = new WP_Backup_Output($this->dropbox, $this->config);

		$this->out->out(__DIR__, __DIR__ . '/Out/file.txt');

		$uploaded = $this->config->get_processed_files();
		$this->assertEmpty($uploaded);
	}

	public function testOutFileUploadWarning() {
		$this
			->dropbox

			->shouldReceive('get_directory_contents')
			->with('DropboxLocation/Out')
			->once()

			->andReturn(array())
			->shouldReceive('upload_file')
			->andThrow(new Exception('<div>Bad Bad Bad</div>'))
			->once()
			;

		$this->out->out(__DIR__, __DIR__ . '/Out/file.txt');

		$dir = __DIR__;
		$time = date('H:i:s', strtotime(current_time('mysql')));

		$log = WP_Backup_Logger::get_log();
		$this->assertEquals(
			"$time: Error uploading '$dir/Out/file.txt' to Dropbox: Bad Bad Bad",
			$log[0]
		);
	}

	public function testOutFileUploadFileTooLarge() {

		if (!file_exists(__DIR__ . '/Out/bigFile.txt') || filesize(__DIR__ . '/Out/bigFile.txt') < CHUNKED_UPLOAD_THREASHOLD) {
			$fh = fopen(__DIR__ . '/Out/bigFile.txt', 'w');
	        for ($i = 0; $i < (CHUNKED_UPLOAD_THREASHOLD / 100); $i++) {
	            fwrite($fh, "..................................................");
	            fwrite($fh, "..................................................");
	        }
			fclose($fh);
		}

		$this->out = new WP_Backup_Output($this->dropbox, $this->config);

		$this
			->dropbox

			->shouldReceive('get_directory_contents')
			->andReturn(array())
			->once()

			->shouldReceive('upload_file')
			->never()

			->shouldReceive('chunk_upload_file')
			->once()
			;

		$this->out->out(__DIR__, __DIR__ . '/Out/bigFile.txt');
	}
}