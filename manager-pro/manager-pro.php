<?php
/**
 * Plugin Name: Manager Pro 1.1
 * Author: HOSSEINKHORRAMI
 */
if (!defined('ABSPATH')) exit;
define('MP_VERSION', '1.1');
define('MP_PATH', plugin_dir_path(__FILE__));
define('MP_URL', plugin_dir_url(__FILE__));
require_once MP_PATH . 'includes/class-manager-pro.php';
function Manager_Pro() { return Manager_Pro_Base::instance(); }
Manager_Pro();
