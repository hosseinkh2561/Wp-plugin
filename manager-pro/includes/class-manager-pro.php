<?php
if (!defined('ABSPATH')) exit;
class Manager_Pro_Base {
    protected static $_instance = null;
    public static function instance() {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }
    public function __construct() {
        $this->includes();
    }
    private function includes() {
        require_once MP_PATH . 'includes/functions.php';
        require_once MP_PATH . 'includes/defaults.php';
        require_once MP_PATH . 'includes/cpts.php';
        require_once MP_PATH . 'admin/class-admin.php';
        require_once MP_PATH . 'public/class-public.php';
    }
}
