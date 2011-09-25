<?php
/**
 * A mock WpDb class
 *
 * @copyright Copyright (C) 2011 Michael De Wildt. All rights reserved.
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
define( 'ARRAY_N', 0 );
define( 'ARRAY_A', 0 );
define( 'DB_NAME', 'UnitTest_DB' );

class Mock_WpDb {

    public function get_results( $sql ) {
        static $count = 0;
        $ret = false;
        if ( $sql == 'SHOW TABLES' ) {
            return array(
                array( 'TABLE' ),
                array( 'TABLE' ),
            );
        } else if ( 'SELECT * FROM TABLE' ) {
            if ( $count % 2 > 0 ) {
                $ret = array();
            } else {
                $ret = array(
                    array(
                        'field 1' => 'value 1',
                        'field 2' => 'value 2',
                        'field 3' => 'value 3',
                        'field 4' => 'value 4',
                        'field 5' => 'value 5',
                    )
                );
            }
            $count++;
        }

        return $ret;
    }

    public function get_row( $sql ) {
        return array( 'SQL', $sql );
    }
}