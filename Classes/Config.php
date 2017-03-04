<?php
/**
 * A class with functions the perform a backup of WordPress
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
class WPB2D_Config
{
    const MAX_HISTORY_ITEMS = 20;

    private
        $db,
        $options
        ;

    public function __construct()
    {
        $this->db = WPB2D_Factory::db();
    }

    public static function get_backup_dir()
    {
        return str_replace('/', DIRECTORY_SEPARATOR, WP_CONTENT_DIR . '/backups');
    }

    public function set_option($name, $value)
    {
        //Short circut if not changed
        if ($this->get_option($name) === $value) {
            return $this;
        }

        $exists = $this->db->get_var(
            $this->db->prepare("SELECT * FROM {$this->db->prefix}wpb2d_options WHERE name = %s", $name)
        );

        if (is_null($exists)) {
            $this->db->insert($this->db->prefix . "wpb2d_options", array(
                'name' => $name,
                'value' => $value,
            ));
        } else {
            $this->db->update(
                $this->db->prefix . 'wpb2d_options',
                array('value' => $value),
                array('name' => $name)
            );
        }

        $this->options[$name] = $value;

        return $this;
    }

    public function get_option($name, $no_cache = false)
    {
        if (!isset($this->options[$name]) || $no_cache) {
            $this->options[$name] = $this->db->get_var(
                $this->db->prepare("SELECT value FROM {$this->db->prefix}wpb2d_options WHERE name = %s", $name)
            );
        }

        return $this->options[$name];
    }

    public static function set_time_limit()
    {
        @set_time_limit(0);
    }

    public static function set_memory_limit()
    {
        @ini_set('memory_limit', WP_MAX_MEMORY_LIMIT);
    }

    public function is_scheduled()
    {
        return wp_get_schedule('execute_instant_drobox_backup') !== false;
    }

    public function set_schedule($day, $time, $frequency)
    {
        $blog_time = strtotime(date('Y-m-d H', strtotime(current_time('mysql'))) . ':00:00');

        //Grab the date in the blogs timezone
        $date = date('Y-m-d', $blog_time);

        //Check if we need to schedule the backup in the future
        $time_arr = explode(':', $time);
        $current_day = date('D', $blog_time);
        if ($day && ($current_day != $day)) {
            $date = date('Y-m-d', strtotime("next $day"));
        } elseif ((int) $time_arr[0] <= (int) date('H', $blog_time)) {
            if ($day) {
                $date = date('Y-m-d', strtotime("+7 days", $blog_time));
            } else {
                $date = date('Y-m-d', strtotime("+1 day", $blog_time));
            }
        }

        $timestamp = wp_next_scheduled('execute_periodic_drobox_backup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'execute_periodic_drobox_backup');
        }

        //This will be in the blogs timezone
        $scheduled_time = strtotime($date . ' ' . $time);

        //Convert the selected time to that of the server
        $server_time = strtotime(date('Y-m-d H') . ':00:00') + ($scheduled_time - $blog_time);

        wp_schedule_event($server_time, $frequency, 'execute_periodic_drobox_backup');

        return $this;
    }

    public function get_schedule()
    {
        $time = wp_next_scheduled('execute_periodic_drobox_backup');
        $frequency = wp_get_schedule('execute_periodic_drobox_backup');
        $schedule = null;

        if ($time && $frequency) {
            //Convert the time to the blogs timezone
            $blog_time = strtotime(date('Y-m-d H', strtotime(current_time('mysql'))) . ':00:00');
            $blog_time += $time - strtotime(date('Y-m-d H') . ':00:00');
            $schedule = array($blog_time, $frequency);
        }

        return $schedule;
    }

    public function clear_history()
    {
        $this->set_option('history', null);
    }

    public function get_history()
    {
        $history = $this->get_option('history');
        if (!$history){
            return array();
        }

        return explode(',', $history);
    }

    public function get_dropbox_path($source, $file, $root = false)
    {
        $dropbox_location = null;
        if ($this->get_option('store_in_subfolder')){
            $dropbox_location = $this->get_option('dropbox_location');
        }

        if ($root){
            return $dropbox_location;
        }

        $source = rtrim($source, DIRECTORY_SEPARATOR);

        return ltrim(dirname(str_replace($source, $dropbox_location, $file)), DIRECTORY_SEPARATOR);
    }

    public function log_finished_time()
    {
        $history = $this->get_history();
        $history[] = time();

        if (count($history) > self::MAX_HISTORY_ITEMS) {
            array_shift($history);
        }

        $this->set_option('history', implode(',', $history));

        return $this;
    }

    public function complete()
    {
        wp_clear_scheduled_hook('monitor_dropbox_backup_hook');
        wp_clear_scheduled_hook('run_dropbox_backup_hook');
        wp_clear_scheduled_hook('execute_instant_drobox_backup');

        $processed = new WPB2D_Processed_Files();
        $processed->truncate();

        $processed = new WPB2D_Processed_DBTables();
        $processed->truncate();

        $this->set_option('in_progress', false);
        $this->set_option('is_running', false);
        $this->set_option('last_backup_time', time());

        return $this;
    }

    public function die_if_stopped()
    {
        $in_progress = $this->db->get_var("SELECT value FROM {$this->db->prefix}wpb2d_options WHERE name = 'in_progress'");
        if (!$in_progress) {
            $msg = __('Backup stopped by user.', 'wpbtd');
            WPB2D_Factory::get('logger')->log($msg);
            die($msg);
        }
    }
}
