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
	private $wpdb;
	private $output;
	private $config;

	public function setUp() {
		reset_globals();
		set_current_time('2012-03-12 00:00:00');
		$this->config = WP_Backup_Config::construct();
		$this->output = Mockery::mock('WP_Backup_Output');
		$this->dropbox = Mockery::mock('Dropbox_Facade');
		$this->wpdb = Mockery::mock('Wpdb');
		$this->backup = new WP_Backup($this->dropbox, $this->wpdb, $this->output);
	}

	public function testBackupPath() {
		$this->config->set_in_progress(false);

		$this->backup->backup_path(__DIR__, 'DropboxLocation');

		$this->config->set_in_progress(true);

		$this
			->output
			->shouldReceive('out')
			->with(__DIR__, Mockery::any())
			;

		$this->backup->backup_path(__DIR__, 'DropboxLocation');
	}

	public function testBackupDatabaseNoWriteAcess() {
		$this
			->wpdb
			->shouldReceive('get_results')
			->with('SHOW TABLES', ARRAY_N)
			;

		$this->backup->backup_database();

		$history = $this->config->get_history();
		$this->assertNotEmpty($history);
		$this->assertEquals(
			"A database backup cannot be created because WordPress does not have write access to 'WordPress-Backup-to-Dropbox/backups', please ensure this directory has write access.",
			$history[0][2]
		);
	}

	private function setUpDbMock() {
		$tableData = array();
		for ($i = 0; $i < 10; $i++) {
			$tableData[] = array(
				'field1' => 'value1',
				'field2' => 'value2',
				'field3' => 'value3',
				'field4' => 'value4',
				'field5' => 'value5',
			);
		}

		$this->config->set_option('dump_location', 'Tests/Out');
		$this
			->wpdb

			->shouldReceive('get_results')
			->with('SHOW TABLES', ARRAY_N)
			->andReturn(array(array('table1'), array('table2')))

			->shouldReceive('get_row')
			->with('SHOW CREATE TABLE table1', ARRAY_N)
			->andReturn(array(1, 'SHOW CREATE TABLE table1'))

			->shouldReceive('get_row')
			->with('SHOW CREATE TABLE table2', ARRAY_N)
			->andReturn(array(1, 'SHOW CREATE TABLE table2'))

			->shouldReceive('get_var')
			->with('SELECT COUNT(*) FROM table1')
			->andReturn(0)

			->shouldReceive('get_var')
			->with('SELECT COUNT(*) FROM table2')
			->andReturn(20)

			->shouldReceive('get_results')
			->with('SELECT * FROM table2 LIMIT 10 OFFSET 0', ARRAY_A)
			->andReturn($tableData)

			->shouldReceive('get_results')
			->with('SELECT * FROM table2 LIMIT 10 OFFSET 10', ARRAY_A)
			->andReturn($tableData)
			;
	}

	public function testBackupDatabase() {
		$this->setUpDbMock();

		$this->backup->backup_database();
		$this->assertEmpty($this->config->get_history());
	}

	public function testExecuteNotAuthorized() {
		$this
			->dropbox
			->shouldReceive('is_authorized')
			->andReturn(false)
			;

		$this->backup->execute();

		$history = $this->config->get_history();
		$this->assertNotEmpty($history);
		$this->assertEquals(
			"Your Dropbox account is not authorized yet.",
			$history[0][2]
		);
	}

	public function testExecute() {
		$this
			->dropbox
			->shouldReceive('is_authorized')
			->andReturn(true)
			;

		$this->setUpDbMock();

		$this
			->output
			->shouldReceive('out')
			;

		$this->backup->execute();

		$history = $this->config->get_history();
		$this->assertNotEmpty($history);
		$this->assertEquals(strtotime('2012-03-12 00:00:00'), $history[0][0]);
		$this->assertEquals(WP_Backup_Config::BACKUP_STATUS_FINISHED, $history[0][1]);
	}


	public function testBackupNow() {
		$this->backup->backup_now();
		$this->assertEquals(time(), wp_next_scheduled('execute_instant_drobox_backup'));
	}

	public function testStop() {
		$this->config->set_in_progress(true);

		$this->backup->stop();

		$this->assertFalse($this->config->in_progress());
		$this->assertEquals(time(), $this->config->get_option('last_backup_time'));

		$history = $this->config->get_history();
		$this->assertNotEmpty($history);
		$this->assertEquals(
			"Backup stopped by user.",
			$history[0][2]
		);

		$this->assertEmpty($this->config->get_processed_files());
	}

	public function testCreateDumpDir() {
		$this->config->set_option('dump_location', 'Tests/Out/Dump');
		$this->backup->create_dump_dir();
		$this->assertTrue(file_exists('Out/Dump'));
		rmdir('Out/Dump');
	}
}
?>
