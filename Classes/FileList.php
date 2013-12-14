<?php
/**
 * A class with functions the perform a backup of WordPress
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
class WPB2D_FileList
{
    private static $ignored_patterns = array(
        '.DS_Store', 'Thumbs.db', 'desktop.ini',
        '.git', '.gitignore', '.gitmodules',
        '.svn', '.dropbox',
        '.sass-cache',
    );

    private
        $excluded_files = array(),
        $excluded_dirs = array(),
        $db
        ;

    public static function construct()
    {
        return new self();
    }

    public function __construct()
    {
        $this->db = WPB2D_Factory::db();

        $result = $this->db->get_results("SELECT * FROM {$this->db->prefix}wpb2d_excluded_files WHERE isdir = 0");
        foreach ($result as $value) {
            $this->excluded_files[] = $value->file;
        }

        $result = $this->db->get_results("SELECT * FROM {$this->db->prefix}wpb2d_excluded_files WHERE isdir = 1");
        foreach ($result as $value) {
            $this->excluded_dirs[] = $value->file;
        }
    }

    public function set_included($path)
    {
        if (is_dir($path)) {
            $this->include_dir(rtrim($path, DIRECTORY_SEPARATOR));
        } else {
            $this->include_file($path);
        }
    }

    public function set_excluded($path)
    {
        if (is_dir($path)) {
            $this->exclude_dir(rtrim($path, DIRECTORY_SEPARATOR));
        } else {
            $this->exclude_file($path);
        }
    }

    public function is_excluded($path)
    {
        if (is_dir($path)) {
            return $this->is_excluded_dir($path);
        } else {
            return $this->is_excluded_file($path);
        }
    }

    private function exclude_file($file)
    {
        if (!in_array($file, $this->excluded_files)) {
            $this->excluded_files[] = $file;
            $this->db->insert("{$this->db->prefix}wpb2d_excluded_files", array(
                'file' => $file,
                'isdir' => false
            ));
        }
    }

    private function exclude_dir($dir)
    {
        if (!in_array($dir, $this->excluded_dirs)) {
            $this->excluded_dirs[] = $dir;
            $this->db->insert("{$this->db->prefix}wpb2d_excluded_files", array(
                'file' => $dir,
                'isdir' => true
            ));
        }
    }

    private function include_file($file)
    {
        $key = array_search($file, $this->excluded_files);

        $this->db->query(
            $this->db->prepare("DELETE FROM {$this->db->prefix}wpb2d_excluded_files WHERE file =  %s", $file)
        );

        unset($this->excluded_files[$key]);
    }

    private function include_dir($dir)
    {
        $key = array_search($dir, $this->excluded_dirs);

        $this->db->query(
            $this->db->prepare("DELETE FROM {$this->db->prefix}wpb2d_excluded_files WHERE file =  %s", $dir)
        );

        unset($this->excluded_dirs[$key]);
    }

    private function is_excluded_file($file)
    {
        if (!in_array($file, $this->excluded_files)) {
            return $this->is_excluded_dir(dirname($file));
        }

        return true;
    }

    private function is_excluded_dir($dir)
    {
        if (empty($this->excluded_dirs)) {
            return false;
        }

        if (in_array($dir, $this->excluded_dirs)) {
            return true;
        }

        if ($dir == get_sanitized_home_path()) {
            return false;
        }

        return $this->is_excluded_dir(dirname($dir));
    }

    private function is_partial_dir($dir)
    {
        if (is_dir($dir) && is_readable($dir)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);
            $files->setMaxDepth(10);
            foreach ($files as $file) {
                $file_name = $file->getPathname();

                if ($file_name == $dir) {
                    continue;
                }

                if (self::in_ignore_list(basename($file_name))) {
                    continue;
                }

                if ($this->is_excluded($file_name)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function get_checkbox_class($path)
    {
        $class = '';
        if ($this->is_excluded($path)) {
            $class = 'checked';
        } elseif ($this->is_partial_dir($path)) {
            $class = 'partial';
        }

        return $class;
    }

    public static function in_ignore_list($file)
    {
        foreach (self::$ignored_patterns as $pattern) {
            if (preg_match('/' . preg_quote($pattern) . '/', $file)) {
                return true;
            }
        }
    }
}
