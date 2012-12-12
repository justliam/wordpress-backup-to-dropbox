<?php
/**
 * Functionality to remove Dropbox backup from your WordPress installation
 *
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
if (!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

delete_option('backup-to-dropbox-tokens');
delete_option('backup-to-dropbox-options');
delete_option('backup-to-dropbox-history');
delete_option('backup-to-dropbox-current-action');
delete_option('backup-to-dropbox-actions');
delete_option('backup-to-dropbox-excluded-files');
delete_option('backup-to-dropbox-file-list');
delete_option('backup-to-dropbox-in-progress');
delete_option('backup-to-dropbox-premium-extensions');
delete_option('backup-to-dropbox-processed-files');
delete_option('backup-to-dropbox-log');

wp_clear_scheduled_hook('execute_periodic_drobox_backup');
wp_clear_scheduled_hook('execute_instant_drobox_backup');
wp_clear_scheduled_hook('monitor_dropbox_backup_hook');

remove_action('run_dropbox_backup_hook', 'run_dropbox_backup');
remove_action('monitor_dropbox_backup_hook', 'monitor_dropbox_backup');
remove_action('execute_instant_drobox_backup', 'execute_drobox_backup');
remove_action('execute_periodic_drobox_backup', 'execute_drobox_backup');
remove_action('admin_menu', 'backup_to_dropbox_admin_menu');
remove_action('wp_ajax_file_tree', 'backup_to_dropbox_file_tree');
remove_action('wp_ajax_progress', 'backup_to_dropbox_progress');