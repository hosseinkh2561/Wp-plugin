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
        if (defined('ELEMENTOR_VERSION') && isset($_GET['elementor-preview'])) {
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

        if (! is_front_page() || is_admin() || is_feed() || post_password_required()) {
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
        if ($options['enabled'] && is_front_page()) {
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

        ob_start();
        include ARENA_SITE_STUDIO_PATH . 'templates/home.php';
        return ob_get_clean();
    }

    /**
     * Make an attachment image or a stable visual placeholder.
     *
     * @param int    $id     Attachment ID.
     * @param string $size   Image size.
     * @param string $class  Extra class.
     * @param string $alt    Alternative text.
     * @return string
     */
    public function image($id, $size = 'large', $class = '', $alt = '') {
        $id = absint($id);
        if ($id && wp_attachment_is_image($id)) {
            $image = wp_get_attachment_image(
                $id,
                $size,
                false,
                array(
                    'class'   => trim('studio-image ' . $class),
                    'alt'     => $alt ? $alt : get_post_meta($id, '_wp_attachment_image_alt', true),
                    'loading' => 'lazy',
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
        $url = trim((string) $url);
        return $url ? esc_url($url) : esc_url(home_url('/'));
    }

    /**
     * Render WordPress menu or a useful fallback when no menu exists yet.
     *
     * @param array $settings Settings.
     * @return string
     */
    public function navigation($settings) {
        $args = array(
            'container'      => false,
            'menu_class'     => 'studio-menu',
            'fallback_cb'    => false,
            'echo'           => false,
            'depth'          => 2,
            'menu'           => ! empty($settings['menu_id']) ? absint($settings['menu_id']) : '',
            'items_wrap'     => '<ul class="studio-menu">%3$s</ul>',
            'link_before'    => '<span>',
            'link_after'     => '</span>',
        );

        $menu = wp_nav_menu($args);
        if ($menu) {
            return $menu;
        }

        $items = array(
            array('خانه', home_url('/')),
            array('فروشگاه', function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/#products')),
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
        if (empty($settings['products'][$key]['enabled'])) {
            return '';
        }

        $products = $this->products($key, $settings['products'][$key]['limit']);
        if (empty($products)) {
            return '';
        }

        $section = $settings['products'][$key];
        $columns = absint($section['columns']);
        $autoplay = ! empty($section['autoplay']) ? 'true' : 'false';
        $html = '<section class="studio-section studio-products" id="products-' . esc_attr($key) . '">';
        $html .= '<div class="studio-section-heading">';
        $html .= '<div><span class="studio-kicker">' . esc_html($this->product_kicker($key)) . '</span>';
        $html .= '<h2>' . esc_html($section['title']) . '</h2>';
        $html .= '<p>' . esc_html($section['description']) . '</p></div>';
        $html .= '<div class="studio-slider-controls" aria-label="کنترل اسلایدر">';
        $html .= '<button type="button" class="studio-slider-button" data-slider-prev aria-label="محصول قبلی">←</button>';
        $html .= '<button type="button" class="studio-slider-button" data-slider-next aria-label="محصول بعدی">→</button>';
        $html .= '</div></div>';
        $html .= '<div class="studio-slider" data-studio-slider data-autoplay="' . esc_attr($autoplay) . '" style="--studio-items:' . esc_attr($columns) . '">';

        foreach ($products as $product) {
            if (! is_object($product) || ! is_a($product, 'WC_Product')) {
                continue;
            }
            $product_id = $product->get_id();
            $html .= '<article class="studio-product-card">';
            $html .= '<a class="studio-product-image" href="' . esc_url(get_permalink($product_id)) . '">';
            $html .= $product->get_image('woocommerce_thumbnail', array('loading' => 'lazy'));
            if ($product->is_on_sale()) {
                $html .= '<span class="studio-product-badge">پیشنهاد ویژه</span>';
            }
            $html .= '</a>';
            $html .= '<div class="studio-product-info">';
            $html .= '<h3><a href="' . esc_url(get_permalink($product_id)) . '">' . esc_html($product->get_name()) . '</a></h3>';
            $html .= '<div class="studio-product-bottom"><span class="studio-product-price">' . wp_kses_post($product->get_price_html()) . '</span>';
            if ($product->is_purchasable() && $product->is_in_stock()) {
                $add_url = $product->is_type('simple') ? $product->add_to_cart_url() : get_permalink($product_id);
                $html .= '<a class="studio-product-add" href="' . esc_url($add_url) . '" aria-label="' . esc_attr($product->is_type('simple') ? 'افزودن ' . $product->get_name() . ' به سبد' : 'مشاهده ' . $product->get_name()) . '">+</a>';
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
        if (! function_exists('wc_get_product') || ! post_type_exists('product')) {
            return array();
        }

        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => max(3, min(24, absint($limit))),
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
            $args['meta_key'] = 'total_sales';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'DESC';
        } elseif ('new_arrivals' === $key) {
            $args['orderby'] = 'date';
            $args['order']   = 'DESC';
        } elseif ('on_sale' === $key) {
            $sale_ids = function_exists('wc_get_product_ids_on_sale') ? wc_get_product_ids_on_sale() : array();
            if (empty($sale_ids)) {
                return array();
            }
            $args['post__in'] = array_map('absint', $sale_ids);
            $args['orderby']  = 'post__in';
        }

        $query    = new \WP_Query($args);
        $products = array();
        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
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
            $custom = str_replace(array('</style', '<script', '</script'), '', $custom);
            $css .= $custom;
        }

        return $css;
    }
}
