<?php
/**
 * Main plugin service container.
 *
 * @package ArenaSiteStudio
 */

namespace Arena\SiteStudio;

if (! defined('ABSPATH')) {
    exit;
}

final class Plugin {
    /** @var Plugin|null */
    private static $instance;

    /** @var Settings */
    private $settings;

    /** @var Renderer */
    private $renderer;

    /** @var Admin */
    private $admin;

    /** @var Elementor */
    private $elementor;

    public static function instance() {
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->settings  = new Settings();
        $this->renderer  = new Renderer($this->settings);
        $this->elementor = new Elementor($this->renderer);
        $this->admin     = new Admin($this->settings, $this->renderer, $this->elementor);
    }

    /**
     * Wire every service. No external plugin is required for the native
     * canvas; Elementor and WooCommerce are optional enhancements.
     */
    public function run() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_init', array($this->settings, 'register'));
        add_action('init', array($this->renderer, 'register'));
        add_action('wp_enqueue_scripts', array($this->renderer, 'enqueue_assets'), 20);
        add_action('admin_menu', array($this->admin, 'menu'));
        add_action('admin_enqueue_scripts', array($this->admin, 'assets'));
        add_action('admin_notices', array($this->admin, 'notices'));
        add_action('admin_post_arena_site_studio_reset', array($this->admin, 'reset'));
        add_action('admin_post_arena_site_studio_export', array($this->admin, 'export'));
        add_filter('plugin_action_links_' . plugin_basename(ARENA_SITE_STUDIO_FILE), array($this->admin, 'plugin_links'));
        $this->elementor->register_hooks();
    }

    public function load_textdomain() {
        load_plugin_textdomain('arena-site-studio', false, dirname(plugin_basename(ARENA_SITE_STUDIO_FILE)) . '/languages');
    }

    /**
     * Safe activation: store defaults but do not overwrite an existing site.
     */
    public static function activate() {
        if (false === get_option(Settings::OPTION, false)) {
            add_option(Settings::OPTION, Settings::defaults());
        }
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public function renderer() {
        return $this->renderer;
    }

    public function settings() {
        return $this->settings;
    }

    public function elementor() {
        return $this->elementor;
    }
}
