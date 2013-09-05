<?php
/**
 * A class with functions the perform a backup of WordPress
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
abstract class WPB2D_Processed_Base
{
    protected
        $db,
        $processed = array()
        ;

    public function __construct()
    {
        $this->db = WPB2D_Factory::db();

        $ret = $this->db->get_results("SELECT * FROM {$this->db->prefix}wpb2d_processed_{$this->getType()}");
        if (is_array($ret)) {
            $this->processed = $ret;
        }
    }

    protected function getType()
    {
        $bits = explode('_', get_class($this));

        return strtolower(array_pop($bits));
    }

    protected function getId()
    {
        return rtrim($this->getType(), 's');
    }

    protected function getVar($val)
    {
        return $this->db->get_var(
            $this->db->prepare("SELECT * FROM {$this->db->prefix}wpb2d_processed_{$this->getType()} WHERE {$this->getId()} = %s", $val)
        );
    }

    protected function upsert($data)
    {
        $exists = $this->db->get_var(
            $this->db->prepare("SELECT * FROM {$this->db->prefix}wpb2d_processed_{$this->getType()} WHERE {$this->getId()} = %s", $data[$this->getId()])
        );

        if (is_null($exists)) {
            $this->db->insert("{$this->db->prefix}wpb2d_processed_{$this->getType()}", $data);

            $this->processed[] = (object)$data;
        } else {
            $this->db->update(
                "{$this->db->prefix}wpb2d_processed_{$this->getType()}",
                $data,
                array($this->getId() => $data[$this->getId()])
            );

            $i = 0;
            foreach ($this->processed as $p) {
                $id = $this->getId();
                if ($p->$id == $data[$this->getId()]) {
                    break;
                }
                $i++;
            }

            $this->processed[$i] = (object)$data;
        }
    }

    public function truncate()
    {
        $this->db->query("TRUNCATE {$this->db->prefix}wpb2d_processed_{$this->getType()}");
    }
}
