<?php
/**
 * This file contains the contents of the Dropbox admin monitor page.
 *
 * @copyright Copyright (C) 2011-2013 Michael De Wild. All rights reserved.
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
$manager = WPB2D_Factory::get('extension-manager');

if (isset($_REQUEST['error'])) {
    add_settings_error('error', 'wpb2d_premium_error', sprintf(__('There was an error with your payment, please contact %s to resolve.', 'wpbtd'), '<a href="mailto:michael.dewildt@gmail.com">Mikey</a>'), 'error');
}

if (isset($_REQUEST['title'])) {
    add_settings_error('general', 'wpb2d_premium_success', sprintf(__('You have succesfully purchased %s.', 'wpbtd'), "<strong>{$_REQUEST['title']}</strong>"), 'updated');
}

if (isset($_POST['name'])) {
    try {
        $ext = $manager->install($_POST['name']);
        $slug = $manager->get_menu_slug($ext);
        $title = $ext->get_menu();

        add_settings_error('general', 'wpb2d_premium_success', __('Installation successful. Please configure the extension from its menu item.', 'wpbtd'), 'updated');

        ?><script type='text/javascript'>
            jQuery(document).ready(function ($) {
                $('a[href$="backup-to-dropbox-premium"]').parent().before('<li><a href="admin.php?page=<?php echo $slug ?>"><?php echo $title ?></a></li>');
            });
        </script><?php
    } catch (Exception $e) {
        add_settings_error('error', 'wpb2d_premium_error', $e->getMessage(), 'error');
    }
}

try {
    $extensions = $manager->get_extensions();
} catch (Exception $e) {
    add_settings_error('error', 'wpb2d_premium_error', $e->getMessage(), 'error');
}

function wpb2d_products($manager, $type, $extensions)
{
    $installUrl = $manager->get_install_url();
    $buyUrl = $manager->get_buy_url();

    if (!is_array($extensions)) {
        return;
    }

    $i = 0;
    foreach ($extensions as $extension) {
        if (!in_array($extension['type'], $type)) {
            continue;
        }
        ?>
        <div class="product-box--<?php echo $extension['type'] ?> product-box--<?php echo $extension['type'] . "--$i" ?> <?php if ($i++ == 0) echo 'product-box--no-margin' ?>">
            <div class="product-box__title wp-menu-name"><?php echo esc_attr($extension['name']) ?></div>
            <div class="product-box__subtitle"><?php echo esc_attr($extension['description']) ?></div>

            <?php if (!is_int($extension['expiry'])): ?>
                <div class="product-box__price">$<?php echo esc_attr($extension['price']) ?> USD</div>
            <?php endif; ?>

            <?php if (is_int($extension['expiry']) && ($manager->is_installed($extension['name']) || in_array($extension['type'], array('multi', 'bundle')))): ?>
                <span class="product-box__tick">&#10004;</span>
                <?php if ($type == 'single'): ?>
                    <span class="product-box__message"><?php _e('Installed and up-to-date', 'wpbtd') ?></span>
                <?php endif; ?>
            <?php else: ?>
                <div class="product-box__button">
                    <form action="<?php echo is_int($extension['expiry']) ? $installUrl : $buyUrl ?>" method="post" id="extension-<?php echo esc_attr($extension['name']) ?>">
                        <input type="hidden" value="<?php echo WPB2D_Extension_Manager::API_KEY ?>" name="apikey" />
                        <input type="hidden" value="<?php echo esc_attr($extension['name']) ?>" name="name" />
                        <input type="hidden" value="<?php echo get_site_url() ?>" name="site" />
                        <input class="button-primary" type="submit" value="<?php echo is_int($extension['expiry']) ? __('Install Now') : __('Buy Now') ?>" class="submitBtn" />
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($extension['expiry'] == 'expired' && $extension['type'] == 'single'): ?>
                <div class="product-box__alert"><?php _e('Your annual updates have expired. Please make a new purchase to renew.') ?></div>
            <?php elseif (is_int($extension['expiry'])): ?>
                <div class="product-box__alert"><?php echo __('Expires on', 'wpbtd') . ' ' . date_i18n(get_option('date_format'), $extension['expiry']) ?></div>
            <?php endif; ?>
        </div>
        <?php
    }
}
?>
<script type='text/javascript'>
    jQuery(document).ready(function ($) {
        $("#tabs").tabs();
    });
</script>
<div class="wrap premium" id="wpb2d">
    <div class="icon32"><img width="36px" height="36px"
                                 src="<?php echo $uri ?>/Images/WordPressBackupToDropbox_64.png"
                                 alt="WordPress Backup to Dropbox Logo"></div>
    <h2><?php _e('WordPress Backup to Dropbox', 'wpbtd'); ?></h2>
    <p class="description"><?php printf(__('Version %s', 'wpbtd'), BACKUP_TO_DROPBOX_VERSION) ?></p>

    <?php settings_errors(); ?>

    <h3><?php _e('Premium Extensions', 'wpbtd'); ?></h3>
    <div>
        <p>
            <?php _e('Welcome to Premium Extensions. Please choose an extension below to enhance WordPress Backup to Dropbox.', 'wpbtd'); ?>
            <?php _e('Installing a premium extension is easy:', 'wpbtd'); ?>
        </p>
        <ol class="instructions">
            <li><?php _e('Click Buy Now and pay using PayPal.', 'wpbtd'); ?></li>
            <li><?php _e('Click Install Now to download and install the extension.', 'wpbtd'); ?></li>
            <li><?php _e("That's it, options for your extension will be available in the menu on the left.", 'wpbtd'); ?></li>
            <li><?php _e('If you manage many websites, consider the multiple site options.'); ?></li>
        </ol>
        <a class="paypal" href="#" onclick="javascript:window.open('https://www.paypal.com/au/cgi-bin/webscr?cmd=xpt/Marketing/popup/OLCWhatIsPayPal-outside','olcwhatispaypal','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=400, height=350');">
            <img  src="https://www.paypalobjects.com/en_AU/i/bnr/horizontal_solution_PP.gif" border="0" alt="Solution Graphics">
        </a>

        <img src="<?php echo $uri ?>/Images/guarantee.gif" alt="<?php _e('100% money back guarantee') ?>"/>
    </div>

    <p></p>

    <div id="tabs">
        <ul>
            <li><a href="#single-site-tab">Single site</a></li>
            <li><a href="#multi-site-tab">Multiple sites</a></li>
        </ul>
        <div id="single-site-tab">
            <?php wpb2d_products($manager, array('single', 'bundle'), $extensions); ?>
            <p class="note_paragraph">
                <strong><?php _e('Please Note:') ?></strong>&nbsp;
                <?php echo sprintf(__('Each payment includes updates and support on a single website for one year.', 'wpbtd')) ?>
            </p>
        </div>

        <div id="multi-site-tab">
            <p class="paragraph-block">
                <?php echo sprintf(__('
                    These plans are perfect for web developers and people who manage multiple websites
                    because they allow you to install all extensions on the sites that you register.
                    Each plan includes updates and support for one year and you can update your limit at any time.
                ', 'wpbtd')); ?>
            </p>
            <?php wpb2d_products($manager, array('multi'), $extensions); ?>
        </div>
    </div>
</div>
