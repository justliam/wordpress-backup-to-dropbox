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

list(, $file) = $backup->get_last_action();
list( $time, $status, $msg ) = array_shift( $backup->get_history() );

if ($status == WP_Backup::BACKUP_STATUS_FINISHED): ?>
	<p class="backup_ok"><?php echo sprintf( __( 'Backup Completed at %s' ), date( 'Y-m-d H:i:s', $time ) ) ?></p>
<?php elseif ( $msg ): $class = $status == WP_Backup::BACKUP_STATUS_WARNING ? 'backup_warning' : 'backup_error' ?>
	<strong><?php _e( 'Last message' ) ?>: </strong><span class="<?php echo $class ?>"><?php echo date( 'Y-m-d H:i:s', $time ) . ' - ' . $msg ?></span>
<?php endif; ?>

<?php if ($backup->in_progress()): ?>
	<p><strong><?php _e( 'Uploading File' )  ?>: </strong><?php echo $file ?></p>
<?php endif; ?>



