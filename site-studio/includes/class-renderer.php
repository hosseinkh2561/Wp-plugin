<?php
/**
 * Front-end rendering, WooCommerce product queries and the theme canvas.
 *
 * @package ArenaSiteStudio
 */

namespace Arena\SiteStudio;

if (! defined('ABSPATH')) {
    exit;
}

final class Renderer {
    /** @var Settings */
    private $settings;

    /** @var bool */
    private $inline_styles_added = false;

    public function __construct(Settings $settings) {
        $this->settings = $settings;
    }

    /**
     * Register front-end hooks.
     */
    public function register() {
        add_shortcode('arena_site_studio', array($this, 'shortcode'));
        add_filter('template_include', array($this, 'template_include'), 99);
        add_filter('the_content', array($this, 'filter_content'), 20);
        add_filter('body_class', array($this, 'body_class'));
    }

    /**
     * Load assets only where the studio can render.
     */
    public function enqueue_assets($force = false) {
        if (! $force && ! $this->should_render_assets()) {
            return;
        }

        wp_enqueue_style(
            'arena-site-studio',
            ARENA_SITE_STUDIO_URL . 'assets/css/frontend.css',
            array(),
            ARENA_SITE_STUDIO_VERSION
        );
        wp_enqueue_script(
            'arena-site-studio',
            ARENA_SITE_STUDIO_URL . 'assets/js/frontend.js',
            array(),
            ARENA_SITE_STUDIO_VERSION,
            true
        );

        if (! $this->inline_styles_added) {
            wp_add_inline_style('arena-site-studio', $this->dynamic_css());
            $this->inline_styles_added = true;
        }
    }

    /**
     * Give the studio a full document canvas on the front page. This avoids
     * fighting the active theme's header and footer when the user chooses the
     * exact canvas mode.
     *
     * @param string $template Current template.
     * @return string
     */
    public function template_include($template) {
        $options = $this->settings->get();

        if (! $options['enabled'] || 'canvas' !== $options['homepage_mode']) {
            return $template;
        }

        if (! is_front_page() || is_feed() || is_embed() || is_customize_preview() || is_preview()) {
            return $template;
        }

        // Elementor's editor must keep control of its preview document.
        $elementor_preview = isset($_GET['elementor-preview']) && is_scalar($_GET['elementor-preview']) ? sanitize_key(wp_unslash($_GET['elementor-preview'])) : '';
        if (defined('ELEMENTOR_VERSION') && $elementor_preview) {
            return $template;
        }

        return ARENA_SITE_STUDIO_PATH . 'templates/canvas.php';
    }

    /**
     * Content mode lets a user keep the theme chrome and place the studio in
     * the theme's content area.
     *
     * @param string $content Page content.
     * @return string
     */
    public function filter_content($content) {
        $options = $this->settings->get();

        if (! $options['enabled'] || 'content' !== $options['homepage_mode']) {
            return $content;
        }

        if (! is_front_page() || is_admin() || is_feed() || post_password_required() || ! is_singular() || ! is_main_query() || ! in_the_loop()) {
            return $content;
        }

        if (has_shortcode($content, 'arena_site_studio')) {
            return $content;
        }

        return $this->render() . $content;
    }

    /**
     * Shortcode bridge for classic editors, block editor and Elementor.
     *
     * @return string
     */
    public function shortcode() {
        return $this->render();
    }

    /**
     * Add a useful class without changing any theme markup.
     *
     * @param array $classes Existing body classes.
     * @return array
     */
    public function body_class($classes) {
        $options = $this->settings->get();
        if ($options['enabled'] && 'disabled' !== $options['homepage_mode'] && is_front_page()) {
            $classes[] = 'arena-site-studio-page';
            $classes[] = 'arena-site-studio-' . sanitize_html_class($options['design']['font']);
        }
        return $classes;
    }

    /**
     * Render the complete studio document fragment.
     *
     * @return string
     */
    public function render() {
        // Shortcodes and the Elementor widget can be placed on a page even
        // while global homepage publishing is off.
        $this->enqueue_assets(true);
        $settings = $this->settings->get();
        $late_style = did_action('wp_head') && ! wp_style_is('arena-site-studio', 'done');

        ob_start();
        if ($late_style) {
            echo '<style id="arena-site-studio-late-style">' . $this->dynamic_css() . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        include ARENA_SITE_STUDIO_PATH . 'templates/home.php';
        return ob_get_clean();
    }

    /**
     * Make an attachment image or a stable visual placeholder.
     *
     * @param int    $id     Attachment ID.
     * @param string $size   Image size.
     * @param string $class  Extra class.
     * @param string $alt     Alternative text.
     * @param string $loading Loading strategy.
     * @return string
     */
    public function image($id, $size = 'large', $class = '', $alt = '', $loading = 'lazy') {
        $id      = is_scalar($id) ? absint($id) : 0;
        $size    = is_scalar($size) && (string) $size ? sanitize_key((string) $size) : 'large';
        $class   = is_scalar($class) ? (string) $class : '';
        $alt     = is_scalar($alt) ? (string) $alt : '';
        $loading = is_scalar($loading) ? (string) $loading : 'lazy';
        if ($id && wp_attachment_is_image($id)) {
            $attachment_alt = get_post_meta($id, '_wp_attachment_image_alt', true);
            $attachment_alt = is_scalar($attachment_alt) ? (string) $attachment_alt : '';
            $image = wp_get_attachment_image(
                $id,
                $size,
                false,
                array(
                    'class'   => trim('studio-image ' . $class),
                    'alt'     => $alt ? $alt : $attachment_alt,
                    'loading' => in_array($loading, array('lazy', 'eager'), true) ? $loading : 'lazy',
                )
            );
            if ($image) {
                return $image;
            }
        }

        return '<span class="studio-image-placeholder ' . esc_attr($class) . '" aria-hidden="true"><span></span></span>';
    }

    /**
     * Return a safe link, defaulting to the home page for empty CTA fields.
     * Hash links are intentionally retained for in-page sections.
     *
     * @param string $url URL.
     * @return string
     */
    public function link($url) {
        $url = is_scalar($url) ? trim((string) $url) : '';
        $safe = $url ? esc_url($url) : '';
        return $safe ? $safe : esc_url(home_url('/'));
    }

    /**
     * Render WordPress menu or a useful fallback when no menu exists yet.
     *
     * @param array $settings Settings.
     * @return string
     */
    public function navigation($settings) {
        $settings = is_array($settings) ? $settings : array();
        $menu_id  = isset($settings['menu_id']) && is_scalar($settings['menu_id']) ? absint($settings['menu_id']) : 0;
        $args = array(
            'container'      => false,
            'menu_class'     => 'studio-menu',
            'fallback_cb'    => false,
            'echo'           => false,
            'depth'          => 2,
            'menu'           => $menu_id ? $menu_id : '',
            'items_wrap'     => '<ul class="studio-menu">%3$s</ul>',
            'link_before'    => '<span>',
            'link_after'     => '</span>',
        );

        $menu = wp_nav_menu($args);
        if (is_wp_error($menu)) {
            $menu = '';
        }
        if ($menu) {
            return $menu;
        }

        $shop_url = home_url('/#products');
        if (function_exists('wc_get_page_permalink')) {
            $candidate = wc_get_page_permalink('shop');
            if (is_scalar($candidate) && (string) $candidate) {
                $shop_url = (string) $candidate;
            }
        }
        $items = array(
            array('خانه', home_url('/')),
            array('فروشگاه', $shop_url),
            array('درباره ما', home_url('/#story')),
            array('تماس با ما', home_url('/#contact')),
        );
        $html = '<ul class="studio-menu">';
        foreach ($items as $item) {
            $html .= '<li><a href="' . esc_url($item[1]) . '"><span>' . esc_html($item[0]) . '</span></a></li>';
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * Render one of the three product rails. It silently disappears when
     * WooCommerce is not installed or there are no products, so a brochure
     * site never shows fake products.
     *
     * @param string $key      Product section key.
     * @param array  $settings Complete settings.
     * @return string
     */
    public function product_section($key, $settings) {
        $allowed = array('best_sellers', 'new_arrivals', 'on_sale');
        if (! is_scalar($key)) {
            return '';
        }
        $key = sanitize_key((string) $key);
        if (! in_array($key, $allowed, true) || ! is_array($settings) || ! isset($settings['products']) || ! is_array($settings['products']) || ! isset($settings['products'][$key]) || ! is_array($settings['products'][$key])) {
            return '';
        }
        $section = $settings['products'][$key];
        if (empty($section['enabled'])) {
            return '';
        }
        $title       = isset($section['title']) && is_scalar($section['title']) ? (string) $section['title'] : '';
        $description = isset($section['description']) && is_scalar($section['description']) ? (string) $section['description'] : '';

        $limit   = isset($section['limit']) && is_scalar($section['limit']) ? absint($section['limit']) : 8;
        $products = $this->products($key, $limit);
        $products = array_values(array_filter($products, function ($product) {
            return is_object($product) && is_a($product, 'WC_Product');
        }));
        if (empty($products)) {
            return '';
        }

        $columns = isset($section['columns']) && is_scalar($section['columns']) ? absint($section['columns']) : 4;
        $autoplay = ! empty($section['autoplay']) ? 'true' : 'false';
        $html = '<section class="studio-section studio-products" id="products-' . esc_attr($key) . '">';
        $html .= '<div class="studio-section-heading">';
        $html .= '<div><span class="studio-kicker">' . esc_html($this->product_kicker($key)) . '</span>';
        $html .= '<h2>' . esc_html($title) . '</h2>';
        $html .= '<p>' . esc_html($description) . '</p></div>';
        $html .= '<div class="studio-slider-controls" aria-label="کنترل اسلایدر">';
        $html .= '<button type="button" class="studio-slider-button" data-slider-prev aria-label="محصول قبلی">←</button>';
        $html .= '<button type="button" class="studio-slider-button" data-slider-next aria-label="محصول بعدی">→</button>';
        $html .= '</div></div>';
        $html .= '<div class="studio-slider" data-studio-slider data-autoplay="' . esc_attr($autoplay) . '" style="--studio-items:' . esc_attr($columns) . '">';

        foreach ($products as $product) {
            if (! is_object($product) || ! is_a($product, 'WC_Product')) {
                continue;
            }
            $product_id   = $product->get_id();
            $product_url  = get_permalink($product_id);
            $product_name = (string) $product->get_name();
            $is_simple    = $product->is_type('simple');
            $html .= '<article class="studio-product-card">';
            $html .= '<a class="studio-product-image" href="' . esc_url($product_url) . '">';
            $product_image = $product->get_image('woocommerce_thumbnail', array('loading' => 'lazy'));
            $html .= $product_image ? wp_kses_post($product_image) : '';
            if ($product->is_on_sale()) {
                $html .= '<span class="studio-product-badge">پیشنهاد ویژه</span>';
            }
            $html .= '</a>';
            $html .= '<div class="studio-product-info">';
            $html .= '<h3><a href="' . esc_url($product_url) . '">' . esc_html($product_name) . '</a></h3>';
            $price_html = $product->get_price_html();
            $html .= '<div class="studio-product-bottom"><span class="studio-product-price">' . ($price_html ? wp_kses_post($price_html) : '<span class="studio-product-no-price">تماس بگیرید</span>') . '</span>';
            if ($product->is_purchasable() && $product->is_in_stock()) {
                $add_url = $is_simple ? $product->add_to_cart_url() : $product_url;
                $html .= '<a class="studio-product-add" href="' . esc_url($add_url) . '" aria-label="' . esc_attr($is_simple ? 'افزودن ' . $product_name . ' به سبد' : 'مشاهده ' . $product_name) . '">+</a>';
            }
            $html .= '</div></div></article>';
        }

        $html .= '</div></section>';
        return $html;
    }

    /**
     * Get products with WooCommerce's public query API.
     *
     * @param string $key   Section key.
     * @param int    $limit Number of products.
     * @return array
     */
    private function products($key, $limit) {
        if (! is_scalar($key) || ! function_exists('wc_get_product') || ! post_type_exists('product')) {
            return array();
        }
        $key   = sanitize_key((string) $key);
        if (! in_array($key, array('best_sellers', 'new_arrivals', 'on_sale'), true)) {
            return array();
        }
        $limit = is_scalar($limit) ? absint($limit) : 8;

        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => max(3, min(24, $limit)),
            'no_found_rows'  => true,
            'meta_query'     => array(
                array(
                    'key'     => '_stock_status',
                    'value'   => 'outofstock',
                    'compare' => '!=',
                ),
            ),
        );

        if ('best_sellers' === $key) {
            $args['meta_key']  = 'total_sales';
            $args['meta_type'] = 'NUMERIC';
            $args['orderby']   = 'meta_value_num';
            $args['order']    = 'DESC';
        } elseif ('new_arrivals' === $key) {
            $args['orderby'] = 'date';
            $args['order']   = 'DESC';
        } elseif ('on_sale' === $key) {
            $sale_ids = function_exists('wc_get_product_ids_on_sale') ? wc_get_product_ids_on_sale() : array();
            if (! is_array($sale_ids) || empty($sale_ids)) {
                return array();
            }
            // A large catalogue can return thousands of sale IDs. The rail
            // never needs more than this bounded set for one request.
            $sale_ids = array_values(array_filter($sale_ids, function ($id) {
                return is_scalar($id) && absint($id);
            }));
            if (empty($sale_ids)) {
                return array();
            }
            $args['post__in'] = array_slice(array_map('absint', $sale_ids), 0, 1000);
            $args['orderby']  = 'post__in';
        }

        $query    = new \WP_Query($args);
        $products = array();
        if (! is_object($query) || ! isset($query->posts) || ! is_array($query->posts)) {
            return $products;
        }
        foreach ($query->posts as $post) {
            if (! is_object($post) || ! isset($post->ID) || ! is_scalar($post->ID)) {
                continue;
            }
            $product = wc_get_product(absint($post->ID));
            if ($product) {
                $products[] = $product;
            }
        }
        wp_reset_postdata();
        return $products;
    }

    private function product_kicker($key) {
        $labels = array(
            'best_sellers' => 'محبوب جامعه ما',
            'new_arrivals' => 'همین حالا اضافه شد',
            'on_sale'      => 'تا پایان موجودی',
        );
        return isset($labels[$key]) ? $labels[$key] : 'محصولات';
    }

    private function should_render_assets() {
        $options = $this->settings->get();
        if (is_front_page() && $options['enabled'] && 'disabled' !== $options['homepage_mode']) {
            return true;
        }

        global $post;
        if (is_singular() && $post instanceof \WP_Post) {
            if (has_shortcode($post->post_content, 'arena_site_studio')) {
                return true;
            }
            // Elementor stores this flag on pages built with its editor. It
            // lets the drag-and-drop widget receive CSS before wp_head.
            if ('builder' === get_post_meta($post->ID, '_elementor_edit_mode', true)) {
                return true;
            }
        }

        return false;
    }

    private function dynamic_css() {
        $options = $this->settings->get();
        $design  = $options['design'];
        $shadow  = 'none';
        if ('soft' === $design['shadow']) {
            $shadow = '0 14px 40px rgba(23, 21, 43, .08)';
        } elseif ('strong' === $design['shadow']) {
            $shadow = '0 18px 48px rgba(23, 21, 43, .16)';
        }

        $css  = ':root{';
        $css .= '--studio-primary:' . esc_attr($design['primary']) . ';';
        $css .= '--studio-secondary:' . esc_attr($design['secondary']) . ';';
        $css .= '--studio-accent:' . esc_attr($design['accent']) . ';';
        $css .= '--studio-background:' . esc_attr($design['background']) . ';';
        $css .= '--studio-surface:' . esc_attr($design['surface']) . ';';
        $css .= '--studio-text:' . esc_attr($design['text']) . ';';
        $css .= '--studio-muted:' . esc_attr($design['muted']) . ';';
        $css .= '--studio-container:' . absint($design['container']) . 'px;';
        $css .= '--studio-radius:' . absint($design['radius']) . 'px;';
        $css .= '--studio-shadow:' . $shadow . ';';
        $css .= '}';

        if ('classic' === $design['font']) {
            $css .= '.arena-studio{--studio-font:Georgia,"Times New Roman",serif;--studio-font-heading:Georgia,"Times New Roman",serif;}';
        } elseif ('modern' === $design['font']) {
            $css .= '.arena-studio{--studio-font:Arial,"Helvetica Neue",sans-serif;--studio-font-heading:Arial,"Helvetica Neue",sans-serif;}';
        }

        $custom = trim((string) $options['custom_css']);
        if ($custom) {
            $custom = preg_replace('/<\/?style|<script|<\/script|javascript\s*:/i', '', $custom);
            $css .= $custom;
        }

        return $css;
    }
}
