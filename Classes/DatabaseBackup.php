<?php
/**
 * A class with functions the perform a backup of the WordPress database
 *
 * @copyright Copyright (C) 2011-2014 Awesoft Pty. Ltd. All rights reserved.
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
class WPB2D_DatabaseBackup
{
    const SELECT_QUERY_LIMIT = 10;
    const WAIT_TIMEOUT = 600; //10 minutes
    const NOT_STARTED = 0;
    const COMPLETE = 1;
    const IN_PROGRESS = 2;

    private
        $temp,
        $database,
        $config
        ;

    public function __construct($processed = null)
    {
        $this->database = WPB2D_Factory::db();
        $this->config = WPB2D_Factory::get('config');
        $this->processed = $processed ? $processed : new WPB2D_Processed_DBTables();
        $this->set_wait_timeout();
    }

    public function get_status()
    {
        if ($this->processed->count_complete() == 0) {
            return self::NOT_STARTED;
        }

        $count = $this->database->get_var(
            $this->database->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s", DB_NAME)
        );

        if ($this->processed->count_complete() < $count) {
            return self::IN_PROGRESS;
        }

        return self::COMPLETE;
    }

    public function execute()
    {
        if (!$this->processed->is_complete('header')) {
            $this->write_db_dump_header();
        }

        $tables = $this->database->get_results('SHOW TABLES', ARRAY_N);

        foreach ($tables as $t) {
            $tableName = $t[0];
            if (!$this->processed->is_complete($tableName)) {
                $table = $this->processed->get_table($tableName);

                $count = 0;
                if ($table) {
                    $count = $table->count * self::SELECT_QUERY_LIMIT;
                }

                if ($count > 0) {
                    WPB2D_Factory::get('logger')->log(sprintf(__("Resuming table '%s' at row %s.", 'wpbtd'), $tableName, $count));
                }

                $this->backup_database_table($tableName, $count);

                WPB2D_Factory::get('logger')->log(sprintf(__("Processed table '%s'.", 'wpbtd'), $tableName));
            }
        }
    }

    public function clean_up()
    {
        if (file_exists($this->get_file())) {
            unlink($this->get_file());
        }
    }

    public function get_file()
    {
        $file = rtrim($this->config->get_backup_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . DB_NAME . "-backup.sql";

        $files = glob($file . '*');
        if (isset($files[0])) {
            return $files[0];
        }

        return $file . '.' . WPB2D_Factory::secret(DB_NAME);
    }

    private function set_wait_timeout()
    {
        $this->database->query("SET SESSION wait_timeout=" . self::WAIT_TIMEOUT);
    }

    private function write_db_dump_header()
    {
        $dump_location = $this->config->get_backup_dir();

        if (!is_writable($dump_location)) {
            $msg = sprintf(__("A database backup cannot be created because WordPress does not have write access to '%s', please ensure this directory has write access.", 'wpbtd'), $dump_location);
            WPB2D_Factory::get('logger')->log($msg);

            return false;
        }

        $blog_time = strtotime(current_time('mysql'));

        $this->write_to_temp("-- WordPress Backup to Dropbox SQL Dump\n");
        $this->write_to_temp("-- Version " . BACKUP_TO_DROPBOX_VERSION . "\n");
        $this->write_to_temp("-- http://wpb2d.com\n");
        $this->write_to_temp("-- Generation Time: " . date("F j, Y", $blog_time) . " at " . date("H:i", $blog_time) . "\n\n");
        $this->write_to_temp('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";' . "\n\n");

        //I got this out of the phpMyAdmin database dump to make sure charset is correct
        $this->write_to_temp("/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n");
        $this->write_to_temp("/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n");
        $this->write_to_temp("/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n");
        $this->write_to_temp("/*!40101 SET NAMES utf8 */;\n\n");

        $this->write_to_temp("--\n-- Create and use the backed up database\n--\n\n");
        $this->write_to_temp("CREATE DATABASE IF NOT EXISTS " . DB_NAME . ";\n");
        $this->write_to_temp("USE " . DB_NAME . ";\n\n");

        $this->persist();

        $this->processed->update_table('header', -1);
    }

    private function backup_database_table($table, $offset)
    {
        $db_error = __('Error while accessing database.', 'wpbtd');

        if ($offset == 0) {
            $this->write_to_temp("--\n-- Table structure for table `$table`\n--\n\n");

            $table_create = $this->database->get_row("SHOW CREATE TABLE $table", ARRAY_N);
            if ($table_create === false) {
                throw new Exception($db_error . ' (ERROR_3)');
            }
            $this->write_to_temp($table_create[1] . ";\n\n");
        }

        $row_count = 0;
        $table_count = $this->database->get_var("SELECT COUNT(*) FROM $table");
        if ($table_count == 0) {
            $this->write_to_temp("--\n-- Table `$table` is empty\n--\n\n");
            $this->persist();
        } else {
            if ($offset == 0) {
                $this->write_to_temp("--\n-- Dumping data for table `$table`\n--\n\n");
                $this->persist();
            }

            for ($i = $offset; $i < $table_count; $i = $i + self::SELECT_QUERY_LIMIT) {

                $table_data = $this->database->get_results("SELECT * FROM $table LIMIT " . self::SELECT_QUERY_LIMIT . " OFFSET $i", ARRAY_A);
                if ($table_data === false) {
                    throw new Exception($db_error . ' (ERROR_4)');
                }

                $fields = '`' . implode('`, `', array_keys($table_data[0])) . '`';
                $this->write_to_temp("INSERT INTO `$table` ($fields) VALUES\n");

                $out = '';
                foreach ($table_data as $data) {
                    $data_out = '(';
                    foreach ($data as $value) {
                        if (is_numeric($value)) {
                            $data_out .= "$value, ";
                        } else {
                            $value = addslashes($value);
                            $value = str_replace("\n", "\\n", $value);
                            $value = str_replace("\r", "\\r", $value);
                            $data_out .= "'$value', ";
                        }
                    }
                    $out .= rtrim($data_out, ' ,') . "),\n";
                    $row_count++;
                }
                $this->write_to_temp(rtrim($out, ",\n") . ";\n\n");

                if ($row_count >= $table_count) {
                    $this->processed->update_table($table, -1); //Done
                } else {
                    $this->processed->update_table($table, $row_count);
                }

                $this->persist();
            }
        }
    }

    private function write_to_temp($out)
    {
        if (!$this->temp) {
            $this->temp = fopen('php://memory', 'rw');
        }

        if (fwrite($this->temp, $out) === false) {
            throw new Exception(__('Error writing to php://memory.', 'wpbtd'));
        }
    }

    private function persist()
    {
        $fh = fopen($this->get_file(), 'a');

        fseek($this->temp, 0);

        fwrite($fh, stream_get_contents($this->temp));

        if (!fclose($fh)) {
            throw new Exception(__('Error closing sql dump file.', 'wpbtd'));
        }

        if (!fclose($this->temp)) {
            throw new Exception(__('Error closing php://memory.', 'wpbtd'));
        }

        $this->temp = null;
    }
}
