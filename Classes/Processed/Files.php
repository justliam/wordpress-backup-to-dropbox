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
class WPB2D_Processed_Files extends WPB2D_Processed_Base
{
    protected function getTableName()
    {
        return 'files';
    }

    protected function getId()
    {
        return 'file';
    }

    public function get_file_count()
    {
        return count($this->processed);
    }

    public function get_file($file_name)
    {
        foreach ($this->processed as $file) {
            if ($file->file == $file_name){
                return $file;
            }
        }
    }

    public function file_complete($file)
    {
        $this->update_file($file, 0, 0);
    }

    public function update_file($file, $upload_id, $offset)
    {
        $this->upsert(array(
            'file' => $file,
            'uploadid' => $upload_id,
            'offset' => $offset,
        ));
    }

    public function add_files($new_files)
    {
        foreach ($new_files as $file) {

            if ($this->get_file($file)) {
                continue;
            }

            $this->upsert(array(
                'file' => $file,
                'uploadid' => null,
                'offset' => null,
            ));
        }

        return $this;
    }
}
