<?php
/**
 * A test for the WP_Backup class
 *
 * @copyright Copyright (C) 2011-2012 Michael De Wildt. All rights reserved.
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

class WP_Backup_Test extends PHPUnit_Framework_TestCase {

	private $backup;
	private $dropbox;
	private $output;
	private $config;

	public function tearDown() {
		Mockery::close();
	}

	public function setUp() {
		WP_Backup_Registry::setDropbox(Mockery::mock('Dropbox'));

		$db = Mockery::mock('DB')
			->shouldReceive('get_results')
			->andReturn(array())

			->mock()
			;

		$db->prefix = 'wp_';

		WP_Backup_Registry::setDatabase($db);
	}

	public function testBackupPath() {
		WP_Backup_Registry::setConfig(Mockery::mock('Config')
			->shouldReceive('get_backup_dir')
			->andReturn(__DIR__ . '/BackupTest/')
			->mock()

			->shouldReceive('get_option')
			->with('in_progress')
			->andReturn(true)
			->once()

			->shouldReceive('get_option')
			->with('last_backup_time')
			->never()

			->shouldReceive('get_option')
			->with('total_file_count')
			->andReturn(1800)
			->once()

			->mock()
		);


		$output = Mockery::mock('Output')
			->shouldReceive('out')
			->with(
				'DropboxPath',
				Mockery::anyOf(
					__DIR__ . '/class-file-list-test.php',
					__DIR__ . '/class-wp-backup-config-test.php',
					__DIR__ . '/class-wp-backup-database-test.php',
					__DIR__ . '/class-wp-backup-extension-manager-test.php',
					__DIR__ . '/class-wp-backup-logger-test.php',
					__DIR__ . '/class-wp-backup-output-test.php',
					__DIR__ . '/class-wp-backup-test.php',
					__DIR__ . '/mock-wp-functions.php',
					__DIR__ . '/phpunit.xml',
					__DIR__ . '/phpunit'
				),
				false
			)
			->times(10)

			->mock()
			;

		$backup = new WP_Backup($output);
		$backup->backup_path(__DIR__, 'DropboxPath');
	}

	public function testBackupPathWithExcludedFiles() {

		$file1 = new stdClass;
		$file1->file = __DIR__ . '/class-file-list-test.php';

		$file2 = new stdClass;
		$file2->file = __DIR__ . '/class-wp-backup-config-test.php';

		$file3 = new stdClass;
		$file3->file = __DIR__ . '/class-wp-backup-database-test.php';

		$file4 = new stdClass;
		$file4->file = __DIR__ . '/class-wp-backup-extension-manager-test.php';

		$db = Mockery::mock('DB')
			->shouldReceive('get_results')
			->with('SELECT file FROM wp_wpb2d_excluded_files WHERE isdir = 0')
			->andReturn(array($file1, $file2, $file3, $file4))
			;

		$db->prefix = 'wp_';

		WP_Backup_Registry::setDatabase($db);

		WP_Backup_Registry::setConfig(Mockery::mock('Config')
			->shouldReceive('get_backup_dir')
			->andReturn(__DIR__ . '/BackupTest/')
			->mock()

			->shouldReceive('get_option')
			->with('in_progress')
			->andReturn(true)
			->once()

			->shouldReceive('get_option')
			->with('last_backup_time')
			->never()

			->shouldReceive('get_option')
			->with('total_file_count')
			->andReturn(1800)
			->once()

			->mock()
		);

		$output = Mockery::mock('Output')
			->shouldReceive('out')
			->with(
				'DropboxPath',
				Mockery::anyOf(
					__DIR__ . '/class-wp-backup-logger-test.php',
					__DIR__ . '/class-wp-backup-output-test.php',
					__DIR__ . '/class-wp-backup-test.php',
					__DIR__ . '/mock-wp-functions.php',
					__DIR__ . '/phpunit.xml',
					__DIR__ . '/phpunit'
				),
				false
			)
			->times(10)

			->mock()
			;

		$backup = new WP_Backup($output);
		$backup->backup_path(__DIR__, 'DropboxPath');
	}

	// public function testBackupPathWithExcludedFile() {
	// 	File_List::construct()->set_excluded(__DIR__ . '/Out');
	// 	$this->config->set_option('in_progress', true);

	// 	$this
	// 		->output
	// 		->shouldReceive('out')
	// 		->with(__DIR__,	Mockery::not(__DIR__ . '/Out/bigFile.txt'))
	// 		->times(9)

	// 		->shouldReceive('end')
	// 		->once()
	// 		;

	// 	$this->backup->backup_path(__DIR__, 'DropboxLocation');
	// }

	// public function testBackupPathWithProcessedFile() {
	// 	$this->config->complete();
	// 	$this->config->set_option('in_progress', true);
	// 	$this->config->add_processed_files(array(__FILE__, __DIR__ . '/class-file-list-test.php'));

	// 	$this
	// 		->output
	// 		->shouldReceive('out')
	// 		->with(
	// 			__DIR__,
	// 			Mockery::notAnyOf(
	// 				__FILE__,
	// 				__DIR__ . '/class-file-list-test.php'
	// 			)
	// 		)
	// 		->times(7)

	// 		->shouldReceive('end')
	// 		->once()
	// 		;

	// 	$this->backup->backup_path(__DIR__, 'DropboxLocation');
	// }

	// public function testExecuteNotAuthorized() {
	// 	$this
	// 		->dropbox
	// 		->shouldReceive('is_authorized')
	// 		->andReturn(false)
	// 		;

	// 	$this->backup->execute();

	// 	$log = WP_Backup_Logger::get_log();
	// 	$this->assertNotEmpty($log);
	// 	$this->assertEquals(
	// 		"00:00:00: Your Dropbox account is not authorized yet.",
	// 		$log[0]
	// 	);
	// }

	// public function testBackupNow() {
	// 	$this->backup->backup_now();
	// 	$this->assertEquals(time(), wp_next_scheduled('execute_instant_drobox_backup'));
	// }

	// public function testStop() {
	// 	$this->config->set_option('in_progress', false);

	// 	$this->backup->stop();

	// 	$this->assertFalse($this->config->get_option('in_progress'));

	// 	$this->assertEmpty($this->config->get_processed_files());
	// }

	// public function testCreateDumpDir() {
	// 	$this->backup->create_dump_dir();
	// 	$this->assertTrue(file_exists(WP_CONTENT_DIR . '/backups'));
	// }
}
