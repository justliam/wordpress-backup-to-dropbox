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
	/**
	 * @var WP_Backup
	 */
	protected $backup;
	private $config;

	/**
	 * @var Mock_Dropbox_Facade
	 */
	private $mock_dropbox_facade;

	/**
	 * Override the time returned by current_time in mock-wp-functions.php
	 * @param  $time
	 * @return int
	 */
	private function setBlogTime( $time ) {
		global $current_time;
		$current_time = $time;
		return strtotime( $time );
	}

	/**
	 * Creates a file
	 * @param $name
	 * @return void
	 */
	private function create_file( $name ) {
		$fh = fopen( $name, 'a' );
		fwrite( $fh, 'WRITE' );
		fclose( $fh );
	}

	/**
	 * @param int $count
	 * @return void
	 */
	private function writeToFiles( $count = 2 ) {
		$fh = fopen( 'Out/changed_file_one.txt', 'a' );
		fwrite( $fh, 'WRITE' );
		fclose( $fh );
		if ( $count > 1 ) {
			$fh = fopen( 'Out/changed_file_two.txt', 'a' );
			fwrite( $fh, 'WRITE' );
			fclose( $fh );
		}
	}

	/**
	 * Create a random file inside the out directory of a given size in megabytes
	 * @param  $name
	 * @param  $size
	 * @return void
	 */
	private function createFile( $name, $size ) {
		$fh = fopen( "Out/$name", 'w' );
		for ( $i = 0; $i < 10000 * $size; $i++ ) {
			fwrite( $fh, '1111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111' );
		}
		fclose( $fh );
	}

	/**
	 * Sets up this test with a WP_Backup object with a Mock_Dropbox_Facade and Mock_WpDB. The initial options will
	 * be set as well.
	 * @return void
	 */
	protected function setUp() {
		$this->mock_dropbox_facade = new Mock_Dropbox_Facade();
		$this->config = new WP_Backup_Config( new Mock_WpDb() );
		$this->backup = new WP_Backup( $this->mock_dropbox_facade, $this->config, new Mock_WpDb() );
		$this->config->set_options( 'Out/', 'Dropbox' );
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {
		if ( file_exists( 'Out/UnitTest_DB-backup.sql' ) ) {
			unlink( 'Out/UnitTest_DB-backup.sql' );
		}
		if ( file_exists( 'Out/changed_file_one.txt' ) ) {
			unlink( 'Out/changed_file_one.txt' );
		}
		if ( file_exists( 'Out/changed_file_two.txt' ) ) {
			unlink( 'Out/changed_file_two.txt' );
		}
		if ( file_exists( 'Out/.htaccess' ) ) {
			unlink( 'Out/.htaccess' );
		}
		if ( file_exists( 'php.ini' ) ) {
			unlink( 'php.ini' );
		}
		if ( file_exists( 'Out/file1.txt' ) ) {
			unlink( 'Out/file1.txt' );
		}
		if ( file_exists( 'Out/file2.txt' ) ) {
			unlink( 'Out/file2.txt' );
		}
		if ( file_exists( 'Out/file3.txt' ) ) {
			unlink( 'Out/file3.txt' );
		}
		if ( file_exists( 'Out/big.txt' ) ) {
			unlink( 'Out/big.txt' );
		}
		$usage = $this->mock_dropbox_facade->get_memory_usage();
		if ( $usage != '0M' ) {
			echo "File upload peak memory usage: $usage\n";
		}
	}

	/**
	 * We are using a mock Dropbox facade here so we can test that all the files are backed up correctly
	 * @return void
	 */
	public function testBackup_website_full_successful_backup() {
		set_time_limit( 0 );
		ini_set( 'memory_limit', '27M' );
		$this->createFile( 'file.txt', 10 );

		$this->config->set_in_progress( true );
		$this->config->log( WP_Backup::BACKUP_STATUS_STARTED );

		$this->backup = new WP_Backup( $this->mock_dropbox_facade, $this->config, new Mock_WpDb() );

		$this->backup->backup_path( ABSPATH, 'Dropbox');
		unlink( 'Out/file.txt' );
		$files_processed = $this->mock_dropbox_facade->get_files_processes();

		$this->assertNotEmpty( $files_processed );
		$this->assertEquals( 7, count( $files_processed ) );

		$dir = dirname( __FILE__ );
		$expected = array(
			"$dir/class-file-list-test.php",
			"$dir/class-wp-backup-test.php",
			"$dir/Mocks/class-mock-dropbox-facade.php",
			"$dir/Mocks/class-mock-wpdb.php",
			"$dir/Mocks/mock-wp-functions.php",
			"$dir/Out/expected.sql",
			"$dir/Out/file.txt",
		);
		$this->assertEquals( $expected, $files_processed );

		$this->config->log( WP_Backup::BACKUP_STATUS_FINISHED );

		ini_restore( 'memory_limit ' );
	}

	/**
	 * A initial backup has been performed and we are doing another. This time only the files that have changed since
	 * then will be included.
	 * @return void
	 */
	public function testBackup_website_only_changed_files() {
		$this->testBackup_website_full_successful_backup();
		sleep( 1 );

		$this->writeToFiles();
		$this->mock_dropbox_facade->reset_files_processed();

		$this->config->set_in_progress( true );
		$this->config->log( WP_Backup::BACKUP_STATUS_STARTED );

		$this->backup = new WP_Backup( $this->mock_dropbox_facade, $this->config );

		$this->assertTrue( $this->backup->backup_path( ABSPATH, './') );
		$this->config->log( WP_Backup::BACKUP_STATUS_FINISHED );

		$files_processed = $this->mock_dropbox_facade->get_files_processes();

		$this->assertNotEmpty( $files_processed );
		$this->assertEquals( 2, count( $files_processed ) );

		$dir = dirname( __FILE__ );
		$expected = array(
			"$dir/Out/changed_file_one.txt",
			"$dir/Out/changed_file_two.txt",
		);

		$this->assertEquals( $expected, $files_processed );

		$this->writeToFiles( 1 );
		$this->mock_dropbox_facade->reset_files_processed();

		$this->config->log( WP_Backup::BACKUP_STATUS_STARTED );
		$this->assertTrue( $this->backup->backup_path( ABSPATH, 'Out') );
		$this->config->log( WP_Backup::BACKUP_STATUS_FINISHED );

		$files_processed = $this->mock_dropbox_facade->get_files_processes();

		$this->assertNotEmpty( $files_processed );
		$this->assertEquals( 1, count( $files_processed ) );

		$dir = dirname( __FILE__ );
		$expected = array(
			"$dir/Out/changed_file_one.txt",
		);

		$this->assertEquals( $expected, $files_processed );
	}

	/**
	 * We are using a mock Dropbox facade here so we can test that all the files are backed up correctly
	 * @return void
	 */
	public function testBackup_website_full_backup_with_warnings() {
		ini_set( 'memory_limit', '20M' );
		$this->createFile( 'big.txt', 10 );

		$this->config->set_in_progress( true );
		$this->config->log( WP_Backup::BACKUP_STATUS_STARTED );

		$this->backup = new WP_Backup( $this->mock_dropbox_facade, $this->config );

		$this->backup->backup_path( ABSPATH, 'Out');
		$files_processed = $this->mock_dropbox_facade->get_files_processes();

		$this->assertNotEmpty( $files_processed );
		$this->assertEquals( 6, count( $files_processed ) );

		$dir = dirname( __FILE__ );
		$expected = array(
			"$dir/class-file-list-test.php",
			"$dir/class-wp-backup-test.php",
			"$dir/Mocks/class-mock-dropbox-facade.php",
			"$dir/Mocks/class-mock-wpdb.php",
			"$dir/Mocks/mock-wp-functions.php",
			"$dir/Out/expected.sql",
		);

		$this->assertEquals( $expected, $files_processed );

		$this->config->log( WP_Backup::BACKUP_STATUS_FINISHED );

		$this->assertEquals( time(), $this->backup->get_last_backup_time() );

		unlink( 'Out/big.txt' );

		$history = $this->config->get_history();

		list( $time, $status, $msg ) = array_shift( $history );
		$this->assertEquals( WP_Backup::BACKUP_STATUS_FINISHED, $status );

		list( $time, $status, $msg ) = array_shift( $history );
		$this->assertEquals( WP_Backup::BACKUP_STATUS_WARNING, $status );
		$this->assertEquals( "file 'big.txt' exceeds 40 percent of your PHP memory limit. The limit must be increased to back up this file.", $msg );

		list( $time, $status, $msg ) = array_shift( $history );
		$this->assertEquals( WP_Backup::BACKUP_STATUS_STARTED, $status );

		ini_restore( 'memory_limit ' );
	}

	/**
	 * Test to make sure the backup is stopped if dropbox becomes unauthorized
	 * @return void
	 */
    public function testExecute_unauthorised() {
        $this->config->log( WP_Backup::BACKUP_STATUS_STARTED );

		$this->mock_dropbox_facade->is_authorized = false;
		$this->backup = new WP_Backup( $this->mock_dropbox_facade, new WP_Backup_Config( new Mock_WpDb() ), new Mock_WpDb() );

        $this->backup->execute();

		$history = $this->config->get_history();
        list( $time, $status, $msg ) = array_shift( $history );
        $this->assertEquals( WP_Backup::BACKUP_STATUS_FAILED, $status );
		$this->assertEquals( 'Your Dropbox account is not authorized yet.', $msg );
	}


		/**
     * Test to make sure the backup is stopped if dropbox becomes unauthorized
     * @return void
     */
    public function testBackup_unauthorised() {
        $this->config->log( WP_Backup::BACKUP_STATUS_STARTED );

		$this->mock_dropbox_facade->throw_unauthorized = true;
		$this->backup = new WP_Backup( $this->mock_dropbox_facade, new WP_Backup_Config( new Mock_WpDb() ), new Mock_WpDb() );

        $this->backup->execute();

		$history = $this->config->get_history();
        list( $time, $status, $msg ) = array_shift( $history );
        $this->assertEquals( WP_Backup::BACKUP_STATUS_FAILED, $status );
		$this->assertEquals( 'The plugin is no longer authorized with Dropbox.', $msg );
	}

	/**
	 * Backup database will grab every table for this blogs database, generate a create table statement and then
	 * generate inserts for each row of the table. The expected result based on the mock db functions is found in
	 * 'Out/expected.sql'.
	 * @return void
	 */
	public function testBackup_database() {
		$this->setBlogTime( '1984-03-12' );

		$this->assertTrue( $this->backup->backup_database() );

		$expected = file_get_contents( 'Out/expected.sql' );
		$actual = file_get_contents( 'Out/UnitTest_DB-backup.sql' );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * This will just simply add a wp cron item to start instantly with its own hook
	 * @return void
	 */
	public function testBackup_now() {
		$this->backup->backup_now();
		$schedule = wp_next_scheduled( 'execute_instant_drobox_backup' );
		$this->assertEquals( time(), $schedule );
	}

	/**
	 * When the user selects the current day a time that has past then the expected outcome is the next backup will be
	 * scheduled for the day and time selected next week.
	 * @return void
	 */
	public function testSetGet_schedule_where_time_of_day_has_past() {
		//The blog time is 10 hours from now
		$blog_time = strtotime( date( 'Y-m-d H:00:00', strtotime( '+10 hours' ) ) );
		$this->setBlogTime( date( 'Y-m-d H:i:s ', $blog_time ) );

		$this->config->set_schedule( date( 'D', $blog_time ), date( 'H', $blog_time ) . ':00', 'daily' );
		$schedule = $this->config->get_schedule();

		//Next week in the blog time time is expected
		$expected_date_time = date( 'Y-m-d', strtotime( '+7 days', $blog_time ) ) . ' ' . date( 'H', $blog_time ) . ':00:00';
		$this->assertEquals( $expected_date_time, date( 'Y-m-d H:i:s', $schedule[0] ) );
		$this->assertEquals( 'daily', $schedule[1] );

		//Next week in the server time is expected
		$schedule = wp_next_scheduled( 'execute_periodic_drobox_backup' );
		$this->assertEquals( strtotime( '+7 days', strtotime( date( 'Y-m-d H:00:00' ) ) ), $schedule );
	}

	/**
	 * If no day is supplied the it is assumed that the user has chosen the 'Daily' schedule so, this time, if they
	 * choose the current day and a time that has past the expected outcome is the next beck up will scheduled for the
	 * time selected tomorrow.
	 * @return void
	 */
	public function testSetGet_schedule_where_time_of_day_has_past_and_no_day_supplied() {
		//The blog time is -4 hours from now
		$blog_time = strtotime( date( 'Y-m-d H:00:00', strtotime( '-10 hours' ) ) );
		$this->setBlogTime( date( 'Y-m-d H:i:s ', $blog_time ) );

		$this->config->set_schedule( null, date( 'H', $blog_time ) . ':00', 'daily' );
		$schedule = $this->config->get_schedule();

		//Today in the blog time time is expected
		$expected_date_time = date( 'Y-m-d', strtotime( '+1 day', $blog_time ) ) . ' ' . date( 'H', $blog_time ) . ':00:00';
		$this->assertEquals( $expected_date_time, date( 'Y-m-d H:i:s', $schedule[0] ) );
		$this->assertEquals( 'daily', $schedule[1] );

		//Today in the server time is expected
		$schedule = wp_next_scheduled( 'execute_periodic_drobox_backup' );
		$this->assertEquals( strtotime( '+1 day', strtotime( date( 'Y-m-d H:00:00' ) ) ), $schedule );
	}

	/**
	 * Regardless of wether or not a day is supplied if the day is today and the selected time not passed then the back
	 * up will be scheduled for the time selected today
	 * @return void
	 */
	public function testSetGet_schedule_where_time_of_day_has_not_past() {
		//The blog time is 2 hours from now
		$blog_time = strtotime( date( 'Y-m-d H:00:00', strtotime( '-10 hours' ) ) );
		$this->setBlogTime( date( 'Y-m-d H:i:s ', $blog_time ) );

		$day = date( 'H', $blog_time ) + 1;
		if ( $day < 10 ) {
			$day = "0$day";
		}

		$this->config->set_schedule( date( 'D', $blog_time ), $day . ':00', 'daily' );
		$schedule = $this->config->get_schedule();

		//Today in the blog time time is expected
		$expected_date_time = date( 'Y-m-d', $blog_time ) . ' ' . $day . ':00:00';
		$this->assertEquals( $expected_date_time, date( 'Y-m-d H:i:s', $schedule[0] ) );
		$this->assertEquals( 'daily', $schedule[1] );

		//Today in the server time is expected
		$schedule = wp_next_scheduled( 'execute_periodic_drobox_backup' );
		$this->assertEquals( strtotime( '+1 hour', strtotime( date( 'Y-m-d H:00:00' ) ) ), $schedule );
	}

	/**
	 * Test the setting of the local and backup location.
	 * It the locations are not valid paths then an exception is raised.
	 * The Dropbox location may have spaces where the local back up location cannot
	 * @return void
	 */
	public function testSetGet_options() {
		//Test bad paths
		$bad_chars = array( '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '+', '=', '{', '}', ']', '[', ':', ';', '"', '\'', '<', '>', '?', ',', '~', '`', '|', '\\' );
		foreach ( $bad_chars as $bad_char ) {
			$error_msg = 'Invalid directory path. Path must only contain alphanumeric characters and the forward slash (\'/\') to separate directories.';
			$errors = $this->config->set_options( $bad_char, $bad_char );
			$this->assertNotEmpty( $errors );
			$this->assertEquals( $bad_char, $errors['dump_location']['original'] );
			$this->assertEquals( $error_msg, $errors['dump_location']['message'] );
			$this->assertEquals( $bad_char, $errors['dropbox_location']['original'] );
			$this->assertEquals( $error_msg, $errors['dump_location']['message'] );
		}

		//The there where errors so the data should remain as it was set in the unit test setup
		$config = $this->config->get_options();
		$this->assertEquals( 'Out', $config['dump_location'] );
		$this->assertEquals( 'Dropbox', $config['dropbox_location'] );

		//Test good paths
		$errors = $this->config->set_options( 'wp-content/backups', 'WordPressBackup' );
		$this->assertEmpty( $errors );
		$config = $this->config->get_options();
		$this->assertEquals( 'wp-content/backups', $config['dump_location'] );
		$this->assertEquals( 'WordPressBackup', $config['dropbox_location'] );

		//It is expected that any leading slashes are removed and extra slashes in between are removed
		$errors = $this->config->set_options( '///wp-content////backups///', '////WordPressBackups///SiteOne////' );
		$config = $this->config->get_options();
		$this->assertEmpty( $errors );
		$this->assertEquals( 'wp-content/backups', $config['dump_location'] );
		$this->assertEquals( 'WordPressBackups/SiteOne', $config['dropbox_location'] );
	}

	/**
	 * Check that the logger is working
	 * @return void
	 */
	public function testSetGet_log() {
		$expected_time = time();
		for ( $i = 0; $i < 100; $i++ ) {
			$this->config->log( WP_Backup::BACKUP_STATUS_WARNING, 'WARNING' );
		}

		$history = $this->config->get_history();
		$this->assertEquals( 100, count( $history ) );

		$this->config->log( WP_Backup::BACKUP_STATUS_FAILED, 'FAILED' );
		$history = $this->config->get_history();
		$this->assertEquals( 100, count( $history ) );

		list( $time, $status, $msg ) = array_shift( $history );
		$this->assertEquals( $expected_time, $time );
		$this->assertEquals( WP_Backup::BACKUP_STATUS_FAILED, $status );
		$this->assertEquals( 'FAILED', $msg );

		for ( $i = 0; $i < 99; $i++ ) {
			list( $time, $status, $msg ) = array_shift( $history );
			$this->assertEquals( $expected_time, $time );
			$this->assertEquals( WP_Backup::BACKUP_STATUS_WARNING, $status );
			$this->assertEquals( 'WARNING', $msg );
		}
	}

	/**
	 * @return void
	 */
	public function testExecute() {
		ini_set( 'memory_limit', '8M' );

		$start_time = $this->setBlogTime( '1984-03-12' );

		$this->backup->execute();

		$history = $this->config->get_history();

		list( $time, $status, $msg ) = array_shift( $history );
		$this->assertEquals( $start_time, $time );
		$this->assertEquals( WP_Backup::BACKUP_STATUS_FINISHED, $status );

		list( $time, $status, $msg ) = array_shift( $history );
		$this->assertEquals( $start_time, $time );
		$this->assertEquals( WP_Backup::BACKUP_STATUS_STARTED, $status );

		$dir = dirname( __FILE__ );
		$expected = array(
			"$dir/class-file-list-test.php",
			"$dir/class-wp-backup-test.php",
			"$dir/Mocks/class-mock-dropbox-facade.php",
			"$dir/Mocks/class-mock-wpdb.php",
			"$dir/Mocks/mock-wp-functions.php",
			"$dir/Out/expected.sql",
			"$dir/Out/UnitTest_DB-backup.sql",
		);

		$files_processed = $this->mock_dropbox_facade->get_files_processes();
		$this->assertEquals( $expected, $files_processed );

		$expected = file_get_contents( 'Out/expected.sql' );
		$actual = file_get_contents( 'Out/UnitTest_DB-backup.sql' );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @return void
	 */
	public function testExecute_withExcludedFiles() {

		$dir = dirname( __FILE__ );

		$file_list = new File_List( new Mock_WpDb() );

		$list = self::get_file_list();

		//If just a directory is set to INCLUDE or EXCLUDE then all of its sub files need to be updated accordingly
		$list[0][1] = File_List::EXCLUDED; //Mocks
		$list[4][1] = File_List::EXCLUDED; //Out
		$list[6][1] = File_List::EXCLUDED; //class-file-list-test.php

		$json = json_encode( $list );
		$file_list->set_file_list( $json );
		$file_list->save();

		//Add a set if files that are allowed in the backup to the white list
		$start_time = $this->setBlogTime( '1984-03-12' );

		$this->backup->execute();

		$history = $this->config->get_history();

		list( $time, $status, $msg ) = array_shift( $history );
		$this->assertEquals( $start_time, $time );
		$this->assertEquals( WP_Backup::BACKUP_STATUS_FINISHED, $status );

		list( $time, $status, $msg ) = array_shift( $history );
		$this->assertEquals( $start_time, $time );
		$this->assertEquals( WP_Backup::BACKUP_STATUS_STARTED, $status );

		$files_processed = $this->mock_dropbox_facade->get_files_processes();
		$expected = array(
			"$dir/class-wp-backup-test.php",
			"$dir/Out/UnitTest_DB-backup.sql",
		);
		$this->assertEquals( $expected, $files_processed );

		$expected = file_get_contents( 'Out/expected.sql' );
		$actual = file_get_contents( 'Out/UnitTest_DB-backup.sql' );

		$this->assertSame( $expected, $actual );
	}

	/* Sets the file list to pick up any new files that may exist on the system
	 * @param bool $init
	 * @return void
	 */
	public static function get_file_list() {
		$new_file_list = array();
		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( ABSPATH ), RecursiveIteratorIterator::SELF_FIRST );
		foreach ( $files as $file ) {
			$file = realpath( $file );
			if ( File_List::in_ignore_list( $file ) ) {
				continue;
			}
			if ( is_dir( $file) ) {
				$file .= '/';
			}
			$new_file_list[] = array( $file, File_List::INCLUDED ) ;
		}
		asort( $new_file_list );
		return array_values( $new_file_list );
	}
}

?>
