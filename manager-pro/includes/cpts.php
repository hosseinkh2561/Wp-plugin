<?php
if (!defined('ABSPATH')) exit;
add_action('init', function() {
    register_post_type('mp_slider', array('labels' => array('name' => 'اسلایدرها'), 'public' => false, 'show_ui' => true, 'show_in_menu' => 'manager-pro'));
});
