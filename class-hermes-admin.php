<?php
add_action('admin_menu', 'hermes_admin_page');

function hermes_admin_page()
{
    add_options_page(
        __('Hermes Settings','hermes'),
        __('Hermes','hermes'),
        'manage_options',
        'hermes_settings',
        'options_page'
    );
}

function options_page()
{?>
    <div class="wrap">
        <h1><?php _e('Hermes Settings', 'hermes'); ?></h1>
        <?php update_options(); ?>
        <form method="post">
            <table class="form-table">
                <tr valign="top">
                    <th><label><?php _e('SDK AppID', 'hermes'); ?></label></th>
                    <td><input type="text" name="appid" value="<?php echo hoption('appid'); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th><label><?php _e('App Key', 'hermes'); ?></label></th>
                    <td><input type="password" name="appkey" value="<?php echo hoption('appkey'); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th><label><?php _e('SMS Body Template ID', 'hermes'); ?></label></th>
                    <td><input type="text" name="templateid" value="<?php echo hoption('templateid'); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th><label><?php _e('SMS Signature', 'hermes'); ?></label></th>
                    <td><input type="text" name="smssign" value="<?php echo hoption('smssign'); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th><label><?php _e('SMS validity(sec.)', 'hermes'); ?></label></th>
                    <td><input type="text" name="ttl" value="<?php echo hoption('ttl'); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th>
                        <input type="submit" name="save" value="<?php _e('Save changes', 'hermes'); ?>" class="button button-primary" />
                    </th>
                </tr>
                <?php wp_nonce_field('hermes_admin'); ?>
            </table>
        </form>
    </div>
<?php
}

function hoption($name, $default = false)
{
    $option_name = 'Hermes';
    $options = get_option($option_name);

    if (isset($options[$name])) {
        return $options[$name];
    }
    
    return $default;
}

function update_options()
{
    if($_POST != null && check_admin_referer( 'hermes_admin' )){
        update_option( 'Hermes', $_POST );
        echo '<div id="message" class="updated notice is-dismissible"><p>'. __("Success", 'hermes').'</p><button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button></div>';
    }
}
