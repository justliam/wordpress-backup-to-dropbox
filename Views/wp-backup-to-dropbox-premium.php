<?php
/**
 * This file contains the contents of the Dropbox admin monitor page.
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
$key = 'c7d97d59e0af29b2b2aa3ca17c695f96';
?>
<style>
th {
	text-align: left;
	border-top: 1px solid #DEDEDE;
}

td, th {
	border-right: 1px solid #DEDEDE;
	border-bottom: 1px solid #DEDEDE;
	padding: 10px;
}

table {
	border-left: 1px solid #DEDEDE;
}
</style>
<div class="wrap">
	<div class="icon32"><img width="36px" height="36px"
								 src="<?php echo $uri ?>/Images/WordPressBackupToDropbox_64.png"
								 alt="WordPress Backup to Dropbox Logo"></div>
	<h2><?php _e( 'WordPress Backup to Dropbox', 'wpbtd' ); ?></h2>
	<p class="description"><?php printf( __( 'Version %s', 'wpbtd' ), BACKUP_TO_DROPBOX_VERSION ) ?></p>
	<h3><?php _e( 'Premium Extensions', 'wpbtd' ); ?></h3>
	<p>
		<?php _e( 'Welcome to Premium Extensions. Please choose an extension below to enhance WordPress Backup to Dropbox.', 'wpbtd' ); ?>
		<?php _e( 'Activating premium extensions is easy:', 'wpbtd' ); ?>
		<ol>
			<li><?php _e( 'Click buy now and pay for your extension using PayPal', 'wpbtd' ); ?></li>
			<li><?php _e( 'Click Install Now to download and install the extension', 'wpbtd' ); ?></li>
			<li><?php _e( 'Finally, click Activate to turn it on and enjoy', 'wpbtd' ); ?></li>
		</ol>
	</p>
	<table>

		<tr>
			<th><?php _e( 'Name' ) ?></th>
			<th><?php _e( 'Description' ) ?></th>
			<th><?php _e( 'Price' ) ?></th>
			<th></th>
		</tr>

		<tr>
			<td><?php _e( 'Zip before upload' ) ?></td>
			<td><?php _e( 'Zips up your website and database dump before uploading it to Dropbox.' ) ?></td>
			<td>$20</td>
			<td>
				<form action="http://wpb2d/buy" method="post">
					<input type="hidden" value="zip" name="extension" />
					<input type="hidden" value="<?php echo get_site_url() ?>" name="site" />
					<input type="hidden" value="<?php echo $key ?>" name="key" />
					<input type="submit" value="Buy Now" />
				</form>
			</td>
		</tr>

		<tr>
			<td><?php _e( 'Notification Emails' ) ?></td>
			<td><?php _e( 'Sends a notification email when a backup completes or runs into problems.' ) ?></td>
			<td>$5</td>
			<td>
				<form action="http://wpb2d/buy" method="post">
					<input type="hidden" value="email" name="extension" />
					<input type="hidden" value="<?php echo get_site_url() ?>" name="site" />
					<input type="hidden" value="<?php echo $key ?>" name="key" />
					<input type="submit" value="Buy Now" />
				</form>
			</td>
		</tr>

	</table>
</div>
