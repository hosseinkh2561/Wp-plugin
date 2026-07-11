<?php
if (!defined('ABSPATH')) exit;
function mp_get_option($key, $default = null) {
    $options = get_option('mp_settings', array());
    return isset($options[$key]) ? $options[$key] : $default;
}
function mp_update_option($key, $value) {
    $options = get_option('mp_settings', array());
    $options[$key] = $value;
    return update_option('mp_settings', $options);
}
