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

class WPB2D_Logger_Test extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        $logger = new WPB2D_Logger();

        if (file_exists($logger->get_log_file())) {
            unlink($logger->get_log_file());
        }

        $dir = WPB2D_Factory::get('config')->get_backup_dir();

        unlink($dir . '/index.php');
        rmdir($dir);
    }

    public function testLog()
    {
        $time = strtotime(current_time('mysql'));

        $logger = new WPB2D_Logger();
        $logger->log('Test');

        $this->assertEquals(sprintf("%s: Test\n", date('H:i:s', $time)), file_get_contents($logger->get_log_file()));
    }

    public function testLogWithFile()
    {
        $logger = new WPB2D_Logger();

        $time = strtotime(current_time('mysql'));

        $files = array(array(
            'file' => 'file1.txt',
            'mtime' => 999944449999,
        ));

        $logger->log('Test', $files);

        $expected = <<<EOS
%s: Test
Uploaded Files:[{"file":"file1.txt","mtime":999944449999}]

EOS;

        $this->assertEquals(sprintf($expected, date('H:i:s', $time)), file_get_contents($logger->get_log_file()));
    }

    public function testGetLog()
    {
        $logger = new WPB2D_Logger();

        $time = strtotime(current_time('mysql'));

        $logger->log('One');
        $logger->log('Two');

        $this->assertEquals(array(
                sprintf('%s: One', date('H:i:s', $time)),
                sprintf('%s: Two', date('H:i:s', $time)),
            ),
            $logger->get_log()
        );
    }

    public function testDeleteLog()
    {
        $logger = new WPB2D_Logger();
        $logger->log('Test');
        $logger->delete_log();
        $this->assertFalse(file_exists($logger->get_log_file()));
    }
}
