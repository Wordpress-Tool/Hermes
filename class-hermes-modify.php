<?php

function modify_phone_page()
{
    global $current_user, $wpdb;

    $table_name = HERMES_TABLE_NAME;

    $old_phone = get_user_meta($current_user->ID, 'phone', true);

    if (!empty($_POST['check'])) {
        if (empty($_POST['token']) || !wp_verify_nonce($_POST['token'], HERMES_PLUGIN_BASENAME)) {
            wp_die(__('Unauthorized access', 'hermes'));
        }

        $errors = '';

        if (empty($_POST['captcha_code']) || empty($_SESSION['captcha'])) {
            $errors .= __("<strong>ERROR</strong>: Image verification code error.<br>","hermes");
        } else {
            $secretword = explode("-", $_SESSION['captcha']);

            if (time() - $secretword[1] > 120) {
                $errors .= __("<strong>ERROR</strong>: Image verification code has expired, please refresh the page and try again.<br>","hermes");
            } else if (trim(strtolower($_POST['captcha_code'])) != $secretword[0]) {
                $errors .= __("<strong>ERROR</strong>: Image verification code error.<br>","hermes");
            }
        }

        unset($_SESSION['captcha']);

        $phone = trim($_POST['phone']);
        if (!Hermes::verify_phone_number($phone)) {
            $errors .= __("<strong>ERROR</strong>: Phone number wrong.<br>","hermes");
            $_POST['phone'] = '';
            $_POST['code'] = '';
        } else {
            $phone_exist = $wpdb->get_var($wpdb->prepare("SELECT `user_id` FROM `" . $wpdb->prefix . "usermeta` WHERE `meta_key` = 'phone_num' AND `meta_value` = %s AND `user_id` != %d;", $phone, $current_user->ID));
            if ($phone == $old_phone) {
                $errors .= __("<strong>ERROR</strong>: Phone number no change.<br>","hermes");
                $_POST['code'] = '';
            } elseif (!empty($phone_exist)) {
                $errors .= __("<strong>ERROR</strong>: Phone number has been registered.<br>","hermes");
                $_POST['phone'] = '';
                $_POST['code'] = '';
            } else if (empty($_POST['code'])) {
                $errors .= __("<strong>ERROR</strong>: Please enter the SMS code.<br>","hermes");
            } else {
                $code = $wpdb->get_var($wpdb->prepare("SELECT `security_code` FROM `$table_name` WHERE `phone_num` = %s;", $phone));
                if (empty($code)) {
                    $errors .= __("<strong>ERROR</strong>: Please get the SMS code.<br>","hermes");
                    $_POST['code'] = '';
                } else if ($code != $_POST['code']) {
                    $errors .= __("<strong>ERROR</strong>: SMS code is incorrect.<br>","hermes");
                    $_POST['code'] = '';
                }
            }
        }

        if (empty($errors)) {
            $success = update_user_meta($current_user->ID, 'phone', $phone);
            $wpdb->query($wpdb->prepare("DELETE FROM `$table_name` WHERE `phone_num` = %s", $phone));
        }
    }
    ?>
    <script>
        var ajaxurl  = "<?php echo admin_url('admin-ajax.php'); ?>",
            captcha  = "<?php echo plugins_url('class-hermes-captcha.php', __FILE__); ?>",
            sendcode = "<?php _e('Send Code', 'hermes');?>",
            error10  = "<?php _e('Unauthorized access', 'hermes');?>",
            error20  = "<?php _e('Image verification code error', 'hermes');?>",
            error30  = "<?php _e('Image authentication code expired', 'hermes');?>",
            error40  = "<?php _e('Get verification code too often', 'hermes');?>",
            error50  = "<?php _e('Phone number wrong', 'hermes');?>",
            error60  = "<?php _e('Phone number has been registered', 'hermes');?>",
            error70  = "<?php _e('Phone number no change', 'hermes');?>",
            error80  = "<?php _e('Database error', 'hermes');?>",
            error90  = "<?php _e('SMS configuration errors', 'hermes');?>"
    </script>
    <script src="<?php echo plugins_url('js/jquery.min.js', __FILE__); ?>"></script>
    <script src="<?php echo plugins_url('js/check.js', __FILE__); ?>"></script>

    <div class="wrap" id="profile-page">
       <h1><?php _e('Modify Phone Number', 'hermes'); ?></h1>
       <?php
        if (!empty($errors)) {
            echo '<div class="error notice is-dismissible" id="message"><p>' . $errors . '</p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button></div>';
        } else if (@$success) {
            echo '<div id="message" class="updated notice is-dismissible">
            <p>'. __("<strong>SUCCESS</strong>: Phone number is modified to $phone", "hermes").'</p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button></div>';
        }
        ?>
       <form id="your-profile" action="#" method="post">
          <p>
             <label for="phone"><?php _e('Phone number', 'hermes');?><span id="sendSmsBtnErr" style="color:#ff5c57;font-size: 14px;margin-left:5px;"></span> <br/>
                <input id="phone" class="regular-text ltr" type="text" size="15" value="<?php echo empty($_POST['phone']) ? $old_phone : $_POST['phone']; ?>" name="phone" autocomplete="off" />
             </label>
          </p>
          <p>
             <label for="CAPTCHA"><?php _e('Captcha', 'hermes');?><span id="captchaErr" style="color:#ff5c57;font-size: 14px;margin-left:5px;"></span> <br/>
                <input id="CAPTCHA" class="regular-text ltr" type="text" size="10" value="" name="captcha_code" autocomplete="off" />
             </label>
          </p>
          <p>
             <label>
                <img id="captcha_img" src="<?php echo plugins_url('class-hermes-captcha.php', __FILE__); ?>" title="<?php _e('Click to change', 'hermes');?>" alt="<?php _e('Click to change', 'hermes');?>" onclick="document.getElementById('captcha_img').src = '<?php echo plugins_url('class-hermes-captcha.php', __FILE__); ?>?' + Math.random();document.getElementById('CAPTCHA').focus();return false;" />
                <a href="javascript:void(0)" onclick="document.getElementById('captcha_img').src = '<?php echo plugins_url('class-hermes-captcha.php', __FILE__); ?>?' + Math.random();document.getElementById('CAPTCHA').focus();return false;"><?php _e('Click to change', 'hermes');?></a>
             </label>
          </p>
          <p>
             <label for="code"><?php _e('SMS Code', 'hermes');?><br/>
                <input id="code" class="regular-text ltr" type="text" size="4" value="<?php echo empty($_POST['code']) ? '' : $_POST['code']; ?>" name="code" />
                &nbsp;<input id="sendSmsBtn" type="button" value="<?php _e('Send Code', 'hermes');?>" class="button button-secondary" />
             </label>
          </p>
          <input type="hidden" name="check" id="admin_check" value="1" />
          <input type="hidden" name="token" value="<?php echo wp_create_nonce(HERMES_PLUGIN_BASENAME); ?>">
          <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Confirm the change', 'hermes');?>"></p>
       </form>
    </div>
    <?php
}
