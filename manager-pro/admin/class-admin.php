<?php
if (!defined('ABSPATH')) exit;
class MP_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));
        add_action('wp_ajax_mp_save_settings', array($this, 'save'));
    }
    public function add_menu() {
        add_menu_page('Manager Pro', 'مدیریت پرو', 'manage_options', 'manager-pro', array($this, 'page'), 'dashicons-admin-generic');
    }
    public function enqueue($hook) {
        if (strpos($hook, 'manager-pro') === false) return;
        wp_enqueue_style('mp-admin', MP_URL . 'assets/css/admin-style.css');
        wp_enqueue_script('mp-admin', MP_URL . 'assets/js/admin-script.js', array('jquery'));
        wp_localize_script('mp-admin', 'mp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('mp_nonce')));
    }
    public function page() {
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
        include MP_PATH . "admin/templates/$tab.php";
    }
    public function save() {
        check_ajax_referer('mp_nonce', 'nonce');
        update_option('mp_settings', $_POST['settings']);
        wp_send_json_success('ذخیره شد');
    }
}
new MP_Admin();
