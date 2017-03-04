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

class WPB2D_Extension_Manager_Test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        reset_globals();

        WPB2D_Factory::set('dropbox', Mockery::mock('Dropbox'));
    }

    public function tearDown()
    {
        Mockery::close();

        if (file_exists(EXTENSIONS_DIR . 'TestExtension.php')) {
            unlink(EXTENSIONS_DIR . 'TestExtension.php');
        }
    }

    public function testConstruct()
    {
        $mgr = new WPB2D_Extension_Manager();

        $this->assertFalse($mgr->is_installed('Test Extension'));
    }

    public function testInstallGetInstalled()
    {
        set_option('wpb2d-premium-extensions', array(
            'Test Extension' => 'TestExtension.php'
        ));

        $mgr = new WPB2D_Extension_Manager();
        $mgr->install('Test extension', 'TestExtension.php');

        $this->assertNotNull($mgr->is_installed('Test Extension'));
    }

    public function testInitAndCallbacks()
    {
        set_option('wpb2d-premium-extensions', array(
            'Test Extension' => 'TestExtension.php'
        ));

        $mgr = new WPB2D_Extension_Manager();

        $mgr->install('Test extension', 'TestExtension.php');

        $this->assertTrue(class_exists('WPB2D_Extension_TestExtension'));
        $this->assertTrue(WPB2D_Extension_TestExtension::complete());
        $this->assertTrue(WPB2D_Extension_TestExtension::failure());
        $this->assertTrue(WPB2D_Extension_TestExtension::get_menu());
        $this->assertEquals(WPB2D_Extension_TestExtension::TYPE_OUTPUT, WPB2D_Extension_TestExtension::get_type());
        $this->assertTrue(WPB2D_Extension_TestExtension::is_enabled());
        $this->assertTrue(WPB2D_Extension_TestExtension::set_enabled(true));
        $this->assertInstanceOf('WPB2D_Extension_TestExtension', $mgr->get_output());

        $mgr->add_menu_items();
        $this->assertEquals('get_menu', WPB2D_Extension_TestExtension::$lastCalled);

        $mgr->complete();
        $this->assertEquals('complete', WPB2D_Extension_TestExtension::$lastCalled);

        $mgr->failure();
        $this->assertEquals('failure', WPB2D_Extension_TestExtension::$lastCalled);
    }
}
