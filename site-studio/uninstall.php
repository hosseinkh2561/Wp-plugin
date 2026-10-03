<?php
/**
 * Remove Site Studio data only when the administrator explicitly uninstalls it.
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('arena_site_studio_options');
