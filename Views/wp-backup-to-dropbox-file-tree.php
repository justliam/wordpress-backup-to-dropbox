<?php
/**
 * This file returns the contents of a directory to the jqueryFileTree
 *
 * The code was adapted from the original PHP connector created by Cory S.N. LaViska
 * at A Beautiful Site (http://abeautifulsite.net/)
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

WP_Backup_Config::construct()
	->set_time_limit()
	->set_memory_limit()
	;

try {
	$file_list = new File_List();

	if (isset($_POST['dir'])) {
		$_POST['dir'] = urldecode($_POST['dir']);
		if (file_exists($_POST['dir']) && is_readable($_POST['dir'])) {
			$files = scandir($_POST['dir']);
			natcasesort($files);
			if (count($files) > 2) { /* The 2 accounts for . and .. */
				echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
				// All dirs
				foreach ($files as $file) {
					if ($file != '.' && $file != '..' && file_exists($_POST['dir'] . $file) && is_dir($_POST['dir'] . $file)) {

						if (!is_readable($_POST['dir']) || $_POST['dir'] == dirname(ABSPATH) . '/' && !strstr($file, basename(ABSPATH))) {
							continue;
						}

						if ($file_list->in_ignore_list($file)) {
							continue;
						}

						$full_path = htmlentities($_POST['dir'] . $file) . "/";
						$file = htmlentities($file);
						$class = $file_list->get_checkbox_class($full_path);

						echo "<li class='directory collapsed'>";
						echo "<a href='#' rel='$full_path' class='tree'>$file</a>";
						echo "<a href='#' rel='$full_path' class='checkbox directory $class'></a>";
						echo "</li>";
					}
				}
				// All files
				foreach ($files as $file) {

					if ($file != '.' && $file != '..' && file_exists($_POST['dir'] . $file) && !is_dir($_POST['dir'] . $file)) {

						if ($_POST['dir'] == dirname(ABSPATH) . '/' && !strstr($file, basename(ABSPATH))) {
							continue;
						}

						if ($file_list->in_ignore_list($file)) {
							continue;
						}

						$full_path = htmlentities($_POST['dir'] . $file);
						$file = htmlentities($file);
						$class = $file_list->get_checkbox_class($full_path);
						$ext = preg_replace('/^.*\./', '', $file);

						echo "<li class='file ext_$ext'>";
						echo "<a href='#' rel='$full_path' class='tree'>$file</a>";
						if (strstr($_POST['dir'] . $file, DB_NAME . '-backup.sql') === false) {
							echo "<a href='#' rel='$full_path' class='checkbox $class'></a>";
						}
						echo "</li>";
					}
				}
				echo "</ul>";
			}
		}
	} else if ($_POST['exclude'] && $_POST['path']) {
		if ($_POST['exclude'] == 'true')
			$file_list->set_excluded($_POST['path']);
		else
			$file_list->set_included($_POST['path']);
	}
} catch (Exception $e) {
	echo '<p class="error">' . $e->getMessage() . '</p>';
}