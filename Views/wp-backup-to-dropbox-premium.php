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
$manager = new Extension_Manager();

$wpb2d = $manager->get_url();
$key = $manager->get_key();
$installUrl = $manager->get_install_url();
$buyUrl = $manager->get_buy_url();

$error = $title = null;
if ( isset( $_REQUEST['error'] ) )
	$error = __( sprintf( 'There was an error with your payment, please contact %s to resolve.', '<a href="mailto:michael.dewildt@gmail.com+wpb2d">Mikey</a>' ) );

if ( isset( $_REQUEST['title'] ) )
	$title = $_REQUEST['title'];

try {
	if ( isset( $_POST['extensionId'] ) )
		$manager->install( $_POST['extensionId'], $_POST['file'] );

	$installed = $manager->get_installed();
	$extensions = $manager->get_extensions();
} catch ( Exception $e ) {
	$error = $e->getMessage();
}
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
<div class="wrap">
	<div class="icon32"><img width="36px" height="36px"
								 src="<?php echo $uri ?>/Images/WordPressBackupToDropbox_64.png"
								 alt="WordPress Backup to Dropbox Logo"></div>
	<h2><?php _e( 'WordPress Backup to Dropbox', 'wpbtd' ); ?></h2>
	<p class="description"><?php printf( __( 'Version %s', 'wpbtd' ), BACKUP_TO_DROPBOX_VERSION ) ?></p>
	<h3><?php _e( 'Premium Extensions', 'wpbtd' ); ?></h3>
	<p>
		<?php _e( 'Welcome to Premium Extensions. Please choose an extension below to enhance WordPress Backup to Dropbox.', 'wpbtd' ); ?>
		<?php _e( 'Installing a premium extensions is easy:', 'wpbtd' ); ?>
		<ol>
			<li><?php _e( 'Click Buy Now and pay for your extension using PayPal', 'wpbtd' ); ?></li>
			<li><?php _e( 'Click Install & Acitvate to download and install the extension', 'wpbtd' ); ?></li>
			<li><?php _e( 'Thats it, options for your extension will be available in the menu on the left', 'wpbtd' ); ?></li>
		</ol>
	</p>
	<?php if ($error): ?>
		<p class="error">
			<?php echo $error ?>
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

		<?php foreach ($extensions as $extension): ?>
		<tr>
			<td><?php echo $extension['name'] ?></td>
			<td><?php echo $extension['description'] ?></td>
			<td>$<?php echo $extension['price'] ?> USD</td>
			<td>
				<form action="<?php echo $extension['purchased'] ? $installUrl : $buyUrl; ?>" method="post" id="extension-1">
					<input type="hidden" value="<?php echo $extension['purchased'] ?>" name="extensionId" />
					<input type="hidden" value="<?php echo $extension['file'] ?>" name="extensionFile" />
					<input type="hidden" value="<?php echo get_site_url() ?>" name="site" />
					<input type="hidden" value="<?php echo $key ?>" name="key" />
					<?php if ( in_array(1, $installed ) ): ?>
						<span class="installed">Installed</span>
					<?php else: ?>
						<input type="submit" value="<?php echo $extension['purchased'] ? __( 'Download & Install' ) : __( 'Buy Now' ); ?>" class="submitBtn" />
					<?php endif; ?>
				</form>
			</td>
		</tr>
		<?php endforeach; ?>

	</table>
</div>
