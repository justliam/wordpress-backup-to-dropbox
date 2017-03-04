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

class WPB2D_Processed_TablesTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        reset_globals();
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testUpdateTableNew()
    {
        $table_two = new stdClass;
        $table_two->name = 'table_two';
        $table_two->count = -1;

        $db = Mockery::mock('DB')
            ->shouldReceive('get_results')
            ->with("SELECT * FROM wp_wpb2d_processed_dbtables")
            ->andReturn(array($table_two))
            ->once()

            ->shouldReceive('prepare')
            ->with("SELECT * FROM wp_wpb2d_processed_dbtables WHERE name = %s", "table_one")
            ->once()

            ->shouldReceive('insert')
            ->with("wp_wpb2d_processed_dbtables", array(
                'name' => 'table_one',
                'count' => -1,
            ))
            ->once()

            ->shouldReceive('get_var')
            ->andReturn(null)

            ->mock()
            ;

        $db->prefix = 'wp_';

        WPB2D_Factory::set('db', $db);

        $p = new WPB2D_Processed_DBTables();

        $this->assertFalse($p->is_complete('table_one'));
        $this->assertTrue($p->is_complete('table_two'));

        $p->update_table('table_one', -1);

        $this->assertTrue($p->is_complete('table_one'));
        $this->assertTrue($p->is_complete('table_two'));
    }

    public function testUpdateTableUpdate()
    {
        $table_two = new stdClass;
        $table_two->name = 'table_two';
        $table_two->count = 4;

        $db = Mockery::mock('DB')
            ->shouldReceive('get_results')
            ->with("SELECT * FROM wp_wpb2d_processed_dbtables")
            ->andReturn(array($table_two))
            ->once()

            ->shouldReceive('prepare')
            ->with("SELECT * FROM wp_wpb2d_processed_dbtables WHERE name = %s", "table_two")
            ->once()

            ->shouldReceive('update')
            ->with(
                "wp_wpb2d_processed_dbtables",
                array(
                    'name' => 'table_two',
                    'count' => -1
                ),
                array('name' => 'table_two')
            )
            ->once()

            ->shouldReceive('get_var')
            ->andReturn(true)

            ->mock()
            ;

        $db->prefix = 'wp_';

        WPB2D_Factory::set('db', $db);

        $p = new WPB2D_Processed_DBTables();

        $this->assertFalse($p->is_complete('table_two'));
        $this->assertEquals(4, $p->get_table('table_two')->count);

        $p->update_table('table_two', -1);

        $this->assertTrue($p->is_complete('table_two'));
        $this->assertEquals(-1, $p->get_table('table_two')->count);
    }
}
