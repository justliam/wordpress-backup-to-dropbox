<?php
/**
 * A test for the WPB2D_FileList class
 *
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

class WPB2D_FileList_Test extends PHPUnit_Framework_TestCase
{
    private $list;

    public function setUp()
    {
        reset_globals();

        $db = Mockery::mock()
            ->shouldReceive('get_results')
            ->andReturn(array())
            ->shouldReceive('insert')
            ->shouldReceive('prepare')
            ->shouldReceive('query')
            ->mock()
            ;

        $db->prefix = 'wp_';

        WPB2D_Factory::set('db', $db);

        $this->list = new WPB2D_FileList();
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testDatabaseInteractions()
    {
        $db = Mockery::mock()
            ->shouldReceive('get_results')
            ->andReturn(array())

            ->shouldReceive('insert')
            ->with('wp_wpb2d_excluded_files', array(
                'file' => __FILE__,
                'isdir' => false
            ))
            ->mock()
            ;

        $db->prefix = 'wp_';

        WPB2D_Factory::set('db', $db);

        $list = new WPB2D_FileList();
        $list->set_excluded(__FILE__);

        $this->assertTrue($list->is_excluded(__FILE__));

        $file = new stdClass;
        $file->file = __FILE__;

        $db = Mockery::mock()
            ->shouldReceive('get_results')
            ->andReturn(array($file))
            ->shouldReceive('prepare')
            ->with('DELETE FROM wp_wpb2d_excluded_files WHERE file =  %s', __FILE__)
            ->once()

            ->shouldReceive('query')
            ->once()

            ->mock()
            ;

        $db->prefix = 'wp_';

        WPB2D_Factory::set('db', $db);

        $list = new WPB2D_FileList();
        $this->assertTrue($list->is_excluded(__FILE__));

        $list->set_included(__FILE__);
        $this->assertFalse($list->is_excluded(__FILE__));
    }

    public function testExcludedIncludeFile()
    {
        $this->list->set_excluded(__FILE__);
        $this->list->set_excluded(__FILE__);

        $this->assertTrue($this->list->is_excluded(__FILE__));

        $this->list->set_included(__FILE__);
        $this->assertFalse($this->list->is_excluded(__FILE__));
    }

    public function testExcludedIncludeDir()
    {
        $this->list->set_excluded(__DIR__);
        $this->list->set_excluded(__DIR__);

        $this->assertTrue($this->list->is_excluded(__DIR__));

        $this->list->set_included(__DIR__);
        $this->assertFalse($this->list->is_excluded(__DIR__));
    }

    public function testSetGetExcludedDir()
    {
        $this->list->set_excluded(__DIR__);
        $this->assertTrue($this->list->is_excluded(__DIR__));

        $this->list->set_included(__DIR__);
        $this->assertFalse($this->list->is_excluded(__DIR__));
    }

    public function testGetExcludedFileWithExcludedParentDir()
    {
        $this->list->set_excluded(__FILE__);
        $this->assertTrue($this->list->is_excluded(__FILE__));

        $this->list->set_included(__FILE__);
        $this->assertFalse($this->list->is_excluded(__FILE__));
    }

    public function testGetExcludedFileWithExcludedRootDir()
    {
        $this->list->set_excluded(ABSPATH);
        $this->assertTrue($this->list->is_excluded(__FILE__));
    }

    public function testGetIncludedDir()
    {
        $this->assertFalse($this->list->is_excluded(__DIR__));
    }

    public function testGetIncludedFile()
    {
        $this->assertFalse($this->list->is_excluded(__FILE__));
    }

    public function testGetCheckBoxClassCheckedFile()
    {
        $this->list->set_excluded(__FILE__);
        $this->assertEquals('checked', $this->list->get_checkbox_class(__FILE__));
    }

    public function testGetCheckBoxClassCheckedDir()
    {
        $this->list->set_excluded(__DIR__);
        $this->assertEquals('checked', $this->list->get_checkbox_class(__DIR__));
    }

    public function testGetCheckBoxClassNotCheckedFile()
    {
        $this->assertEquals('', $this->list->get_checkbox_class(__FILE__));
    }

    public function testGetCheckBoxClassNotCheckedDir()
    {
        $this->assertEquals('', $this->list->get_checkbox_class(__DIR__));
    }

    public function testGetCheckBoxClassPartialDir()
    {
        $this->list->set_excluded(__FILE__);
        $this->assertEquals('partial', $this->list->get_checkbox_class(__DIR__));
    }
}
