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

	public function setUp() {
		reset_globals();
	}

	public function tearDown() {
		Mockery::close();
	}

	public function testOutFileNotInDropbox() {
		WP_Backup_Registry::setConfig(Mockery::mock('Config')
			->shouldReceive('get_dropbox_path')
			->with(__DIR__, __FILE__, false)
			->andReturn('/DropboxPath')
			->once()

			->shouldReceive('get_option')
			->with('last_backup_time')
			->never()

			->mock()
		);

		WP_Backup_Registry::setDropbox(Mockery::mock('Dropbox')
			->shouldReceive('get_directory_contents')
			->with('/DropboxPath')
			->andReturn(array())
			->once()

			->shouldReceive('upload_file')
			->with('/DropboxPath', __FILE__)
			->once()

			->mock()
		);

		$out = new WP_Backup_Output;
		$out->out(__DIR__, __FILE__);
	}

	public function testOutFileInDropboxButOlder() {
		WP_Backup_Registry::setConfig(Mockery::mock('Config')
			->shouldReceive('get_option')
			->with('last_backup_time')
			->andReturn(filemtime(__FILE__) - 1)
			->once()

			->shouldReceive('get_dropbox_path')
			->with(__DIR__, __FILE__, false)
			->andReturn('/DropboxPath')
			->once()

			->mock()
		);

		WP_Backup_Registry::setDropbox(Mockery::mock('Dropbox')
			->shouldReceive('get_directory_contents')
			->with('/DropboxPath')
			->andReturn(array(basename(__FILE__)))
			->once()

			->shouldReceive('upload_file')
			->with('/DropboxPath', __FILE__)
			->once()

			->mock()
		);

		$out = new WP_Backup_Output;
		$out->out(__DIR__, __FILE__);
	}

	public function testChunkedUpload() {
		$processed_file = new stdClass;
		$processed_file->name = __FILE__;
		$processed_file->offset = 123;
		$processed_file->uploadid = 456;

		WP_Backup_Registry::setConfig(Mockery::mock('Config')
			->shouldReceive('get_dropbox_path')
			->with(__DIR__, __FILE__, false)
			->andReturn('/DropboxPath')
			->once()

			->shouldReceive('get_option')
			->with('last_backup_time')
			->never()

			->mock()
		);

		WP_Backup_Registry::setDropbox(Mockery::mock('Dropbox')
			->shouldReceive('get_directory_contents')
			->with('/DropboxPath')
			->andReturn(array())
			->once()

			->shouldReceive('chunk_upload_file')
			->with('/DropboxPath', __FILE__, $processed_file)
			->once()

			->mock()
		);

		$out = new WP_Backup_Output;
		$out->set_chunked_upload_threashold(0);
		$out->out(__DIR__, __FILE__, $processed_file);
	}

	public function testOutFileUploadWarning() {
		WP_Backup_Registry::setConfig(Mockery::mock('Config')
			->shouldReceive('get_dropbox_path')
			->with(__DIR__, __FILE__, false)
			->andReturn('/DropboxPath')
			->once()

			->mock()
		);

		WP_Backup_Registry::setDropbox(Mockery::mock('Dropbox')
			->shouldReceive('get_directory_contents')
			->with('/DropboxPath')
			->andReturn(array())
			->once()

			->shouldReceive('upload_file')
			->with('/DropboxPath', __FILE__)
			->andThrow(new Exception('<div>Bad Bad Bad</div>'))
			->once()

			->mock()
		);

		WP_Backup_Registry::setLogger(Mockery::mock('Logger')
			->shouldReceive('log')
			->with("Error uploading '" . __FILE__ . "' to Dropbox: Bad Bad Bad")
			->once()

			->mock()
		);

		$out = new WP_Backup_Output;
		$out->out(__DIR__, __FILE__);
	}
}