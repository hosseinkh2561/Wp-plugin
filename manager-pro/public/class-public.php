<?php
if (!defined('ABSPATH')) exit;
class MP_Public {
    public function __construct() {
        add_filter('template_include', array($this, 'override'));
    }
    public function override($template) {
        if (is_front_page()) return MP_PATH . 'public/templates/full-page.php';
        return $template;
    }
}
new MP_Public();
