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
$manager = WP_Backup_Extension_Manager::construct();

$wpb2d = $manager->get_url();
$key = $manager->get_key();
$installUrl = $manager->get_install_url();
$buyUrl = $manager->get_buy_url();

$error = $title = null;
if (isset($_REQUEST['error']))
	$error = sprintf(__('There was an error with your payment, please contact %s to resolve.'), '<a href="mailto:michael.dewildt@gmail.com">Mikey</a>');

if (isset($_REQUEST['title']))
	$success = sprintf(__('You have succesfully purchased the %s premium extension, please install it below.'), "<strong>{$_REQUEST['title']}</strong>");

try {
	if (isset($_POST['name'])) {
		$manager->install($_POST['name'], $_POST['file']);
		echo '<script>window.location.reload(true);</script>';
	}

	$installed = array_keys($manager->get_installed());
	$extensions = $manager->get_extensions();
} catch (Exception $e) {
	$error = $e->getMessage();
}

?>
<div class="wrap premium">
	<div class="icon32"><img width="36px" height="36px"
								 src="<?php echo $uri ?>/Images/WordPressBackupToDropbox_64.png"
								 alt="WordPress Backup to Dropbox Logo"></div>
	<h2><?php _e('WordPress Backup to Dropbox', 'wpbtd'); ?></h2>
	<p class="description"><?php printf(__('Version %s', 'wpbtd'), BACKUP_TO_DROPBOX_VERSION) ?></p>
	<h3><?php _e('Premium Extensions', 'wpbtd'); ?></h3>
	<div>
		<p>
			<?php _e('Welcome to Premium Extensions. Please choose an extension below to enhance WordPress Backup to Dropbox.', 'wpbtd'); ?>
			<?php _e('Installing a premium extensions is easy:', 'wpbtd'); ?>
		</p>
		<ol class="instructions">
			<li><?php _e('Click Buy Now and pay for your extension using PayPal', 'wpbtd'); ?></li>
			<li><?php _e('Click Download & Install to download and install the extension', 'wpbtd'); ?></li>
			<li><?php _e('Thats it, options for your extension will be available in the menu on the left', 'wpbtd'); ?></li>
		</ol>
		<a class="paypal" href="#" onclick="javascript:window.open('https://www.paypal.com/au/cgi-bin/webscr?cmd=xpt/Marketing/popup/OLCWhatIsPayPal-outside','olcwhatispaypal','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=400, height=350');">
			<img  src="https://www.paypalobjects.com/en_AU/i/bnr/horizontal_solution_PP.gif" border="0" alt="Solution Graphics">
		</a>

		<a class="moneyback" href="http://wpb2d.com/money-back-guarantee">
			<img src="<?php echo $uri ?>/Images/guarantee.gif" alt="<?php _e('100% money back guarantee') ?>"/>
		</a>

	</div>
	<div class="errors">
		<?php if ($error): ?>
			<p class="error">
				<?php echo $error ?>
			</p>
		<?php elseif ($success): ?>
			<p class="success">
				<?php echo $success ?>
			</p>
		<?php endif; ?>
	</div>
	<table id="extensions">
		<tr>
			<th><?php _e('Name') ?></th>
			<th><?php _e('Description') ?></th>
			<th><?php _e('Price') ?></th>
			<th></th>
		</tr>

		<?php if (is_array($extensions)) foreach ($extensions as $extension): ?>
		<tr>
			<td><?php echo $extension['name'] ?></td>
			<td><?php echo $extension['description'] ?></td>
			<td>$<?php echo $extension['price'] ?> USD</td>
			<td>
				<form action="<?php echo $extension['purchased'] ? $installUrl : $buyUrl; ?>" method="post" id="extension-<?php echo $extension['name'] ?>">
					<input type="hidden" value="<?php echo $extension['name']; ?>" name="name" />
					<input type="hidden" value="<?php echo $extension['file'] ?>" name="file" />
					<input type="hidden" value="<?php echo get_site_url() ?>" name="site" />
					<input type="hidden" value="<?php echo $key ?>" name="key" />
					<?php if (in_array($extension['name'], $installed)): ?>
						<span class="installed">Installed</span>
					<?php else: ?>
						<input type="submit" value="<?php echo $extension['purchased'] ? __('Download & Install') : __('Buy Now'); ?>" class="submitBtn" />
					<?php endif; ?>
				</form>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
	<p>
		<strong><?php _e('Please Note:') ?></strong>&nbsp;
		<?php echo sprintf(__('Each extension can only be activated on a single website for one year. If you manage multiple websites please %s.'), '<a href="http://wpb2d.com/buy-subscription">' . __('purchase a subscription') . '</a>') ?>
	</p>
</div>
