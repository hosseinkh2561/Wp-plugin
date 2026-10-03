<?php
/**
 * Plugin Name:       استودیو پوسته سایت
 * Plugin URI:        https://example.com/site-studio
 * Description:       سازنده سبک و سریع صفحه اصلی فروشگاه؛ با هدر، فوتر، اسلایدر محصولات، رنگ‌ها، چیدمان‌های آماده و اتصال اختیاری به Elementor.
 * Version:           1.0.1
 * Author:            Site Studio
 * Author URI:        https://example.com
 * Text Domain:       arena-site-studio
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 */

if (! defined('ABSPATH')) {
    exit;
}

define('ARENA_SITE_STUDIO_VERSION', '1.0.1');
define('ARENA_SITE_STUDIO_FILE', __FILE__);
define('ARENA_SITE_STUDIO_PATH', plugin_dir_path(__FILE__));
define('ARENA_SITE_STUDIO_URL', plugin_dir_url(__FILE__));

require_once ARENA_SITE_STUDIO_PATH . 'includes/class-settings.php';
require_once ARENA_SITE_STUDIO_PATH . 'includes/class-renderer.php';
require_once ARENA_SITE_STUDIO_PATH . 'includes/class-elementor.php';
require_once ARENA_SITE_STUDIO_PATH . 'includes/class-admin.php';
require_once ARENA_SITE_STUDIO_PATH . 'includes/class-plugin.php';

register_activation_hook(__FILE__, array('Arena\SiteStudio\Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('Arena\SiteStudio\Plugin', 'deactivate'));

Arena\SiteStudio\Plugin::instance()->run();
