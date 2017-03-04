<?php
/**
 * @copyright Copyright (C) 2011-2013 Michael De Wild. All rights reserved.
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

class WPB2D_Processed_FilesTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        reset_globals();
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testAddFiles()
    {
        $file_three = new stdClass;
        $file_three->file = 'file3.txt';
        $file_three->offset = 1234;
        $file_three->uploadid = 'ABC123';

        $db = Mockery::mock('DB')
            ->shouldReceive('get_results')
            ->with("SELECT * FROM wp_wpb2d_processed_files")
            ->andReturn(array($file_three))
            ->once()

            ->shouldReceive('prepare')
            ->with("SELECT * FROM wp_wpb2d_processed_files WHERE file = %s", "file1.txt")
            ->once()

            ->shouldReceive('insert')
            ->with("wp_wpb2d_processed_files", array(
                'file' => 'file1.txt',
                'uploadid' => null,
                'offset' => null,
            ))
            ->once()

            ->shouldReceive('prepare')
            ->with("SELECT * FROM wp_wpb2d_processed_files WHERE file = %s", "file2.txt")
            ->once()

            ->shouldReceive('insert')
            ->with("wp_wpb2d_processed_files", array(
                'file' => 'file2.txt',
                'uploadid' => null,
                'offset' => null,
            ))
            ->once()

            ->shouldReceive('get_var')
            ->andReturn(null)

            ->mock()
            ;

        $db->prefix = 'wp_';

        WPB2D_Factory::set('db', $db);

        $p = new WPB2D_Processed_Files();
        $p->add_files(array(
            'file1.txt',
            'file2.txt',
        ));

        $this->assertEquals(3, $p->get_file_count());

        $file_obj = $p->get_file('file2.txt');

        $this->assertEquals('file2.txt', $file_obj->file);

        $this->assertNull($file_obj->offset);
        $this->assertNull($file_obj->uploadid);

        $file_obj = $p->get_file('file3.txt');

        $this->assertEquals($file_three, $file_obj);
    }

    public function testUpdateFile()
    {
        $file = new stdClass;
        $file->file = 'file.txt';
        $file->offset = 1234;
        $file->uploadid = 'ABC123';

        $db = Mockery::mock('DB')
            ->shouldReceive('get_results')
            ->with("SELECT * FROM wp_wpb2d_processed_files")
            ->andReturn(array($file))
            ->once()

            ->shouldReceive('prepare')
            ->with("SELECT * FROM wp_wpb2d_processed_files WHERE file = %s", "file.txt")
            ->once()

            ->shouldReceive('get_var')
            ->andReturn(true)

            ->shouldReceive('update')

            ->mock()
            ;

        $db->prefix = 'wp_';

        WPB2D_Factory::set('db', $db);

        $p = new WPB2D_Processed_Files();

        $this->assertEquals(1, $p->get_file_count());

        $this->assertNull($p->get_file('file-not-found.txt'));

        $file = $p->get_file('file.txt');

        $this->assertEquals('file.txt', $file->file);
        $this->assertEquals(1234, $file->offset);
        $this->assertEquals('ABC123', $file->uploadid);

        $p->update_file('file.txt', '123ABC', 4321);

        $file = $p->get_file('file.txt');

        $this->assertEquals('file.txt', $file->file);
        $this->assertEquals(4321, $file->offset);
        $this->assertEquals('123ABC', $file->uploadid);
    }
}
