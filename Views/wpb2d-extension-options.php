<?php
/**
 * This file contains the contents of the Dropbox admin options page.
 *
 * @copyright Copyright (C) 2011-2013 Michael De Wildt. All rights reserved.
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

$instances = WPB2D_Extension_Manager::construct()->get_instances();

if (array_key_exists('wpb2d_save_changes', $_POST)) {
    try {
        check_admin_referer('backup_to_dropbox_options_save');

        $config = WPB2D_Factory::get('config');
        $accepted = array();

        foreach ($instances as $extension) {
            $extension->update($_POST);
        }

        add_settings_error('general', 'settings_updated', __('Settings saved.'), 'updated');

    } catch (Exception $e) {
        add_settings_error('extension-options', 'invalid_email', $e->getMessage(), 'error');
    }
}

settings_errors();
?>
<form id="backup_to_dropbox_options" name="backup_to_dropbox_options" action="" method="post">
    <?php foreach ($instances as $extension): ?>
        <h3><?php echo $extension->get_menu_title() ?></h3>
        <table class="form-table">
            <?php foreach ($extension->get_form_items() as $form_item): ?>
                <tr valign="top">
                    <th scope="row">
                        <label for="<?php echo $form_item['name'] ?>"><?php echo $form_item['label'] ?></label></th>
                    <td>
                        <?php if ($form_item['type'] == 'checkbox'): ?>
                            <input type="hidden" value="0" name="<?php echo $form_item['name'] ?>">
                        <?php endif; ?>
                        <input
                            name="<?php echo $form_item['name'] ?>"
                            type="<?php echo $form_item['type'] ?>"
                            <?php
                                if (isset($form_item['value'])) {
                                    if (get_settings_errors('extension-options')) {
                                        echo 'value="' . $_POST[$form_item['name']] . '"';
                                    } else {
                                        echo 'value="' . $form_item['value'] . '"';
                                    }
                                }

                                if ($form_item['type'] == 'checkbox') {
                                    echo 'value="1"';

                                    if ($form_item['checked']) {
                                        echo 'checked="checked"';
                                    }
                                }

                            ?>
                        >
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endforeach; ?>

    <p class="submit">
        <input type="submit" id="wpb2d_save_changes" name="wpb2d_save_changes" class="button-primary" value="<?php _e('Save Changes', 'wpbtd'); ?>">
    </p>
    <?php wp_nonce_field('backup_to_dropbox_options_save'); ?>
</form>