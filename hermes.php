<?php
/*
Plugin Name: Hermes
Plugin URI: https://github.com/Vtrois/Hermes
Description: WordPress user registration plugin based on Tencent cloud SMS
Version: 1.0.0
Author: Seaton Jiang
Author URI: https://www.vtrois.com/
License: GPLv3 or later
Text Domain: hermes
Domain Path: /languages
 */

/*
Copyright (C) 2019 Seaton Jiang

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!isset($_SESSION)) {
    session_start();
    session_regenerate_id(true);
}

global $wpdb;

define('HERMES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HERMES_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('HERMES_OPTIONS', get_option('Hermes'));
define('HERMES_TABLE_NAME', $wpdb->prefix . "hermes");

register_activation_hook(__FILE__, array('Hermes', 'plugin_activation'));
register_deactivation_hook(__FILE__, array('Hermes', 'plugin_deactivation'));

require_once HERMES_PLUGIN_DIR . 'class-hermes.php';
require_once HERMES_PLUGIN_DIR . 'class-hermes-admin.php';
require_once HERMES_PLUGIN_DIR . 'class-hermes-modify.php';

// Load plugin textdomain
add_action('init', array('Hermes', 'load_textdomain'));

// Add the phone filter
add_filter('user_contactmethods', array('Hermes', 'add_contact_fields'));

// Add the modify menu
add_action('admin_menu', array('Hermes', 'modify_phone_submenu'));

// Add the phone number form
add_action('register_form', array('Hermes', 'register_data_form'));

// Send SMS verification code
add_action('wp_ajax_send_sms', array('Hermes', 'send_sms'));
add_action('wp_ajax_nopriv_send_sms', array('Hermes', 'send_sms'));

// Save the phone number form
add_action('user_register', array('Hermes', 'register_data_save'));

// Remove default password nag
add_action('admin_init', array('Hermes', 'remove_default_password_nag'));

// Change translated text
add_filter('gettext', array('Hermes', 'change_translated_text'), 20, 3);

// Check the phone number form
add_action('register_post', array('Hermes', 'register_data_check'), 10, 3);
