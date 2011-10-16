<?php
/**
 * This file returns the contents of a directory to the jqueryFileTree
 *
 * The code was adapted from the original PHP connector created by Cory S.N. LaViska
 * at A Beautiful Site (http://abeautifulsite.net/)
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
spawn_cron();
	
global $wpdb;
$backup = new WP_Backup( null, $wpdb );
list($time, $file) = get_option( 'backup-to-dropbox-last-action');

$backup_history = $backup->get_history();
list( $backup_time, $status, $msg ) = array_shift( $backup_history );
?>
<p>Last message: <?php echo date( 'Y-m-d H:i:s', $backup_time ) ?> - <?php echo $msg ?></p>
<p><?php echo date( 'Y-m-d H:i:s', $time ) ?> - Uploading File '<?php echo $file ?>'</p>


