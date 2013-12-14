<?php
/**
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
$config = WPB2D_Factory::get('config');

if (!$config->get_option('in_progress'))
    spawn_cron();

$log = WPB2D_Factory::get('logger')->get_log();

if (empty($log)): ?>
    <p><?php _e('You have not run a backup yet. When you do you will see a log of it here.'); ?></p>
<?php else: ?>
    <ul>
        <?php foreach (array_reverse($log) as $log_item): ?>
            <li>
            <?php
                if (preg_match('/^Uploaded Files:/', $log_item)) {
                    $files = json_decode(preg_replace('/^Uploaded Files:/', '', $log_item), true);
                    continue;
                }
                echo esc_attr($log_item);
            ?>
            <?php if (!empty($files)): ?>
                <a class="view-files" href="#"><?php _e('View uploaded', 'wpbtd') ?> &raquo;</a>
                <ul class="files">
                    <?php foreach ($files as $file): ?>
                        <li title="<?php echo sprintf(__('Last modified: %s', 'wpbtd'), date('F j, Y, H:i:s', $file['mtime'])) ?>"><?php echo esc_attr($file['file']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php $files = null; endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif;
