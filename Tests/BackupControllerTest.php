<?php
/**
 * A test for the WPB2D_BackupController class
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

class WPB2D_BackupControllerTest extends PHPUnit_Framework_TestCase
{
    private $backup;
    private $dropbox;
    private $output;
    private $config;

    public function tearDown()
    {
        Mockery::close();
    }

    public function setUp()
    {
        WPB2D_Factory::set('fileList', Mockery::mock('FileList')
            ->shouldReceive('is_excluded')
            ->andReturn(false)

            ->shouldReceive('in_ignore_list')
            ->andReturn(false)

            ->mock()
        );

        WPB2D_Factory::set('processed-files', Mockery::mock('ProcessedFiles')
            ->shouldReceive('get_file')
            ->andReturn(false)

            ->shouldReceive('get_file_count')
            ->andReturn(0)

            ->mock()
        );

        WPB2D_Factory::set('logger', Mockery::mock('Logger')
            ->shouldReceive('log')
            ->mock()
        );

        WPB2D_Factory::set('dropbox', Mockery::mock('Dropbox'));
        WPB2D_Factory::set('databaseBackup', Mockery::mock('DatabaseBackup'));

        $db = Mockery::mock('DB')
            ->shouldReceive('get_results')
            ->andReturn(array())

            ->mock()
            ;

        $db->prefix = 'wp_';

        WPB2D_Factory::set('db', $db);
    }

    public function testBackupPath()
    {
        WPB2D_Factory::set('config', Mockery::mock('Config')
            ->shouldReceive('get_backup_dir')
            ->andReturn(__DIR__ . '/BackupTest/')
            ->mock()

            ->shouldReceive('get_option')
            ->with('in_progress')
            ->andReturn(true)
            ->once()

            ->shouldReceive('get_option')
            ->with('last_backup_time')
            ->never()

            ->shouldReceive('get_option')
            ->with('total_file_count')
            ->andReturn(1800)
            ->once()

            ->mock()
        );

        $output = Mockery::mock('Output')
            ->shouldReceive('out')
            ->with(
                'DropboxPath',
                Mockery::anyOf(
                    __DIR__ . '/BackupControllerTest.php',
                    __DIR__ . '/ConfigTest.php',
                    __DIR__ . '/DatabaseTest.php',
                    __DIR__ . '/DefaultOutputTest.php',
                    __DIR__ . '/ExtensionManagerTest.php',
                    __DIR__ . '/FactoryTest.php',
                    __DIR__ . '/FileListTest.php',
                    __DIR__ . '/LoggerTest.php',
                    __DIR__ . '/MockWordPressFunctions.php',
                    __DIR__ . '/ProcessedDBTablesTest.php',
                    __DIR__ . '/ProcessedFilesTest.php',
                    __DIR__ . '/phpunit',
                    __DIR__ . '/phpunit.xml'
                ),
                false
            )
            ->times(13)

            ->mock()
            ;

        $backup = new WPB2D_BackupController($output);
        $backup->backup_path(__DIR__, 'DropboxPath');
    }

    public function testBackupPathWithExcludedFiles()
    {
        WPB2D_Factory::set('fileList', Mockery::mock('FileList')
            ->shouldReceive('is_excluded')
            ->with(Mockery::anyOf(
                    __DIR__ . '/ExtensionManagerTest.php',
                    __DIR__ . '/FactoryTest.php',
                    __DIR__ . '/FileListTest.php',
                    __DIR__ . '/LoggerTest.php',
                    __DIR__ . '/ProcessedDBTablesTest.php',
                    __DIR__ . '/ProcessedFilesTest.php',
                    __DIR__ . '/phpunit',
                    __DIR__ . '/phpunit.xml'
            ))
            ->andReturn(false)

            ->shouldReceive('is_excluded')
            ->with(Mockery::anyOf(
                    __DIR__ . '/BackupControllerTest.php',
                    __DIR__ . '/ConfigTest.php',
                    __DIR__ . '/DatabaseTest.php',
                    __DIR__ . '/DefaultOutputTest.php',
                    __DIR__ . '/MockWordPressFunctions.php'
            ))
            ->andReturn(true)

            ->shouldReceive('in_ignore_list')
            ->andReturn(false)

            ->mock()
        );


        WPB2D_Factory::set('config', Mockery::mock('Config')
            ->shouldReceive('get_backup_dir')
            ->andReturn(__DIR__ . '/BackupTest/')
            ->mock()

            ->shouldReceive('get_option')
            ->with('in_progress')
            ->andReturn(true)
            ->once()

            ->shouldReceive('get_option')
            ->with('last_backup_time')
            ->never()

            ->shouldReceive('get_option')
            ->with('total_file_count')
            ->andReturn(1800)
            ->once()

            ->mock()
        );

        $output = Mockery::mock('Output')
            ->shouldReceive('out')
            ->with(
                'DropboxPath',
                Mockery::anyOf(
                    __DIR__ . '/ExtensionManagerTest.php',
                    __DIR__ . '/FactoryTest.php',
                    __DIR__ . '/FileListTest.php',
                    __DIR__ . '/LoggerTest.php',
                    __DIR__ . '/MockWordPressFunctions.php',
                    __DIR__ . '/ProcessedDBTablesTest.php',
                    __DIR__ . '/ProcessedFilesTest.php',
                    __DIR__ . '/phpunit',
                    __DIR__ . '/phpunit.xml'
                ),
                false
            )
            ->times(8)

            ->mock()
            ;

        $backup = new WPB2D_BackupController($output);
        $backup->backup_path(__DIR__, 'DropboxPath');
    }

    public function testBackupPathWithProcessedFile()
    {
        $output = Mockery::mock('Output')
            ->shouldReceive('out')
            ->with(
                'DropboxPath',
                Mockery::anyOf(
                    __DIR__ . '/ConfigTest.php',
                    __DIR__ . '/DatabaseTest.php',
                    __DIR__ . '/DefaultOutputTest.php',
                    __DIR__ . '/ExtensionManagerTest.php',
                    __DIR__ . '/FactoryTest.php',
                    __DIR__ . '/FileListTest.php',
                    __DIR__ . '/LoggerTest.php',
                    __DIR__ . '/MockWordPressFunctions.php',
                    __DIR__ . '/ProcessedDBTablesTest.php',
                    __DIR__ . '/ProcessedFilesTest.php',
                    __DIR__ . '/phpunit',
                    __DIR__ . '/phpunit.xml'
                ),
                false
            )
            ->times(12)
            ->mock()
            ;

        $file = new stdClass;
        $file->offset = 0;

        WPB2D_Factory::set('processed-files', Mockery::mock('ProcessedFiles')
            ->shouldReceive('get_file')
            ->with(__DIR__ . '/BackupControllerTest.php')
            ->andReturn($file)
            ->once()

            ->shouldReceive('get_file')
            ->with(Mockery::anyOf(
                __DIR__ . '/ConfigTest.php',
                __DIR__ . '/DatabaseTest.php',
                __DIR__ . '/DefaultOutputTest.php',
                __DIR__ . '/ExtensionManagerTest.php',
                __DIR__ . '/FactoryTest.php',
                __DIR__ . '/FileListTest.php',
                __DIR__ . '/LoggerTest.php',
                __DIR__ . '/MockWordPressFunctions.php',
                __DIR__ . '/ProcessedDBTablesTest.php',
                __DIR__ . '/ProcessedFilesTest.php',
                __DIR__ . '/phpunit',
                __DIR__ . '/phpunit.xml'
            ))
            ->andReturn(false)
            ->times(12)

            ->shouldReceive('get_file_count')
            ->andReturn(0)

            ->mock()
        );

        $backup = new WPB2D_BackupController($output);
        $backup->backup_path(__DIR__, 'DropboxPath');
    }

    public function testExecuteNotAuthorized()
    {
        WPB2D_Factory::set('dropbox', Mockery::mock('Dropbox')
            ->shouldReceive('is_authorized')
            ->andReturn(false)
            ->mock()
        );

        WPB2D_Factory::set('config', Mockery::mock('Config')
            ->shouldReceive('set_time_limit')
            ->shouldReceive('set_memory_limit')
            ->shouldReceive('get_backup_dir')
            ->andReturn(__DIR__ . '/Backups')
            ->mock()
        );

        WPB2D_Factory::set('logger', Mockery::mock('Logger')
            ->shouldReceive('log')
            ->with('Your Dropbox account is not authorized yet.')
            ->once()
            ->mock()
        );

        $backup = new WPB2D_BackupController();
        $backup->execute();
    }

    public function testBackupNow()
    {
        $backup = new WPB2D_BackupController();
        $backup->backup_now();

        $this->assertEquals(time(), wp_next_scheduled('execute_instant_drobox_backup'));
    }

    public function testStop()
    {
        WPB2D_Factory::set('config', Mockery::mock('Config')
            ->shouldReceive('complete')
            ->once()
            ->mock()
        );

        WPB2D_Factory::set('databaseBackup', Mockery::mock('DatabaseBackup')
            ->shouldReceive('clean_up')
            ->once()
            ->mock()
        );

        $backup = new WPB2D_BackupController();
        $backup->stop();
    }

    public function testCreateDumpDir() {
        $dir = __DIR__ . '/Backups';

        WPB2D_Factory::set('config', Mockery::mock('Config')
            ->shouldReceive('get_backup_dir')
            ->andReturn($dir)
            ->twice()
            ->mock()
        );

        $backup = new WPB2D_BackupController();
        $backup->create_dump_dir();

        $this->assertTrue(file_exists($dir));
        $this->assertTrue(file_exists($dir . '/index.php'));

        unlink($dir . '/index.php');
        rmdir($dir);
    }
}
