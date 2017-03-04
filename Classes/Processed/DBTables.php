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
class WPB2D_Processed_DBTables extends WPB2D_Processed_Base
{
    const COMPLETE = -1;

    protected function getTableName()
    {
        return 'dbtables';
    }

    protected function getId()
    {
        return 'name';
    }

    public function get_table($name)
    {
        foreach ($this->processed as $table) {
            if ($table->name == $name) {
                return $table;
            }
        }
    }

    public function is_complete($name)
    {
        $table = $this->get_table($name);

        if ($table) {
            return $table->count == self::COMPLETE;
        }

        return false;
    }

    public function count_complete()
    {
        $i = 0;

        foreach ($this->processed as $table) {
            if ($table->count == self::COMPLETE) {
                $i++;
            }
        }

        return $i;
    }

    public function update_table($table, $count)
    {
        $this->upsert(array(
            'name' => $table,
            'count' => (int)$count,
        ));
    }
}
