<?php
/**
 * A test for the WPB2D_FileList class
 *
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
require_once 'MockWordPressFunctions.php';

class WPB2D_FactoryTest extends PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        global $wpdb;

        $wpdb = Mockery::mock('DB')
            ->shouldReceive('hide_errors')
            ->once()
            ->mock()
            ;

        $this->assertInstanceOf('WPB2D_Logger', WPB2D_Factory::get('logger'));
        $this->assertInstanceOf('WPB2D_Config', WPB2D_Factory::get('config'));
        $this->assertInstanceOf(get_class($wpdb), WPB2D_Factory::db());

        $this->assertNull(WPB2D_Factory::get('IAmNull'));
    }

    public function testSet()
    {
        WPB2D_Factory::set('config', $this);
        WPB2D_Factory::set('db', $this);

        $this->assertInstanceOf(get_class($this), WPB2D_Factory::get('config'));
        $this->assertInstanceOf(get_class($this), WPB2D_Factory::db());
    }

    public function testSecret()
    {
        $this->assertNotNull(WPB2D_Factory::secret('Bob'));
    }
}
