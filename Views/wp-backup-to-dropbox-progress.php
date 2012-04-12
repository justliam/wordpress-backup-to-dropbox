<?php
/**
 * @copyright Copyright (C) 2011-2012 Michael De Wildt. All rights reserved.
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
$config = new WP_Backup_Config();

if (!$config->in_progress())
	spawn_cron();

$action = $config->get_current_action();
$file_count = count($config->get_processed_files());

if ($action && $config->in_progress()): ?>
	<p>
		<strong><?php echo date('H:i:s', $action['time']) ?>: </strong>
		<?php echo $action['message']; ?>
	</p>
	<?php if ($file_count > 0 ): ?>
		<p>
			<strong><?php echo date('H:i:s', strtotime(current_time('mysql'))) ?>: </strong>
			<?php echo sprintf(__('Processed %d files.', 'wpbtd'), $file_count); ?>
		</p>
	<?php endif; ?>
<?php endif; ?>