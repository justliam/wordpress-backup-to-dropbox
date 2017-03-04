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

class WPB2D_ConfigTest extends PHPUnit_Framework_TestCase
{
    private $config;

    public function setUp()
    {
        reset_globals();
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testConstruct()
    {
        $db = Mockery::mock('DB')
            ->shouldReceive('prepare')
            ->with('SELECT value FROM wp_wpb2d_options WHERE name = %s', 'last_backup_time')
            ->once()

            ->shouldReceive('prepare')
            ->with('SELECT value FROM wp_wpb2d_options WHERE name = %s', 'in_progress')
            ->once()

            ->shouldReceive('get_var')
            ->andReturn(false)

            ->mock()
            ;

        $db->prefix = 'wp_';

        WPB2D_Factory::set('db', $db);

        $config = new WPB2D_Config();
        $this->assertEquals(false, $config->get_option('last_backup_time'));
        $this->assertEquals(false, $config->get_option('in_progress'));
    }

    public function testAddBackupHistory()
    {
        $db = Mockery::mock()
            ->shouldReceive('prepare')
            ->shouldReceive('get_var')
            ->andReturn(null)

            ->shouldReceive('insert')
            ->with('wp_wpb2d_options', Mockery::type('array'))
            ->andReturn(true)
            ->times(20)

            ->mock()
            ;

        $db->prefix = 'wp_';

        WPB2D_Factory::set('db', $db);

        $config = new WPB2D_Config();

        for ($i = 0; $i < 30; $i++) {
            set_current_time('2012-03-12 00:00:' . $i);
            $config->log_finished_time();
        }

        $history = $config->get_history();
        $this->assertEquals(20, count($history));
    }

    private function getConfig()
    {
        $db = Mockery::mock();
        $db->prefix = 'wp_';

        WPB2D_Factory::set('db', $db);

        return new WPB2D_Config();
    }

    public function testSetGetScheduleWhereTimeOfDayHasPast()
    {
        $config = $this->getConfig();

        $blog_time = strtotime(date('Y-m-d H:00:00', strtotime('+2 hours')));
        set_current_time(date('Y-m-d H:i:s ', $blog_time));

        $config->set_schedule(date('D', $blog_time), date('H', $blog_time) . ':00', 'daily');
        $schedule = $config->get_schedule();

        //Next week in the blog time time is expected
        $expected_date_time = date('Y-m-d', strtotime('+7 days', $blog_time)) . ' ' . date('H', $blog_time) . ':00:00';
        $this->assertEquals($expected_date_time, date('Y-m-d H:i:s', $schedule[0]));
        $this->assertEquals('daily', $schedule[1]);

        //Next week in the server time is expected
        $schedule = wp_next_scheduled('execute_periodic_drobox_backup');
        $this->assertEquals(strtotime('+7 days', strtotime(date('Y-m-d H:00:00'))), $schedule);
    }

    public function testSetGetScheduleWhereTimeOfDayHasPastAndNoDaySupplied()
    {
        $config = $this->getConfig();

        $blog_time = strtotime(date('Y-m-d H:00:00', strtotime('+2 hours')));
        set_current_time(date('Y-m-d H:i:s ', $blog_time));

        $config->set_schedule(null, date('H') . ':00', 'daily');
        $schedule = $config->get_schedule();

        //Today in the blog time time is expected
        $expected_date_time = date('Y-m-d', strtotime('+1 day')) . ' ' . date('H') . ':00:00';
        $this->assertEquals($expected_date_time, date('Y-m-d H:i:s', $schedule[0]));
        $this->assertEquals('daily', $schedule[1]);

        //Today in the server time is expected
        $schedule = wp_next_scheduled('execute_periodic_drobox_backup');
        $this->assertEquals(strtotime('+1 day',  strtotime(date('Y-m-d H:00:00', strtotime('-2 hours')))), $schedule);
    }

    public function testSetGetScheduleWhereTimeOfDayHasNotPast()
    {
        $config = $this->getConfig();

        $blog_time = strtotime(date('Y-m-d H:00:00', strtotime('+2 hours')));
        set_current_time(date('Y-m-d H:i:s ', $blog_time));

        $hour = date('H', $blog_time) + 1;
        if ($hour < 10) {
            $hour = "0$hour";
        }

        $config->set_schedule(date('D', $blog_time), $hour . ':00', 'weekly');
        $schedule = $config->get_schedule();

        $expected_date_time = date('Y-m-d', $blog_time) . ' ' . $hour . ':00:00';
        $this->assertEquals($expected_date_time, date('Y-m-d H:i:s', $schedule[0]));
        $this->assertEquals('weekly', $schedule[1]);

        $schedule = wp_next_scheduled('execute_periodic_drobox_backup');
        $this->assertEquals(strtotime(date('Y-m-d H:00:00', strtotime('+1 hours'))), $schedule);
    }

    public function testGetDropboxLocation()
    {
        $db = Mockery::mock('DB')
            ->shouldReceive('prepare')
            ->shouldReceive('get_var')
            ->andReturn(null)

            ->shouldReceive('insert')
            ->with('wp_wpb2d_options', array(
                'name' => 'dropbox_location',
                'value' => 'MyDropboxRoot'
            ))
            ->andReturn(false)
            ->once()

            ->shouldReceive('insert')
            ->with('wp_wpb2d_options', array(
                'name' => 'store_in_subfolder',
                'value' => true
            ))
            ->andReturn(true)
            ->once()

            ->mock()
            ;

        $db->prefix = 'wp_';

        WPB2D_Factory::set('db', $db);

        $config = new WPB2D_Config();

        $dropbox_path = $config->get_dropbox_path(__DIR__, __DIR__ . '/Out/file.txt');
        $this->assertEquals('Out', $dropbox_path);

        $config
            ->set_option('dropbox_location', 'MyDropboxRoot')
            ->set_option('store_in_subfolder', true)
            ;

        $dropbox_path = $config->get_dropbox_path(__DIR__, __DIR__ . '/Out/file.txt');
        $this->assertEquals('MyDropboxRoot/Out', $dropbox_path);

        $dropbox_path = $config->get_dropbox_path(__DIR__ . DIRECTORY_SEPARATOR, __DIR__ . '/Out/file.txt');
        $this->assertEquals('MyDropboxRoot/Out', $dropbox_path);

        $dropbox_path = $config->get_dropbox_path(__DIR__ . DIRECTORY_SEPARATOR, __DIR__ . '/Out/file.txt', true);
        $this->assertEquals('MyDropboxRoot', $dropbox_path);
    }

    public function testComplete()
    {
        $db = Mockery::mock()
            ->shouldReceive('get_results')
            ->shouldReceive('prepare')
            ->shouldReceive('get_var')
            ->andReturn(null)

            ->shouldReceive('query')
            ->with("TRUNCATE wp_wpb2d_processed_files")
            ->once()

            ->shouldReceive('query')
            ->with("TRUNCATE wp_wpb2d_processed_dbtables")
            ->once()

            ->shouldReceive('insert')
            ->with('wp_wpb2d_options', array(
                'name' => 'in_progress',
                'value' => false
            ))
            ->andReturn(true)
            ->once()

            ->shouldReceive('insert')
            ->with('wp_wpb2d_options', array(
                'name' => 'is_running',
                'value' => false
            ))
            ->andReturn(true)
            ->once()

            ->shouldReceive('insert')
            ->with('wp_wpb2d_options', Mockery::type('array'))
            ->andReturn(true)
            ->once()

            ->mock()
            ;

        $db->prefix = 'wp_';

        WPB2D_Factory::set('db', $db);

        $config = new WPB2D_Config();

        $config->set_schedule('Monday', '00:00:00', 'daily');

        set_current_time('2012-03-12 00:00:00');

        $config->complete();

        $this->assertFalse(wp_next_scheduled('monitor_dropbox_backup_hook'));
        $this->assertFalse(wp_next_scheduled('run_dropbox_backup_hook'));
    }
}
