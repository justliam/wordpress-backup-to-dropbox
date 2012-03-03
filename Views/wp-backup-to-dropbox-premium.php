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

$error = $title = null;
if (isset($_REQUEST['error']))
	$error = true;

if (isset($_REQUEST['title']))
	$title = $_REQUEST['title'];

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

.error {
	color: red;
}

.success {
	color: green;
}
</style>
<script type="text/javascript" language="javascript">
	jQuery(document).ready(function ($) {
		var params = {
			'key' : '<?php echo $key ?>',
			'site' : '<?php echo get_site_url() ?>',
		};
		$.post('http://xtendy/purchased', params, function (data) {
			var arr = JSON.parse(data);
			for (var i = 0; i < arr.length; i++) {
				var $form = $('#extensions').find('#extension-' + arr[i]);
				$form.attr('action', 'admin.php?page=backup-to-dropbox-premium');
				$form.find('.submitBtn').val('Download & Install');
			}
		});
	});
</script>
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
	<?php if ($error): ?>
		<p class="error">
			<?php _e( sprintf( 'There was an error with your payment, please contact %s to resolve.', '<a href="mailto:michael.dewildt@gmail.com+wpb2d">Mikey</a>' ) ) ?>
		</p>
	<?php elseif ($title): ?>
		<p class="success">
			<?php _e( sprintf( 'You have succesfully purchased the %s premium extension, please install it below.', "<strong>$title</strong>" ) ) ?>
		</p>
	<?php endif; ?>
	<table id="extensions">

		<tr>
			<th><?php _e( 'Name' ) ?></th>
			<th><?php _e( 'Description' ) ?></th>
			<th><?php _e( 'Price' ) ?></th>
			<th></th>
		</tr>

		<tr>
			<td><?php _e( 'Zip Archive' ) ?></td>
			<td><?php _e( 'Zips your backup before uploading it to Dropbox' ) ?></td>
			<td>$10 USD</td>
			<td>
				<form action="http://wpb2d/buy" method="post" id="extension-1">
					<input type="hidden" value="zip" name="extension" />
					<input type="hidden" value="<?php echo get_site_url() ?>" name="site" />
					<input type="hidden" value="<?php echo $key ?>" name="key" />
					<input type="submit" value="Buy Now" class="submitBtn" />
				</form>
			</td>
		</tr>

	</table>
</div>
