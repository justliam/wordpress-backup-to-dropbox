<?php
/**
 * @copyright Copyright (C) 2011-2015 Awesoft Pty. Ltd. All rights reserved.
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
require_once 'MockWordPressFunctions.php';

class WPB2D_DatabaseBackupTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();

        @rmdir(__DIR__ . '/BackupTest/');
    }

    public function setUp()
    {
        reset_globals();
        set_current_time('2012-03-12 00:00:00');

        @mkdir(__DIR__ . '/BackupTest/');

        $files = glob(__DIR__ . '/BackupTest/*');
        foreach ($files as $file) {
            unlink($file);
        }

        WPB2D_Factory::set('config', Mockery::mock('Config')
            ->shouldReceive('get_backup_dir')
            ->andReturn(__DIR__ . '/BackupTest/')
            ->mock()
        );
    }

    private function getTableData()
    {
        $tableData = array();
        for ($i = 0; $i < 10; $i++) {
            $tableData[] = array(
                'field1' => 'value1',
                'field2' => 'value2',
                'field3' => 'value3',
                'field4' => 0,
            );
        }

        return $tableData;
    }

    public function testExecute()
    {
        $db = Mockery::mock('wpdb')

            ->shouldReceive('query')
            ->twice()

            ->shouldReceive('get_results')
            ->with('SHOW TABLES', ARRAY_N)
            ->andReturn(array(
                array('table1'),
                array('table2'),
            ))
            ->once()

            ->shouldReceive('get_results')
            ->with('SHOW TABLES', ARRAY_N)
            ->andReturn(array(
                array('table1'),
                array('table2'),
                array('table3'),
            ))
            ->once()


            ->shouldReceive('get_row')
            ->with('SHOW CREATE TABLE table1', ARRAY_N)
            ->andReturn(array(1, $this->getCreateTable('table1')))
            ->once()

            ->shouldReceive('get_row')
            ->with('SHOW CREATE TABLE table2', ARRAY_N)
            ->andReturn(array(1, $this->getCreateTable('table2')))
            ->once()

            ->shouldReceive('get_row')
            ->with('SHOW CREATE TABLE table3', ARRAY_N)
            ->andReturn(array(1, $this->getCreateTable('table3')))
            ->once()

            ->shouldReceive('get_var')
            ->with('SELECT COUNT(*) FROM table1')
            ->andReturn(0)
            ->once()

            ->shouldReceive('get_var')
            ->with('SELECT COUNT(*) FROM table2')
            ->andReturn(20)
            ->twice()

            ->shouldReceive('get_results')
            ->with('SELECT * FROM table2 LIMIT 10 OFFSET 0', ARRAY_A)
            ->andReturn($this->getTableData())
            ->once()

            ->shouldReceive('get_results')
            ->with('SELECT * FROM table2 LIMIT 10 OFFSET 10', ARRAY_A)
            ->andReturn($this->getTableData())
            ->twice()

            ->shouldReceive('get_var')
            ->with('SELECT COUNT(*) FROM table3')
            ->andReturn(10)
            ->once()

            ->shouldReceive('get_results')
            ->with('SELECT * FROM table3 LIMIT 10 OFFSET 0', ARRAY_A)
            ->andReturn($this->getTableData())
            ->once()

            ->mock();

        $db->prefix = 'wp_';

        WPB2D_Factory::set('db', $db);

        $table = new stdClass;
        $table->count = 0;

        $tableOne = new stdClass;
        $tableOne->count = 1;

        $processed = Mockery::mock('Processed_DBTables')
            ->shouldReceive('update_table')

            ->shouldReceive('get_table')
            ->andReturn($table)

            ->shouldReceive('is_complete')
            ->andReturn(false)

            ->mock()
            ;

        $backup = new WPB2D_DatabaseBackup($processed);
        $backup->execute();

        $this->assertEquals($this->getExpectedOutputOne(), file_get_contents($backup->get_file()));

        $processed = Mockery::mock('Processed_DBTables')
            ->shouldReceive('update_table')

            ->shouldReceive('is_complete')
            ->with('header')
            ->andReturn(true)
            ->once()

            ->shouldReceive('is_complete')
            ->with('table1')
            ->andReturn(true)
            ->once()

            ->shouldReceive('is_complete')
            ->with('table2')
            ->andReturn(false)
            ->once()

            ->shouldReceive('is_complete')
            ->with('table3')
            ->andReturn(false)
            ->once()

            ->shouldReceive('get_table')
            ->with('table2')
            ->andReturn($tableOne)
            ->once()

            ->shouldReceive('get_table')
            ->with('table3')
            ->andReturn($table)
            ->once()

            ->mock()
            ;

        $backup = new WPB2D_DatabaseBackup($processed);
        $backup->execute();

        $this->assertEquals($this->getExpectedOutputOne() . $this->getExpectedOutputTwo(), file_get_contents($backup->get_file()));

        $backup->clean_up();
    }


    private function getCreateTable($table)
    {
return "CREATE TABLE `$table` (\n" . <<<EOS
  `field1` varchar(255) default NULL,
  `field2` varchar(255) default NULL,
  `field3` varchar(255) default NULL,
  `field4` int default 0,
  PRIMARY KEY  (`field1`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
EOS;
    }

    private function getExpectedOutputOne()
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
  `field4` int default 0,
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
  `field4` int default 0,
  PRIMARY KEY  (`field1`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `table2`
--

INSERT INTO `table2` (`field1`, `field2`, `field3`, `field4`) VALUES
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0);

INSERT INTO `table2` (`field1`, `field2`, `field3`, `field4`) VALUES
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0);


EOS;
    }

    private function getExpectedOutputTwo()
    {
return <<<EOS
INSERT INTO `table2` (`field1`, `field2`, `field3`, `field4`) VALUES
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0);

--
-- Table structure for table `table3`
--

CREATE TABLE `table3` (
  `field1` varchar(255) default NULL,
  `field2` varchar(255) default NULL,
  `field3` varchar(255) default NULL,
  `field4` int default 0,
  PRIMARY KEY  (`field1`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `table3`
--

INSERT INTO `table3` (`field1`, `field2`, `field3`, `field4`) VALUES
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0),
('value1', 'value2', 'value3', 0);


EOS;
    }
}
