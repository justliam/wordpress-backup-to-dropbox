<?php
/**
 * @copyright Copyright (C) 2011-2013 Michael De Wildt. All rights reserved.
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

class WP_Backup_Logger_Test extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        @unlink(WP_Backup_Logger::get_log_file());

        $dir = WP_Backup_Registry::config()->get_backup_dir();

        unlink($dir . 'index.php');
        rmdir(WP_Backup_Registry::config()->get_backup_dir());
    }

    public function testLog()
    {
        $time = strtotime(current_time('mysql'));

        WP_Backup_Logger::log('Test');

        $this->assertEquals(sprintf("%s: Test\n", date('H:i:s', $time)), file_get_contents(WP_Backup_Logger::get_log_file()));
    }

    public function testLogWithFile()
    {
        $time = strtotime(current_time('mysql'));

        $files = array(array(
            'file' => 'file1.txt',
            'mtime' => 999944449999,
        ));

        WP_Backup_Logger::log('Test', $files);

        $expected = <<<EOS
%s: Test
Uploaded Files:[{"file":"file1.txt","mtime":999944449999}]

EOS;

        $this->assertEquals(sprintf($expected, date('H:i:s', $time)), file_get_contents(WP_Backup_Logger::get_log_file()));
    }

    public function testGetLog()
    {
        $time = strtotime(current_time('mysql'));

        WP_Backup_Logger::log('One');
        WP_Backup_Logger::log('Two');

        $this->assertEquals(array(
                sprintf('%s: One', date('H:i:s', $time)),
                sprintf('%s: Two', date('H:i:s', $time)),
            ),
            WP_Backup_Logger::get_log()
        );
    }

    public function testDeleteLog()
    {
        WP_Backup_Logger::log('Test');
        WP_Backup_Logger::delete_log();
        $this->assertFalse(file_exists(WP_Backup_Logger::get_log_file()));
    }
}
