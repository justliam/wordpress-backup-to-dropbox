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

class WP_Backup_Config_Test extends PHPUnit_Framework_TestCase {

	private $config;

	public function setUp() {
		reset_globals();
		$this->config = WP_Backup_Config::construct();
	}

	public function testConstruct() {
		$this->assertEquals(false, $this->config->get_option('last_backup_time'));
		$this->assertEquals(false, $this->config->get_option('in_progress'));

		$log = $this->config->get_log();
		$this->assertEquals($log, array());
	}

	public function testConstructDudData() {
		global $options;
		$options['backup-to-dropbox-options'] = array('bad');

		$this->config = WP_Backup_Config::construct();
		$this->assertEquals(false, $this->config->get_option('last_backup_time'));
		$this->assertEquals(false, $this->config->get_option('in_progress'));
	}

	public function testAddBackupHistory() {
		for ($i = 0; $i < 30; $i++)
			$this->config->add_backup_history($i);

		$history = $this->config->get_history();
		$this->assertEquals(20, count($history));

		for ($i = 0; $i < 20; $i++)
			$this->assertEquals($i + 10, $history[$i]);
	}

	public function testLogGetLog() {
		set_current_time('2012-03-12 00:00:00');
		$this->config->log('Action1');
		set_current_time('2012-03-12 00:00:01');
		$this->config->log('Action2');

		$log = $this->config->get_log();

		$this->assertEquals($log[0], array(
			'time' => strtotime('2012-03-12 00:00:00'),
			'message' => 'Action1'
		));

		$this->assertEquals($log[1], array(
			'time' => strtotime('2012-03-12 00:00:01'),
			'message' => 'Action2'
		));
	}

	public function testGetUploadedFiles() {
		$files = $this->config->add_processed_files(array('File1', 'File2'));

		$files = $this->config->get_processed_files();
		$this->assertEquals($files, array('File1', 'File2'));

		$files = $this->config->add_processed_files(array('File3', 'File4'));

		$files = $this->config->get_processed_files();
		$this->assertEquals($files, array('File1', 'File2', 'File3', 'File4'));
	}

	public function testInProgess() {
		$this->assertFalse($this->config->get_option('in_progress'));

		$this->config->set_in_progress(true);
		$this->assertTrue($this->config->get_option('in_progress'));

		$this->config->set_in_progress(false);
		$this->assertFalse($this->config->get_option('in_progress'));
	}

	public function testSetGetScheduleWhereTimeOfDayHasPast() {
		$blog_time = strtotime(date('Y-m-d H:00:00', strtotime('+2 hours')));
		set_current_time(date('Y-m-d H:i:s ', $blog_time));

		$this->config->set_schedule(date('D', $blog_time), date('H', $blog_time) . ':00', 'daily');
		$schedule = $this->config->get_schedule();

		//Next week in the blog time time is expected
		$expected_date_time = date('Y-m-d', strtotime('+7 days', $blog_time)) . ' ' . date('H', $blog_time) . ':00:00';
		$this->assertEquals($expected_date_time, date('Y-m-d H:i:s', $schedule[0]));
		$this->assertEquals('daily', $schedule[1]);

		//Next week in the server time is expected
		$schedule = wp_next_scheduled('execute_periodic_drobox_backup');
		$this->assertEquals(strtotime('+7 days', strtotime(date('Y-m-d H:00:00'))), $schedule);
	}

	public function testSetGetScheduleWhereTimeOfDayHasPastAndNoDaySupplied() {
		$blog_time = strtotime(date('Y-m-d H:00:00', strtotime('+2 hours')));
		set_current_time(date('Y-m-d H:i:s ', $blog_time));

		$this->config->set_schedule(null, date('H') . ':00', 'daily');
		$schedule = $this->config->get_schedule();

		//Today in the blog time time is expected
		$expected_date_time = date('Y-m-d', strtotime('+1 day')) . ' ' . date('H') . ':00:00';
		$this->assertEquals($expected_date_time, date('Y-m-d H:i:s', $schedule[0]));
		$this->assertEquals('daily', $schedule[1]);

		//Today in the server time is expected
		$schedule = wp_next_scheduled('execute_periodic_drobox_backup');
		$this->assertEquals(strtotime('+1 day',  strtotime(date('Y-m-d H:00:00', strtotime('-2 hours')))), $schedule);
	}

	public function testSetGetScheduleWhereTimeOfDayHasNotPast() {
		$blog_time = strtotime(date('Y-m-d H:00:00', strtotime('+2 hours')));
		set_current_time(date('Y-m-d H:i:s ', $blog_time));

		$hour = date('H', $blog_time) + 1;
		if ($hour < 10) {
			$hour = "0$hour";
		}

		$this->config->set_schedule(date('D', $blog_time), $hour . ':00', 'weekly');
		$schedule = $this->config->get_schedule();

		$expected_date_time = date('Y-m-d', $blog_time) . ' ' . $hour . ':00:00';
		$this->assertEquals($expected_date_time, date('Y-m-d H:i:s', $schedule[0]));
		$this->assertEquals('weekly', $schedule[1]);

		$schedule = wp_next_scheduled('execute_periodic_drobox_backup');
		$this->assertEquals(strtotime(date('Y-m-d H:00:00', strtotime('+1 hours'))), $schedule);
	}

	public function testSetGetOptions() {
		//Test bad paths
		$bad_chars = array('!', '#', '$', '%', '^', '&', '*', '(', ')', '+', '=', '{', '}', ']', '[', ':', ';', '"', '\'', '<', '>', '?', ',', '~', '`', '|', '\\');
		foreach ($bad_chars as $bad_char) {
			$error_msg = 'The sub directory must only contain alphanumeric characters.';

			$options['dump_location'] = $bad_char;
			$options['dropbox_location'] = $bad_char;
			$options['in_progress'] = false;
			$options['last_backup_time'] = false;

			$errors = $this->config->set_options($options);
			$this->assertNotEmpty($errors);
			$this->assertEquals($bad_char, $errors['dump_location']['original']);
			$this->assertEquals($error_msg, $errors['dump_location']['message']);
			$this->assertEquals($bad_char, $errors['dropbox_location']['original']);
			$this->assertEquals($error_msg, $errors['dump_location']['message']);
		}

		//The there where errors so the data should remain as it was set in the unit test setup

		//Test good paths
		$options['dump_location'] = 'subdir';
		$options['dropbox_location'] = 'WordPressBackup';
		$errors = $this->config->set_options($options);

		$this->assertEmpty($errors, var_export($errors, true));

		$this->assertTrue(isset($options['last_backup_time']));
		$this->assertTrue(isset($options['in_progress']));
	}

	public function testCleanUp() {
		$this->config->set_schedule('Monday', '00:00:00', 'daily');

		set_current_time('2012-03-12 00:00:00');
		$this->config->log('Action1', 'File1');

		$this->config->clean_up();

		$this->assertEmpty($this->config->get_actions());
		$this->assertFalse(wp_next_scheduled('monitor_dropbox_backup_hook'));
		$this->assertFalse(wp_next_scheduled('run_dropbox_backup_hook'));
	}
}