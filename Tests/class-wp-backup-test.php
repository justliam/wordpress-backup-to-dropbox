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
			"A database backup cannot be created because WordPress does not have write access to 'Tests', please create the folder 'backups' manually.",
			$history[0][2]
		);
	}

	public function testBackupDatabase() {
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

		$this->config->set_option('dump_location', '/Out');
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

		$this->backup->backup_database();
		$this->assertEmpty($this->config->get_history());
	}
}
?>
