<?php
/**
 * Settings and safe defaults for Site Studio.
 *
 * @package ArenaSiteStudio
 */

namespace Arena\SiteStudio;

if (! defined('ABSPATH')) {
    exit;
}

final class Settings {
    const OPTION = 'arena_site_studio_options';
    const GROUP  = 'arena_site_studio_group';

    /**
     * Register the setting with WordPress.
     */
    public function register() {
        register_setting(
            self::GROUP,
            self::OPTION,
            array(
                'type'              => 'array',
                'sanitize_callback' => array(__CLASS__, 'sanitize'),
                'default'           => self::defaults(),
            )
        );
    }

    /**
     * Return the complete option object. Defaults are merged so an upgrade
     * never makes a newly added field undefined.
     *
     * @return array
     */
    public static function get() {
        $saved = get_option(self::OPTION, array());

        if (! is_array($saved)) {
            $saved = array();
        }

        return array_replace_recursive(self::defaults(), $saved);
    }

    /**
     * Defaults are deliberately useful in Persian and do not depend on a
     * particular theme. An admin can publish them with one switch.
     *
     * @return array
     */
    public static function defaults() {
        return array(
            'enabled'       => false,
            'preset'        => 'commerce',
            'homepage_mode' => 'canvas',
            'brand_name'    => get_bloginfo('name') ? get_bloginfo('name') : 'فروشگاه شما',
            'tagline'       => 'انتخابی تازه برای سبک زندگی شما',
            'logo_id'       => 0,
            'menu_id'       => 0,
            'header'        => array(
                'announcement_enabled' => true,
                'announcement'         => 'ارسال رایگان برای سفارش‌های بالای ۲ میلیون تومان',
                'sticky'               => true,
                'show_search'          => true,
                'show_cart'            => true,
                'show_account'         => false,
                'cta_enabled'          => true,
                'cta_text'             => 'مشاهده فروشگاه',
                'cta_url'              => '#products',
            ),
            'design'        => array(
                'primary'        => '#6C4CFF',
                'secondary'      => '#17152B',
                'accent'         => '#FFB547',
                'background'     => '#F7F7FB',
                'surface'        => '#FFFFFF',
                'text'           => '#17152B',
                'muted'          => '#6B7280',
                'container'      => 1200,
                'columns'        => 4,
                'radius'         => 22,
                'shadow'         => 'soft',
                'font'           => 'system',
                'header_style'   => 'floating',
            ),
            'hero'          => array(
                'enabled'        => true,
                'eyebrow'        => 'فصل تازه، انتخاب تازه',
                'title'          => 'خانه‌ای برای انتخاب‌های دوست‌داشتنی شما',
                'description'    => 'محصولات منتخب، تجربه خرید ساده و ارسال سریع؛ همه‌چیز برای اینکه انتخاب بعدی‌تان لذت‌بخش‌تر باشد.',
                'button_text'    => 'شروع خرید',
                'button_url'     => '#products',
                'secondary_text' => 'داستان ما',
                'secondary_url'  => '#story',
                'image_id'       => 0,
                'image_position' => 'right',
            ),
            'features'      => array(
                'enabled' => true,
                'items'   => array(
                    array(
                        'icon'        => '✦',
                        'title'       => 'انتخاب‌های دقیق',
                        'description' => 'هر محصول با وسواس انتخاب شده است.',
                    ),
                    array(
                        'icon'        => '↗',
                        'title'       => 'ارسال سریع',
                        'description' => 'سفارش شما با بسته‌بندی مطمئن می‌رسد.',
                    ),
                    array(
                        'icon'        => '♡',
                        'title'       => 'خرید با خیال راحت',
                        'description' => 'پشتیبانی واقعی قبل و بعد از خرید.',
                    ),
                ),
            ),
            'products'      => array(
                'best_sellers' => array(
                    'enabled'     => true,
                    'title'       => 'پرفروش‌ترین‌ها',
                    'description' => 'محبوب‌ترین انتخاب‌های این روزها',
                    'limit'       => 8,
                    'columns'     => 4,
                    'autoplay'    => true,
                ),
                'new_arrivals' => array(
                    'enabled'     => true,
                    'title'       => 'تازه‌رسیده‌ها',
                    'description' => 'محصولاتی که به‌تازگی به فروشگاه اضافه شده‌اند',
                    'limit'       => 8,
                    'columns'     => 4,
                    'autoplay'    => false,
                ),
                'on_sale'      => array(
                    'enabled'     => true,
                    'title'       => 'فرصت‌های ویژه',
                    'description' => 'قیمت‌های جذاب، برای مدت محدود',
                    'limit'       => 8,
                    'columns'     => 4,
                    'autoplay'    => true,
                ),
            ),
            'story'         => array(
                'enabled'     => true,
                'eyebrow'     => 'کمی درباره ما',
                'title'       => 'فروشگاهی که برای تجربه شما ساخته شده است',
                'description' => 'اینجا می‌توانید داستان برند، ارزش‌ها و دلیل متفاوت بودن‌تان را با یک متن صمیمی با مخاطب به اشتراک بگذارید.',
                'button_text' => 'بیشتر بدانید',
                'button_url'  => '#',
                'image_id'    => 0,
                'image_side'  => 'left',
            ),
            'footer'        => array(
                'enabled'     => true,
                'eyebrow'     => 'همیشه در ارتباطیم',
                'description' => 'برای دریافت خبرهای تازه، تخفیف‌ها و پیشنهادهای اختصاصی همراه ما باشید.',
                'email'       => get_bloginfo('admin_email'),
                'phone'       => '',
                'address'     => '',
                'image_id'    => 0,
                'copyright'   => 'تمامی حقوق برای برند شما محفوظ است.',
                'show_menu'   => true,
            ),
            'integrations'  => array(
                'use_elementor_widget'    => true,
                'use_elementor_locations' => false,
                'woo_notice_dismissed'    => false,
            ),
            'custom_css'    => '',
        );
    }

    /**
     * Sanitize the nested settings object. Explicit whitelists prevent a
     * pasted option object from becoming executable markup or CSS.
     *
     * @param mixed $input Raw settings.
     * @return array
     */
    public static function sanitize($input) {
        $defaults = self::defaults();
        $input    = is_array($input) ? wp_unslash($input) : array();
        $out      = $defaults;

        $out['enabled']       = ! empty($input['enabled']);
        $out['preset']        = self::choice($input, 'preset', array('commerce', 'editorial', 'minimal', 'marketplace'), $defaults['preset']);
        $out['homepage_mode'] = self::choice($input, 'homepage_mode', array('canvas', 'content', 'disabled'), $defaults['homepage_mode']);
        $out['brand_name']    = self::text($input, 'brand_name', $defaults['brand_name'], 120);
        $out['tagline']       = self::text($input, 'tagline', $defaults['tagline'], 180);
        $out['logo_id']       = self::id($input, 'logo_id');
        $out['menu_id']       = self::id($input, 'menu_id');

        $header = isset($input['header']) && is_array($input['header']) ? $input['header'] : array();
        $out['header']['announcement_enabled'] = ! empty($header['announcement_enabled']);
        $out['header']['announcement']         = self::text($header, 'announcement', $defaults['header']['announcement'], 180);
        $out['header']['sticky']               = ! empty($header['sticky']);
        $out['header']['show_search']          = ! empty($header['show_search']);
        $out['header']['show_cart']            = ! empty($header['show_cart']);
        $out['header']['show_account']         = ! empty($header['show_account']);
        $out['header']['cta_enabled']          = ! empty($header['cta_enabled']);
        $out['header']['cta_text']             = self::text($header, 'cta_text', $defaults['header']['cta_text'], 80);
        $out['header']['cta_url']              = self::url($header, 'cta_url');

        $design = isset($input['design']) && is_array($input['design']) ? $input['design'] : array();
        foreach (array('primary', 'secondary', 'accent', 'background', 'surface', 'text', 'muted') as $color) {
            $raw = isset($design[$color]) && is_scalar($design[$color]) ? (string) $design[$color] : '';
            $value = sanitize_hex_color($raw);
            $out['design'][$color] = $value ? $value : $defaults['design'][$color];
        }
        $out['design']['container']    = self::number($design, 'container', 960, 1600, $defaults['design']['container']);
        $out['design']['columns']      = self::number($design, 'columns', 2, 5, $defaults['design']['columns']);
        $out['design']['radius']       = self::number($design, 'radius', 0, 48, $defaults['design']['radius']);
        $out['design']['shadow']       = self::choice($design, 'shadow', array('none', 'soft', 'strong'), $defaults['design']['shadow']);
        $out['design']['font']         = self::choice($design, 'font', array('system', 'modern', 'classic'), $defaults['design']['font']);
        $out['design']['header_style'] = self::choice($design, 'header_style', array('floating', 'line', 'solid'), $defaults['design']['header_style']);

        $hero = isset($input['hero']) && is_array($input['hero']) ? $input['hero'] : array();
        $out['hero']['enabled']        = ! empty($hero['enabled']);
        $out['hero']['eyebrow']        = self::text($hero, 'eyebrow', $defaults['hero']['eyebrow'], 120);
        $out['hero']['title']          = self::textarea($hero, 'title', $defaults['hero']['title'], 220);
        $out['hero']['description']    = self::textarea($hero, 'description', $defaults['hero']['description'], 500);
        $out['hero']['button_text']    = self::text($hero, 'button_text', $defaults['hero']['button_text'], 80);
        $out['hero']['button_url']     = self::url($hero, 'button_url');
        $out['hero']['secondary_text'] = self::text($hero, 'secondary_text', $defaults['hero']['secondary_text'], 80);
        $out['hero']['secondary_url']  = self::url($hero, 'secondary_url');
        $out['hero']['image_id']       = self::id($hero, 'image_id');
        $out['hero']['image_position'] = self::choice($hero, 'image_position', array('left', 'right'), $defaults['hero']['image_position']);

        $features = isset($input['features']) && is_array($input['features']) ? $input['features'] : array();
        $out['features']['enabled'] = ! empty($features['enabled']);
        $items = isset($features['items']) && is_array($features['items']) ? array_slice($features['items'], 0, 4) : array();
        $out['features']['items'] = array();
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $out['features']['items'][] = array(
                'icon'        => self::text($item, 'icon', '✦', 8),
                'title'       => self::text($item, 'title', '', 80),
                'description' => self::text($item, 'description', '', 180),
            );
        }
        if (empty($out['features']['items'])) {
            $out['features']['items'] = $defaults['features']['items'];
        }

        foreach (array('best_sellers', 'new_arrivals', 'on_sale') as $section) {
            $value = isset($input['products'][$section]) && is_array($input['products'][$section]) ? $input['products'][$section] : array();
            $out['products'][$section]['enabled']     = ! empty($value['enabled']);
            $out['products'][$section]['title']       = self::text($value, 'title', $defaults['products'][$section]['title'], 100);
            $out['products'][$section]['description'] = self::text($value, 'description', $defaults['products'][$section]['description'], 180);
            $out['products'][$section]['limit']       = self::number($value, 'limit', 3, 24, $defaults['products'][$section]['limit']);
            $out['products'][$section]['columns']     = self::number($value, 'columns', 2, 5, $defaults['products'][$section]['columns']);
            $out['products'][$section]['autoplay']    = ! empty($value['autoplay']);
        }

        $story = isset($input['story']) && is_array($input['story']) ? $input['story'] : array();
        $out['story']['enabled']     = ! empty($story['enabled']);
        $out['story']['eyebrow']     = self::text($story, 'eyebrow', $defaults['story']['eyebrow'], 100);
        $out['story']['title']       = self::textarea($story, 'title', $defaults['story']['title'], 200);
        $out['story']['description'] = self::textarea($story, 'description', $defaults['story']['description'], 500);
        $out['story']['button_text'] = self::text($story, 'button_text', $defaults['story']['button_text'], 80);
        $out['story']['button_url']  = self::url($story, 'button_url');
        $out['story']['image_id']    = self::id($story, 'image_id');
        $out['story']['image_side']  = self::choice($story, 'image_side', array('left', 'right'), $defaults['story']['image_side']);

        $footer = isset($input['footer']) && is_array($input['footer']) ? $input['footer'] : array();
        $out['footer']['enabled']     = ! empty($footer['enabled']);
        $out['footer']['eyebrow']     = self::text($footer, 'eyebrow', $defaults['footer']['eyebrow'], 100);
        $out['footer']['description'] = self::textarea($footer, 'description', $defaults['footer']['description'], 400);
        $footer_email = isset($footer['email']) && is_scalar($footer['email']) ? (string) $footer['email'] : $defaults['footer']['email'];
        $out['footer']['email']       = sanitize_email($footer_email);
        $out['footer']['phone']       = self::text($footer, 'phone', '', 60);
        $out['footer']['address']     = self::text($footer, 'address', '', 180);
        $out['footer']['image_id']    = self::id($footer, 'image_id');
        $out['footer']['copyright']   = self::text($footer, 'copyright', $defaults['footer']['copyright'], 180);
        $out['footer']['show_menu']   = ! empty($footer['show_menu']);

        $integrations = isset($input['integrations']) && is_array($input['integrations']) ? $input['integrations'] : array();
        $out['integrations']['use_elementor_widget']    = ! empty($integrations['use_elementor_widget']);
        $out['integrations']['use_elementor_locations'] = ! empty($integrations['use_elementor_locations']);
        $out['integrations']['woo_notice_dismissed']    = ! empty($integrations['woo_notice_dismissed']);

        // Admins may add small visual adjustments, but never close the style tag.
        $custom_css = isset($input['custom_css']) && is_scalar($input['custom_css']) ? (string) $input['custom_css'] : '';
        $custom_css = str_replace(array('</style', '<script', '</script', 'javascript:'), '', $custom_css);
        $out['custom_css'] = substr(wp_strip_all_tags($custom_css), 0, 8000);

        return $out;
    }

    /**
     * Return a complete preset configuration.
     *
     * @param string $key Preset key.
     * @return array
     */
    public static function preset($key) {
        $base = self::defaults();
        $key  = sanitize_key($key);

        $presets = array(
            'commerce' => array(
                'design' => array(
                    'primary'      => '#6C4CFF',
                    'secondary'    => '#17152B',
                    'accent'       => '#FFB547',
                    'background'   => '#F7F7FB',
                    'surface'      => '#FFFFFF',
                    'text'         => '#17152B',
                    'muted'        => '#6B7280',
                    'columns'      => 4,
                    'radius'       => 22,
                    'header_style' => 'floating',
                ),
                'hero' => array(
                    'eyebrow'     => 'فصل تازه، انتخاب تازه',
                    'title'       => 'خانه‌ای برای انتخاب‌های دوست‌داشتنی شما',
                    'image_position' => 'right',
                ),
            ),
            'editorial' => array(
                'design' => array(
                    'primary'      => '#B45432',
                    'secondary'    => '#2E2722',
                    'accent'       => '#D9A441',
                    'background'   => '#F6F1E9',
                    'surface'      => '#FFFDF9',
                    'text'         => '#2E2722',
                    'muted'        => '#786E64',
                    'columns'      => 3,
                    'radius'       => 8,
                    'shadow'       => 'none',
                    'header_style' => 'line',
                    'font'         => 'classic',
                ),
                'hero' => array(
                    'eyebrow'        => 'مجله انتخاب شما',
                    'title'          => 'چیزهای خوب، ارزش دیده شدن دارند',
                    'description'    => 'روایت محصولاتی که با دقت، سلیقه و احترام به زندگی روزمره شما انتخاب شده‌اند.',
                    'image_position' => 'left',
                ),
                'products' => array(
                    'best_sellers' => array('title' => 'انتخاب سردبیر', 'description' => 'محصولات محبوب جامعه ما', 'columns' => 3),
                    'new_arrivals' => array('title' => 'تازه از راه رسیده', 'description' => 'داستان‌های جدید فروشگاه', 'columns' => 3),
                    'on_sale'      => array('enabled' => false, 'columns' => 3),
                ),
            ),
            'minimal' => array(
                'design' => array(
                    'primary'      => '#111827',
                    'secondary'    => '#111827',
                    'accent'       => '#22C55E',
                    'background'   => '#FFFFFF',
                    'surface'      => '#F8FAFC',
                    'text'         => '#111827',
                    'muted'        => '#64748B',
                    'columns'      => 3,
                    'radius'       => 12,
                    'shadow'       => 'soft',
                    'header_style' => 'line',
                ),
                'hero' => array(
                    'eyebrow'     => 'کمتر، بهتر',
                    'title'       => 'سادگی، امضای انتخاب‌های خوب است',
                    'description' => 'یک ویترین تمیز و سریع برای برندهایی که به جزئیات اهمیت می‌دهند.',
                ),
                'features' => array('items' => array(
                    array('icon' => '01', 'title' => 'کیفیت', 'description' => 'انتخاب با معیارهای روشن.'),
                    array('icon' => '02', 'title' => 'سرعت', 'description' => 'تجربه‌ای بدون شلوغی.'),
                    array('icon' => '03', 'title' => 'اعتماد', 'description' => 'همراه شما تا پایان.'),
                )),
                'products' => array(
                    'best_sellers' => array('columns' => 3),
                    'new_arrivals' => array('columns' => 3),
                    'on_sale'      => array('columns' => 3),
                ),
            ),
            'marketplace' => array(
                'design' => array(
                    'primary'      => '#0F766E',
                    'secondary'    => '#12343B',
                    'accent'       => '#FB7185',
                    'background'   => '#F0FDFA',
                    'surface'      => '#FFFFFF',
                    'text'         => '#12343B',
                    'muted'        => '#537277',
                    'columns'      => 4,
                    'radius'       => 18,
                    'shadow'       => 'soft',
                    'header_style' => 'solid',
                ),
                'hero' => array(
                    'eyebrow'     => 'بازارچه منتخب',
                    'title'       => 'همه انتخاب‌های خوب، یک‌جا',
                    'description' => 'چند فروشنده، هزاران انتخاب و تجربه‌ای ساده برای پیدا کردن چیزی که دوستش دارید.',
                ),
                'features' => array('items' => array(
                    array('icon' => '✓', 'title' => 'فروشنده‌های معتبر', 'description' => 'هر فروشنده با دقت بررسی می‌شود.'),
                    array('icon' => '⌁', 'title' => 'ارسال منعطف', 'description' => 'روش مناسب خودتان را انتخاب کنید.'),
                    array('icon' => '♢', 'title' => 'پشتیبانی پاسخ‌گو', 'description' => 'در هر مرحله کنار شما هستیم.'),
                )),
            ),
        );

        if (isset($presets[$key])) {
            $base = array_replace_recursive($base, $presets[$key]);
            $base['preset'] = $key;
        }

        return $base;
    }

    /**
     * Apply a preset without deleting identity, menus, or uploaded media.
     *
     * @param string $key     Preset key.
     * @param array  $current Current settings.
     * @return array
     */
    public static function apply_preset($key, $current = array()) {
        $current = array_replace_recursive(self::defaults(), is_array($current) ? $current : array());
        $fresh   = self::preset($key);

        foreach (array('brand_name', 'tagline', 'logo_id', 'menu_id', 'enabled', 'homepage_mode') as $field) {
            if (array_key_exists($field, $current)) {
                $fresh[$field] = $current[$field];
            }
        }
        foreach (array('hero', 'story', 'footer') as $section) {
            foreach (array('image_id') as $field) {
                if (isset($current[$section][$field])) {
                    $fresh[$section][$field] = $current[$section][$field];
                }
            }
        }

        return $fresh;
    }

    private static function text($array, $key, $fallback = '', $length = 255) {
        $raw   = isset($array[$key]) && is_scalar($array[$key]) ? (string) $array[$key] : $fallback;
        $value = sanitize_text_field($raw);
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }

    private static function textarea($array, $key, $fallback = '', $length = 500) {
        $raw   = isset($array[$key]) && is_scalar($array[$key]) ? (string) $array[$key] : $fallback;
        $value = sanitize_textarea_field($raw);
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }

    private static function url($array, $key) {
        return isset($array[$key]) && is_scalar($array[$key]) ? esc_url_raw((string) $array[$key]) : '';
    }

    private static function id($array, $key) {
        return isset($array[$key]) && is_scalar($array[$key]) ? absint($array[$key]) : 0;
    }

    private static function number($array, $key, $min, $max, $fallback) {
        $value = isset($array[$key]) && is_scalar($array[$key]) ? absint($array[$key]) : absint($fallback);
        return max($min, min($max, $value));
    }

    private static function choice($array, $key, $allowed, $fallback) {
        $value = isset($array[$key]) && is_scalar($array[$key]) ? sanitize_key((string) $array[$key]) : $fallback;
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
