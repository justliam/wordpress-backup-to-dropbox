<?php
/**
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

class WP_Backup_Database_Test extends PHPUnit_Framework_TestCase {

	private function assertOutput($actual, $expected) {
		$actual = explode("\n", file_get_contents($actual));
		$expected = explode("\n", $expected);

		for ($i = 0; $i < count($actual); $i++)
			$this->assertEquals($expected[$i], $actual[$i]);
	}

	public function tearDown() {
		Mockery::close();
		rmdir($this->config->get_backup_dir());
	}

	public function setUp() {
		reset_globals();
		set_current_time('2012-03-12 00:00:00');
		$this->config = WP_Backup_Config::construct();
		$this->config->set_option('dump_location', 'Out');
		if (!file_exists($this->config->get_backup_dir()))
			mkdir($this->config->get_backup_dir());
	}

	private function getTableData() {
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
		return $tableData;
	}

	public function testExecuteCore() {
		$wpdb = Mockery::mock('wpdb')

			->shouldReceive('tables')
			->andReturn(
				array(
					1 => 'table1',
					2 => 'table2'
				)
			)
			->once()

			->shouldReceive('get_row')
			->with('SHOW CREATE TABLE table1', ARRAY_N)
			->andReturn(array(1, $this->getCreateTable('table1')))
			->once()

			->shouldReceive('get_row')
			->with('SHOW CREATE TABLE table2', ARRAY_N)
			->andReturn(array(1, $this->getCreateTable('table2')))
			->once()

			->shouldReceive('get_var')
			->with('SELECT COUNT(*) FROM table1')
			->andReturn(0)
			->once()

			->shouldReceive('get_var')
			->with('SELECT COUNT(*) FROM table2')
			->andReturn(20)
			->once()

			->shouldReceive('get_results')
			->with('SELECT * FROM table2 LIMIT 10 OFFSET 0', ARRAY_A)
			->andReturn($this->getTableData())
			->once()

			->shouldReceive('get_results')
			->with('SELECT * FROM table2 LIMIT 10 OFFSET 10', ARRAY_A)
			->andReturn($this->getTableData())
			->once()

			->mock();

		$backup = new WP_Backup_Database_Core($wpdb, $this->config);
		$this->assertTrue($backup->execute());

		$out = $this->config->get_backup_dir() . '/TestDB-backup-core.sql';

		$this->assertOutput($out, $this->getExpectedCoreDBDump());

		unlink($out);
	}

	public function testExecutePlugins() {
		$wpdb = Mockery::mock('wpdb')

			->shouldReceive('tables')
			->andReturn(
				array(
					1 => 'table1',
					2 => 'table2'
				)
			)
			->once()

			->shouldReceive('get_results')
			->with('SHOW TABLES', ARRAY_N)
			->andReturn(
				array(
					array('table1'),
					array('table2'),
					array('table3'),
					array('table4'),
				)
			)
			->once()

			->shouldReceive('get_row')
			->with('SHOW CREATE TABLE table3', ARRAY_N)
			->andReturn(array(1, $this->getCreateTable('table3')))
			->once()

			->shouldReceive('get_var')
			->with('SELECT COUNT(*) FROM table3')
			->andReturn(5)
			->once()

			->shouldReceive('get_results')
			->with('SELECT * FROM table3 LIMIT 10 OFFSET 0', ARRAY_A)
			->andReturn($this->getTableData())
			->once()

			->shouldReceive('get_row')
			->with('SHOW CREATE TABLE table4', ARRAY_N)
			->andReturn(array(1, $this->getCreateTable('table4')))
			->once()

			->shouldReceive('get_var')
			->with('SELECT COUNT(*) FROM table4')
			->andReturn(5)
			->once()

			->shouldReceive('get_results')
			->with('SELECT * FROM table4 LIMIT 10 OFFSET 0', ARRAY_A)
			->andReturn($this->getTableData())
			->once()

			->mock();

		$backup = new WP_Backup_Database_Plugins($wpdb, $this->config);
		$this->assertTrue($backup->execute());

		$out = $this->config->get_backup_dir() . '/TestDB-backup-plugins.sql';

		$this->assertOutput($out, $this->getExpectedPluginDBDump());

		unlink($out);
	}

	private function getCreateTable($table) {
return "CREATE TABLE `$table` (\n" . <<<EOS
  `field1` varchar(255) default NULL,
  `field2` varchar(255) default NULL,
  `field3` varchar(255) default NULL,
  `field4` varchar(255) default NULL,
  `field5` varchar(255) default NULL,
  PRIMARY KEY  (`field1`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
EOS;
	}

	private function getExpectedCoreDBDump()
	{
return <<<EOS
-- WordPress Backup to Dropbox SQL Dump
-- Version 99
-- http://wpb2d.com
-- Generation Time: March 12, 2012 at 00:00

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Create and use the backed up database
--

CREATE DATABASE IF NOT EXISTS TestDB;
USE TestDB;

--
-- Table structure for table `table1`
--

CREATE TABLE `table1` (
  `field1` varchar(255) default NULL,
  `field2` varchar(255) default NULL,
  `field3` varchar(255) default NULL,
  `field4` varchar(255) default NULL,
  `field5` varchar(255) default NULL,
  PRIMARY KEY  (`field1`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table `table1` is empty
--

--
-- Table structure for table `table2`
--

CREATE TABLE `table2` (
  `field1` varchar(255) default NULL,
  `field2` varchar(255) default NULL,
  `field3` varchar(255) default NULL,
  `field4` varchar(255) default NULL,
  `field5` varchar(255) default NULL,
  PRIMARY KEY  (`field1`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `table2`
--

INSERT INTO `table2` (`field1`, `field2`, `field3`, `field4`, `field5`) VALUES
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5');

INSERT INTO `table2` (`field1`, `field2`, `field3`, `field4`, `field5`) VALUES
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5');


EOS;
	}

		private function getExpectedPluginDBDump()
	{
return <<<EOS
-- WordPress Backup to Dropbox SQL Dump
-- Version 99
-- http://wpb2d.com
-- Generation Time: March 12, 2012 at 00:00

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Create and use the backed up database
--

CREATE DATABASE IF NOT EXISTS TestDB;
USE TestDB;

--
-- Table structure for table `table3`
--

CREATE TABLE `table3` (
  `field1` varchar(255) default NULL,
  `field2` varchar(255) default NULL,
  `field3` varchar(255) default NULL,
  `field4` varchar(255) default NULL,
  `field5` varchar(255) default NULL,
  PRIMARY KEY  (`field1`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `table3`
--

INSERT INTO `table3` (`field1`, `field2`, `field3`, `field4`, `field5`) VALUES
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5');

--
-- Table structure for table `table4`
--

CREATE TABLE `table4` (
  `field1` varchar(255) default NULL,
  `field2` varchar(255) default NULL,
  `field3` varchar(255) default NULL,
  `field4` varchar(255) default NULL,
  `field5` varchar(255) default NULL,
  PRIMARY KEY  (`field1`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `table4`
--

INSERT INTO `table4` (`field1`, `field2`, `field3`, `field4`, `field5`) VALUES
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5'),
('value1', 'value2', 'value3', 'value4', 'value5');


EOS;
	}
}