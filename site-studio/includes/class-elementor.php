<?php
/**
 * Optional Elementor bridge. Site Studio remains fully functional without it.
 *
 * @package ArenaSiteStudio
 */

namespace Arena\SiteStudio;

if (! defined('ABSPATH')) {
    exit;
}

final class Elementor {
    /** @var Renderer */
    private $renderer;

    public function __construct(Renderer $renderer) {
        $this->renderer = $renderer;
    }

    public function register_hooks() {
        add_action('elementor/widgets/register', array($this, 'register_widget'));
        add_action('elementor/elements/categories_registered', array($this, 'register_category'));
        add_action('wp_ajax_arena_site_studio_elementor', array($this, 'install_or_activate'));
    }

    /**
     * Make the full studio available as one drag-and-drop Elementor widget.
     * The anonymous class is created only after Elementor has loaded, avoiding
     * a fatal error when Elementor is not installed.
     *
     * @param object $widgets_manager Elementor widgets manager.
     */
    public function register_widget($widgets_manager) {
        if (! class_exists('Elementor\\Widget_Base') || ! is_object($widgets_manager)) {
            return;
        }

        $renderer = $this->renderer;
        $widget   = new class($renderer) extends \Elementor\Widget_Base {
            private $studio_renderer;

            public function __construct($renderer, $data = array(), $args = null) {
                $this->studio_renderer = $renderer;
                parent::__construct($data, $args);
            }

            public function get_name() {
                return 'arena-site-studio';
            }

            public function get_title() {
                return 'استودیو پوسته سایت';
            }

            public function get_icon() {
                return 'eicon-layout-settings';
            }

            public function get_categories() {
                return array('arena-studio');
            }

            protected function register_controls() {
                $this->start_controls_section(
                    'studio_section',
                    array('label' => 'استودیو پوسته')
                );
                $this->add_control(
                    'studio_help',
                    array(
                        'type'            => \Elementor\Controls_Manager::RAW_HTML,
                        'raw'             => '<p style="line-height:1.8">چیدمان کامل از تنظیمات استودیو خوانده می‌شود. برای رنگ، هدر، فوتر و اسلایدرها از منوی استودیو پوسته استفاده کنید.</p>',
                        'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                    )
                );
                $this->end_controls_section();
            }

            protected function render() {
                echo $this->studio_renderer->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
        };

        $widgets_manager->register($widget);
    }

    public function register_category($categories_manager) {
        if (! is_object($categories_manager) || ! method_exists($categories_manager, 'add_category')) {
            return;
        }
        $categories_manager->add_category(
            'arena-studio',
            array(
                'title' => 'استودیو پوسته',
                'icon'  => 'fa fa-paint-brush',
            )
        );
    }

    /**
     * Install and activate Elementor only after an administrator explicitly
     * clicks the action in the Site Studio screen.
     */
    public function install_or_activate() {
        check_ajax_referer('arena_site_studio_admin', 'nonce');

        if (! current_user_can('install_plugins') && ! current_user_can('activate_plugins')) {
            wp_send_json_error(array('message' => 'دسترسی لازم برای مدیریت افزونه‌ها وجود ندارد.'), 403);
        }

        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $plugin_file = 'elementor/elementor.php';

        if (is_plugin_active($plugin_file)) {
            wp_send_json_success(array('message' => 'Elementor از قبل فعال است.'));
        }

        if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
            $result = activate_plugin($plugin_file);
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()), 500);
            }
            wp_send_json_success(array('message' => 'Elementor فعال شد. صفحه را تازه‌سازی کنید.'));
        }

        if (! current_user_can('install_plugins')) {
            wp_send_json_error(array('message' => 'برای نصب Elementor باید دسترسی نصب افزونه را داشته باشید.'), 403);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        $api = plugins_api(
            'plugin_information',
            array(
                'slug'   => 'elementor',
                'fields' => array('sections' => false),
            )
        );
        if (is_wp_error($api)) {
            wp_send_json_error(array('message' => 'اطلاعات Elementor دریافت نشد: ' . $api->get_error_message()), 500);
        }

        $skin    = new \Automatic_Upgrader_Skin();
        $upgrader = new \Plugin_Upgrader($skin);
        $result  = $upgrader->install($api->download_link);
        if (is_wp_error($result) || ! $result) {
            $message = is_wp_error($result) ? $result->get_error_message() : 'نصب Elementor انجام نشد.';
            wp_send_json_error(array('message' => $message), 500);
        }

        $activation = activate_plugin($plugin_file);
        if (is_wp_error($activation)) {
            wp_send_json_error(array('message' => 'نصب انجام شد اما فعال‌سازی ناموفق بود: ' . $activation->get_error_message()), 500);
        }

        wp_send_json_success(array('message' => 'Elementor نصب و فعال شد. صفحه را تازه‌سازی کنید.'));
    }

    public function is_installed() {
        return file_exists(WP_PLUGIN_DIR . '/elementor/elementor.php');
    }

    public function is_active() {
        if (! function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active('elementor/elementor.php');
    }
}
