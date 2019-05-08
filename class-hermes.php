<?php

class Hermes
{
    private static $table_name = HERMES_TABLE_NAME;
    private static $plugin_dir = HERMES_PLUGIN_DIR;
    private static $appid = HERMES_OPTIONS['appid'];
    private static $appkey = HERMES_OPTIONS['appkey'];
    private static $templateId = HERMES_OPTIONS['templateid'];
    private static $smsSign = HERMES_OPTIONS['smssign'];
    private static $ttl = HERMES_OPTIONS['ttl'];

    public static function load_textdomain()
    {
        load_plugin_textdomain( 'hermes', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
    }

    public static function generate_random_code()
    {
        return str_pad(mt_rand(0, 999999), 6, "0", STR_PAD_BOTH);
    }

    public static function verify_phone_number($phonenumber)
    {
        if (empty($phonenumber) || !preg_match("/^(((13[0-9]{1})|(15[0-9]{1})|(17[0-9]{1})|(18[0-9]{1}))+\d{8})$/", $phonenumber)) {
            return 0;
        } else {
            return 1;
        }
    }

    public static function add_contact_fields($profile_fields)
    {
        if (current_user_can('manage_options')) {
            $profile_fields['phone'] = __('Phone Number', 'hermes');
        }

        return $profile_fields;
    }

    public static function modify_phone_submenu()
    {
        add_submenu_page(
            'users.php',
            __('Modify Phone Number', 'hermes'),
            __('Modify Phone Number', 'hermes'),
            'read',
            'modify-phone',
            'modify_phone_page'
        );
    }

    public static function register_data_form()
    {
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
        <p>
        <label for="user_pwd"><?php _e('Password', 'hermes');?><br/>
            <input id="user_pwd" class="input" type="password" size="25" value="" name="user_pass" />
        </label>
        </p>
        <p>
        <label for="user_repwd"><?php _e('Confirm Password', 'hermes');?><br/>
            <input id="user_repwd" class="input" type="password" size="25" value="" name="user_repass" />
        </label>
        </p>
        <p>
        <label for="CAPTCHA"><?php _e('Captcha', 'hermes');?><span id="captchaErr" style="color:#ff5c57;font-size: 14px;margin-left:5px;"></span> <br/>
        <img id="captcha_img" style="height:40px" src="<?php echo plugins_url('class-hermes-captcha.php', __FILE__); ?>" title="<?php _e('Click to change', 'hermes');?>" alt="<?php _e('Click to change', 'hermes');?>" onclick="document.getElementById('captcha_img').src = '<?php echo plugins_url('class-hermes-captcha.php', __FILE__); ?>?' + Math.random();document.getElementById('CAPTCHA').focus();return false;" />
        <a href="javascript:void(0)" onclick="document.getElementById('captcha_img').src = '<?php echo plugins_url('class-hermes-captcha.php', __FILE__); ?>?' + Math.random();document.getElementById('CAPTCHA').focus();return false;"><?php _e('Click to change', 'hermes');?></a>
            <input id="CAPTCHA" style="" class="input" type="text" size="10" value="" name="captcha_code" autocomplete="off" />
        </label>
        </p>
        <p>
        <label for="phone"><?php _e('Phone number', 'hermes');?><span id="sendSmsBtnErr" style="color:#ff5c57;font-size: 14px;margin-left:5px;"></span> <br/>
            <input id="phone" style="width:65%;display: inline-block;" class="input" type="text" size="15" value="<?php echo empty($_POST['phone']) ? '' : $_POST['phone']; ?>" name="phone" autocomplete="off" />
            <input id="sendSmsBtn" style="width: 32%; display: inline-block; padding: 0 6px;" type="button" value="<?php _e('Send Code', 'hermes');?>" class="button button-primary button-large" />
        </label>
        </p>
        <p>
        <label for="code"><?php _e('SMS Code', 'hermes');?><br/>
            <input id="code" class="input" type="text" size="6" value="<?php echo empty($_POST['code']) ? '' : $_POST['code']; ?>" name="code" />
        </label>
        </p>
        <input type="hidden" name="token" value="<?php echo wp_create_nonce(HERMES_PLUGIN_BASENAME); ?>">
        <?php
    }

    public static function register_data_save($user_id)
    {
        global $wpdb;

        update_user_meta($user_id, 'phone', $_POST['phone']);

        $wpdb->query($wpdb->prepare("DELETE FROM `" . self::$table_name . "` WHERE `phone_num` = %s", $_POST['phone']));

        $userdata = array();
        $userdata['ID'] = $user_id;
        $userdata['user_pass'] = $_POST['user_pass'];

        wp_update_user($userdata);
    }

    public static function register_data_check($login, $email, $errors)
    {
        global $wpdb;
    
        if (empty($_POST['token']) || !wp_verify_nonce($_POST['token'], HERMES_PLUGIN_BASENAME)) {
            wp_die(__('Unauthorized access', 'hermes'));
        }
    
        if (strlen($_POST['user_pass']) < 6) {
            $errors->add('password_length', __("<strong>ERROR</strong>: Password length needs at least 6 digits.","hermes"));
        } elseif ($_POST['user_pass'] != $_POST['user_repass']) {
            $errors->add('password_error', __("<strong>ERROR</strong>: Password needs to be consistent.","hermes"));
        }
    
        if (empty($_POST['captcha_code']) || empty($_SESSION['captcha'])) {
            $errors->add('captcha_spam', __("<strong>ERROR</strong>: Image verification code error.","hermes"));
        } else {
            $secretword = explode("-", $_SESSION['captcha']);
            if (time() - $secretword[1] > 120) {
                $errors->add('captcha_spam', __("<strong>ERROR</strong>: Image verification code has expired, please refresh the page and try again.","hermes"));
            } else if (trim(strtolower($_POST['captcha_code'])) != $secretword[0]) {
                $errors->add('captcha_spam', __("<strong>ERROR</strong>: Image verification code error.","hermes"));
            }
        }
    
        unset($_SESSION['captcha']);
    
        $phone = trim($_POST['phone']);
        if (!self::verify_phone_number($phone)) {
            $errors->add('phone_error', __("<strong>ERROR</strong>: Phone number wrong.","hermes"));
            $_POST['phone'] = '';
            $_POST['code'] = '';
        } else {
            $phone_exist = $wpdb->get_var($wpdb->prepare("SELECT `user_id` FROM `" . $wpdb->prefix . "usermeta` WHERE `meta_key` = 'phone_num' AND `meta_value` = %s", $phone));
            if (!empty($phone_exist)) {
                $errors->add('phone_error', __("<strong>ERROR</strong>: Phone number has been registered.","hermes"));
                $_POST['phone'] = '';
                $_POST['code'] = '';
            } else {
                if (empty($_POST['code'])) {
                    $errors->add('code_error1', __("<strong>ERROR</strong>: Please enter the SMS code.","hermes"));
                } else {
                    $code = $wpdb->get_var($wpdb->prepare("SELECT `security_code` FROM `". self::$table_name ."` WHERE `phone_num` = %s", $phone));
                    if (empty($code)) {
                        $errors->add('code_error2', __("<strong>ERROR</strong>: Please get the SMS code.","hermes"));
                    } else if ($code != $_POST['code']) {
                        $errors->add('code_error3', __("<strong>ERROR</strong>: SMS code is incorrect.","hermes"));
                    }
                }
            }
        }
    }

    public static function remove_default_password_nag()
    {
        global $user_ID;
        delete_user_setting('default_password_nag', $user_ID);
        update_user_option($user_ID, 'default_password_nag', false, true);
    }

    public static function tencent_sms($code, $phone)
    {
        require_once HERMES_PLUGIN_DIR . 'class-hermes-sms.php';

        $ssender = new SmsSingleSender(self::$appid, self::$appkey);
        $params = [$code, self::$ttl/60];
        $result = $ssender->sendWithParam("86", $phone, self::$templateId,$params, self::$smsSign, "", "");
        $rsp = json_decode($result, true);

        if ($rsp['errmsg'] == 'OK') {
            return 0;
        }else{
            return $rsp['errmsg'];
        }
    }

    public static function send_sms()
    {
        $error = '';

        if ('POST' != $_SERVER['REQUEST_METHOD']) {
            header('Allow: POST');
            header('HTTP/1.1 405 Method Not Allowed');
            header('Content-Type: text/plain');
            $error = 10;
        }

        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
            $error = 10;
        }

        if (!check_ajax_referer(HERMES_PLUGIN_BASENAME, 'token', false)) {
            $error = 10;
        }

        if (empty($_POST['captcha_code']) || empty($_SESSION['captcha'])) {
            $error = 20;
        } else {
            $secretword = explode("-", $_SESSION['captcha']);
            if (time() - $secretword[1] > 120) {
                $error = 30;
            } else if (trim(strtolower($_POST['captcha_code'])) != $secretword[0]) {
                $error = 20;
            }
        }

        global $wpdb;

        $wpdb->query($wpdb->prepare("DELETE FROM `" . self::$table_name . "` WHERE `timestamp` < %s", (time() - self::$ttl)));
        $phone = trim($_POST['phone']);
        $time = $wpdb->get_var($wpdb->prepare("SELECT `timestamp` FROM `" . self::$table_name . "` WHERE `phone_num` = %s", $phone));

        if (!self::verify_phone_number($phone)) {
            $error = 50;
        } else {
            $user_id = $wpdb->get_var($wpdb->prepare("SELECT `user_id` FROM `" . $wpdb->prefix . "usermeta` WHERE `meta_key` = 'phone_num' AND `meta_value` = %s", $phone));
            if ($_POST['admin'] == 1) {
                global $current_user;
                if ($current_user->ID == $user_id) {
                    $error = 70;
                }
            } elseif (!empty($user_id)) {
                $error = 60;
            }
        }

        if (!empty($time) && (time() - $time) <= 60) {
            $error = 40;
        }

        if (empty($error)) {
            $code = self::generate_random_code();
            if (empty($time)) {
                $db = $wpdb->insert(self::$table_name, array('phone_num' => $phone, 'security_code' => $code, 'timestamp' => time()), array('%s', '%s', '%d'));
            } else {
                $db = $wpdb->update(self::$table_name, array('security_code' => $code, 'timestamp' => time()), array('phone_num' => $phone), array('%s', '%d'), array('%s'));
            }
            
            if ($db) {
                $send_status = self::tencent_sms($code, $phone);
                $result['result'] = ($send_status) ? 'error' : 'success';
                if ($send_status) {
                    $result['message'] = $send_status;
                    $result['code'] = 90;
                }else{
                    $result['code'] = 200;
                }
            } else {
                $result['result'] = 'error';
                $result['code'] = 80;
            }
        } else {
            $result['result'] = 'error';
            $result['code'] = $error;
        }

        $result = json_encode($result);
        echo $result;

        exit();
    }

    public static function change_translated_text($translated_text, $untranslated_text, $domain)
    {
        if ($untranslated_text === 'A password will be e-mailed to you.' || $untranslated_text === 'Registration confirmation will be emailed to you.') {
            return '';
        } else if ($untranslated_text === 'Registration complete. Please check your e-mail.' || $untranslated_text === 'Registration complete. Please check your email.') {
            return __('Registration complete', 'hermes');
        } else {
            return $translated_text;
        }
    }

    public static function plugin_activation()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = '';

        if (!empty($wpdb->charset)) {
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        } else {
            $charset_collate = "DEFAULT CHARSET=utf8";
        }
        if (!empty($wpdb->collate)) {
            $charset_collate .= " COLLATE $wpdb->collate";
        }

        $creat_table = "CREATE TABLE IF NOT EXISTS " . self::$table_name . "(
            `phone_num` char(11) NOT NULL,
            `security_code` char(6) NOT NULL,
            `timestamp` bigint(20) unsigned NOT NULL,
            PRIMARY KEY (`phone_num`),
            UNIQUE KEY `phone_num` (`phone_num`,`security_code`)
            ) $charset_collate;";
        dbDelta($creat_table);

        add_option('Hermes');
    }

    public static function plugin_deactivation()
    {
        global $wpdb;

        $drop_table = "DROP TABLE IF EXISTS " . self::$table_name;
        $wpdb->query($drop_table);

        delete_option('Hermes');
    }
}
