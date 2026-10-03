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
    const OPTION        = 'arena_site_studio_options';
    const GROUP         = 'arena_site_studio_group';
    const SCHEMA_VERSION = 1;

    /** @var array|null */
    private static $cache;

    /** @var array|null */
    private static $defaults_cache;

    /**
     * Register the setting with WordPress and migrate an older option once.
     */
    public function register() {
        self::upgrade_stored_option();

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
     * Clear the request cache after an AJAX or admin update.
     */
    public static function clear_cache() {
        self::$cache = null;
    }

    /**
     * Return the complete option object. Defaults are merged so an upgrade
     * never makes a newly added field undefined. Caching prevents repeated
     * calls from performing another options-table lookup during one request.
     *
     * @return array
     */
    public static function get() {
        if (null !== self::$cache) {
            return self::$cache;
        }

        $saved = get_option(self::OPTION, array());
        $saved = is_array($saved) ? self::migrate($saved) : array();

        // Normalize stored values on read as well as on save. This keeps a
        // hand-edited or very old option from turning a missing/scalar field
        // into a PHP warning on the front end. This never writes in a front-
        // end request; the result is cached for the rest of the request.
        self::$cache = self::sanitize($saved, false, $saved);
        self::$cache['schema_version'] = self::SCHEMA_VERSION;
        return self::$cache;
    }

    /**
     * Defaults are deliberately useful in Persian and do not depend on a
     * particular theme. An admin can publish them with one switch.
     *
     * @return array
     */
    public static function defaults() {
        if (null !== self::$defaults_cache) {
            return self::$defaults_cache;
        }

        self::$defaults_cache = array(
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
            'schema_version' => self::SCHEMA_VERSION,
        );

        return self::$defaults_cache;
    }

    /**
     * Sanitize the nested settings object. Explicit whitelists prevent a
     * pasted option object from becoming executable markup or CSS.
     *
     * @param mixed $input       Raw settings.
     * @param bool  $unslash     Whether the value came from a WordPress request.
     * @param mixed $stored_base Optional already-read stored settings.
     * @return array
     */
    public static function sanitize($input, $unslash = true, $stored_base = null) {
        $defaults = self::defaults();
        $saved    = null === $stored_base ? get_option(self::OPTION, array()) : $stored_base;
        $saved    = is_array($saved) ? self::migrate($saved) : array();
        $base  = self::merge_with_defaults($saved);
        $input = is_array($input) ? ($unslash ? wp_unslash($input) : $input) : array();
        $out   = $defaults;

        $out['enabled']       = self::flag($input, 'enabled', ! empty($base['enabled']));
        $out['preset']        = self::choice($input, 'preset', array('commerce', 'editorial', 'minimal', 'marketplace'), $base['preset']);
        $out['homepage_mode'] = self::choice($input, 'homepage_mode', array('canvas', 'content', 'disabled'), $base['homepage_mode']);
        $out['brand_name']    = self::text($input, 'brand_name', $base['brand_name'], 120);
        $out['tagline']       = self::text($input, 'tagline', $base['tagline'], 180);
        $out['logo_id']       = self::id($input, 'logo_id', $base['logo_id']);
        $out['menu_id']       = self::id($input, 'menu_id', $base['menu_id']);

        $header = isset($input['header']) && is_array($input['header']) ? $input['header'] : array();
        $out['header']['announcement_enabled'] = self::flag($header, 'announcement_enabled', ! empty($base['header']['announcement_enabled']));
        $out['header']['announcement']         = self::text($header, 'announcement', $base['header']['announcement'], 180);
        $out['header']['sticky']               = self::flag($header, 'sticky', ! empty($base['header']['sticky']));
        $out['header']['show_search']          = self::flag($header, 'show_search', ! empty($base['header']['show_search']));
        $out['header']['show_cart']            = self::flag($header, 'show_cart', ! empty($base['header']['show_cart']));
        $out['header']['show_account']         = self::flag($header, 'show_account', ! empty($base['header']['show_account']));
        $out['header']['cta_enabled']          = self::flag($header, 'cta_enabled', ! empty($base['header']['cta_enabled']));
        $out['header']['cta_text']             = self::text($header, 'cta_text', $base['header']['cta_text'], 80);
        $out['header']['cta_url']              = self::url($header, 'cta_url', $base['header']['cta_url']);

        $design = isset($input['design']) && is_array($input['design']) ? $input['design'] : array();
        foreach (array('primary', 'secondary', 'accent', 'background', 'surface', 'text', 'muted') as $color) {
            $raw       = isset($design[$color]) && is_scalar($design[$color]) ? (string) $design[$color] : '';
            $value     = sanitize_hex_color($raw);
            $base_color = isset($base['design'][$color]) && is_scalar($base['design'][$color]) ? sanitize_hex_color($base['design'][$color]) : false;
            $out['design'][$color] = $value ? $value : ($base_color ? $base_color : $defaults['design'][$color]);
        }
        $out['design']['container']    = self::number($design, 'container', 960, 1600, $base['design']['container']);
        $out['design']['columns']      = self::number($design, 'columns', 2, 5, $base['design']['columns']);
        $out['design']['radius']       = self::number($design, 'radius', 0, 48, $base['design']['radius']);
        $out['design']['shadow']       = self::choice($design, 'shadow', array('none', 'soft', 'strong'), $base['design']['shadow']);
        $out['design']['font']         = self::choice($design, 'font', array('system', 'modern', 'classic'), $base['design']['font']);
        $out['design']['header_style'] = self::choice($design, 'header_style', array('floating', 'line', 'solid'), $base['design']['header_style']);

        $hero = isset($input['hero']) && is_array($input['hero']) ? $input['hero'] : array();
        $out['hero']['enabled']        = self::flag($hero, 'enabled', ! empty($base['hero']['enabled']));
        $out['hero']['eyebrow']        = self::text($hero, 'eyebrow', $base['hero']['eyebrow'], 120);
        $out['hero']['title']          = self::textarea($hero, 'title', $base['hero']['title'], 220);
        $out['hero']['description']    = self::textarea($hero, 'description', $base['hero']['description'], 500);
        $out['hero']['button_text']    = self::text($hero, 'button_text', $base['hero']['button_text'], 80);
        $out['hero']['button_url']     = self::url($hero, 'button_url', $base['hero']['button_url']);
        $out['hero']['secondary_text'] = self::text($hero, 'secondary_text', $base['hero']['secondary_text'], 80);
        $out['hero']['secondary_url']  = self::url($hero, 'secondary_url', $base['hero']['secondary_url']);
        $out['hero']['image_id']       = self::id($hero, 'image_id', $base['hero']['image_id']);
        $out['hero']['image_position'] = self::choice($hero, 'image_position', array('left', 'right'), $base['hero']['image_position']);

        $features = isset($input['features']) && is_array($input['features']) ? $input['features'] : array();
        $out['features']['enabled'] = self::flag($features, 'enabled', ! empty($base['features']['enabled']));
        $items = array_key_exists('items', $features) && is_array($features['items']) ? array_slice($features['items'], 0, 4) : $base['features']['items'];
        $out['features']['items'] = array();
        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $feature_base = isset($base['features']['items'][$index]) && is_array($base['features']['items'][$index]) ? array_replace_recursive(array('icon' => '✦', 'title' => '', 'description' => ''), $base['features']['items'][$index]) : array('icon' => '✦', 'title' => '', 'description' => '');
            $out['features']['items'][] = array(
                'icon'        => self::text($item, 'icon', $feature_base['icon'], 8),
                'title'       => self::text($item, 'title', $feature_base['title'], 80),
                'description' => self::text($item, 'description', $feature_base['description'], 180),
            );
        }
        if (empty($out['features']['items'])) {
            $out['features']['items'] = $base['features']['items'];
        }

        $products_input = isset($input['products']) && is_array($input['products']) ? $input['products'] : array();
        foreach (array('best_sellers', 'new_arrivals', 'on_sale') as $section) {
            $value = isset($products_input[$section]) && is_array($products_input[$section]) ? $products_input[$section] : array();
            $section_base = $base['products'][$section];
            $out['products'][$section]['enabled']     = self::flag($value, 'enabled', ! empty($section_base['enabled']));
            $out['products'][$section]['title']       = self::text($value, 'title', $section_base['title'], 100);
            $out['products'][$section]['description'] = self::text($value, 'description', $section_base['description'], 180);
            $out['products'][$section]['limit']       = self::number($value, 'limit', 3, 24, $section_base['limit']);
            $out['products'][$section]['columns']     = self::number($value, 'columns', 2, 5, $section_base['columns']);
            $out['products'][$section]['autoplay']    = self::flag($value, 'autoplay', ! empty($section_base['autoplay']));
        }

        $story = isset($input['story']) && is_array($input['story']) ? $input['story'] : array();
        $out['story']['enabled']     = self::flag($story, 'enabled', ! empty($base['story']['enabled']));
        $out['story']['eyebrow']     = self::text($story, 'eyebrow', $base['story']['eyebrow'], 100);
        $out['story']['title']       = self::textarea($story, 'title', $base['story']['title'], 200);
        $out['story']['description'] = self::textarea($story, 'description', $base['story']['description'], 500);
        $out['story']['button_text'] = self::text($story, 'button_text', $base['story']['button_text'], 80);
        $out['story']['button_url']  = self::url($story, 'button_url', $base['story']['button_url']);
        $out['story']['image_id']    = self::id($story, 'image_id', $base['story']['image_id']);
        $out['story']['image_side']  = self::choice($story, 'image_side', array('left', 'right'), $base['story']['image_side']);

        $footer = isset($input['footer']) && is_array($input['footer']) ? $input['footer'] : array();
        $out['footer']['enabled']     = self::flag($footer, 'enabled', ! empty($base['footer']['enabled']));
        $out['footer']['eyebrow']     = self::text($footer, 'eyebrow', $base['footer']['eyebrow'], 100);
        $out['footer']['description'] = self::textarea($footer, 'description', $base['footer']['description'], 400);
        $footer_email = isset($footer['email']) && is_scalar($footer['email']) ? (string) $footer['email'] : (is_scalar($base['footer']['email']) ? (string) $base['footer']['email'] : '');
        $out['footer']['email']       = sanitize_email($footer_email);
        $out['footer']['phone']       = self::text($footer, 'phone', $base['footer']['phone'], 60);
        $out['footer']['address']     = self::text($footer, 'address', $base['footer']['address'], 180);
        $out['footer']['image_id']    = self::id($footer, 'image_id', $base['footer']['image_id']);
        $out['footer']['copyright']   = self::text($footer, 'copyright', $base['footer']['copyright'], 180);
        $out['footer']['show_menu']   = self::flag($footer, 'show_menu', ! empty($base['footer']['show_menu']));

        $integrations = isset($input['integrations']) && is_array($input['integrations']) ? $input['integrations'] : array();
        $out['integrations']['use_elementor_widget']    = self::flag($integrations, 'use_elementor_widget', ! empty($base['integrations']['use_elementor_widget']));
        $out['integrations']['use_elementor_locations'] = self::flag($integrations, 'use_elementor_locations', ! empty($base['integrations']['use_elementor_locations']));
        $out['integrations']['woo_notice_dismissed']    = self::flag($integrations, 'woo_notice_dismissed', ! empty($base['integrations']['woo_notice_dismissed']));

        // Admins may add small visual adjustments, but never close the style tag.
        $custom_css = isset($input['custom_css']) && is_scalar($input['custom_css']) ? (string) $input['custom_css'] : (is_scalar($base['custom_css']) ? (string) $base['custom_css'] : '');
        $custom_css = preg_replace('/<\/?style|<script|<\/script|javascript\s*:/i', '', $custom_css);
        $out['custom_css'] = substr(wp_strip_all_tags($custom_css), 0, 8000);
        $out['schema_version'] = self::SCHEMA_VERSION;

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
        $key  = is_scalar($key) ? sanitize_key((string) $key) : '';

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
        $key      = is_scalar($key) ? sanitize_key((string) $key) : '';
        $key      = in_array($key, array('commerce', 'editorial', 'minimal', 'marketplace'), true) ? $key : 'commerce';
        $current  = is_array($current) ? self::sanitize($current, false, $current) : self::defaults();
        $fresh    = self::preset($key);
        $defaults = self::defaults();

        // A preset is a visual starting point, not a destructive import. Keep
        // identity, publish mode, navigation, uploaded media and any text a
        // site owner has already customized. Curated preset copy is still
        // used when the corresponding value is untouched at its default.
        foreach (array('brand_name', 'tagline', 'logo_id', 'menu_id', 'enabled', 'homepage_mode') as $field) {
            if (array_key_exists($field, $current)) {
                $fresh[$field] = $current[$field];
            }
        }
        foreach (array('header', 'hero', 'features', 'products', 'story', 'footer', 'integrations') as $section) {
            if (isset($current[$section], $fresh[$section], $defaults[$section])) {
                $fresh[$section] = self::preserve_custom_values($fresh[$section], $current[$section], $defaults[$section]);
            }
        }
        if (! empty($current['custom_css'])) {
            $fresh['custom_css'] = $current['custom_css'];
        }
        // Content width is a site-level constraint rather than a visual
        // preset choice, so changing the preset must not unexpectedly resize
        // an established layout.
        $fresh['design']['container'] = $current['design']['container'];
        $fresh['preset'] = sanitize_key((string) $key);

        return $fresh;
    }

    /**
     * Preserve only values that differ from the original defaults. This lets
     * a preset provide its own untouched copy while retaining real edits.
     */
    private static function preserve_custom_values($fresh, $current, $defaults) {
        if (! is_array($fresh) || ! is_array($current)) {
            return $fresh;
        }
        foreach ($current as $field => $value) {
            $default = is_array($defaults) && array_key_exists($field, $defaults) ? $defaults[$field] : null;
            if (is_array($value)) {
                $fresh[$field] = self::preserve_custom_values(
                    isset($fresh[$field]) && is_array($fresh[$field]) ? $fresh[$field] : array(),
                    $value,
                    is_array($default) ? $default : array()
                );
            } elseif (! is_array($default) || $value !== $default) {
                $fresh[$field] = $value;
            }
        }
        return $fresh;
    }

    private static function merge_with_defaults($saved) {
        $defaults = self::defaults();
        $base     = array_replace_recursive($defaults, is_array($saved) ? $saved : array());

        foreach (array('header', 'design', 'hero', 'features', 'products', 'story', 'footer', 'integrations') as $section) {
            if (! isset($base[$section]) || ! is_array($base[$section])) {
                $base[$section] = $defaults[$section];
            }
        }
        if (! isset($base['features']['items']) || ! is_array($base['features']['items'])) {
            $base['features']['items'] = $defaults['features']['items'];
        } else {
            foreach ($base['features']['items'] as $index => $item) {
                if (! is_array($item)) {
                    $base['features']['items'][$index] = isset($defaults['features']['items'][$index]) ? $defaults['features']['items'][$index] : array('icon' => '✦', 'title' => '', 'description' => '');
                }
            }
            if (empty($base['features']['items'])) {
                $base['features']['items'] = $defaults['features']['items'];
            }
        }
        $base['features']['items'] = array_slice($base['features']['items'], 0, 4);
        foreach (array('best_sellers', 'new_arrivals', 'on_sale') as $section) {
            if (! isset($base['products'][$section]) || ! is_array($base['products'][$section])) {
                $base['products'][$section] = $defaults['products'][$section];
            }
        }

        return $base;
    }

    /**
     * Add newly introduced fields without destroying an existing design.
     *
     * @param array $saved Stored settings.
     * @return array
     */
    private static function migrate($saved) {
        $version = isset($saved['schema_version']) && is_scalar($saved['schema_version']) ? absint($saved['schema_version']) : 0;
        if ($version < self::SCHEMA_VERSION) {
            $saved['schema_version'] = self::SCHEMA_VERSION;
        }
        return $saved;
    }

    /**
     * Persist the migration once during admin bootstrap. Front-end requests
     * only merge defaults and never perform a write.
     */
    private static function upgrade_stored_option() {
        $saved = get_option(self::OPTION, false);
        if (! is_array($saved)) {
            return;
        }
        $version = isset($saved['schema_version']) && is_scalar($saved['schema_version']) ? absint($saved['schema_version']) : 0;
        if ($version >= self::SCHEMA_VERSION) {
            return;
        }

        $migrated = array_replace_recursive(self::defaults(), self::migrate($saved));
        update_option(self::OPTION, self::sanitize($migrated, false));
        self::clear_cache();
    }

    private static function flag($array, $key, $fallback = false) {
        return array_key_exists($key, $array) ? (is_scalar($array[$key]) && ! empty($array[$key])) : (bool) $fallback;
    }

    private static function text($array, $key, $fallback = '', $length = 255) {
        $fallback = is_scalar($fallback) ? (string) $fallback : '';
        $raw      = isset($array[$key]) && is_scalar($array[$key]) ? (string) $array[$key] : $fallback;
        $value    = sanitize_text_field($raw);
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }

    private static function textarea($array, $key, $fallback = '', $length = 500) {
        $fallback = is_scalar($fallback) ? (string) $fallback : '';
        $raw      = isset($array[$key]) && is_scalar($array[$key]) ? (string) $array[$key] : $fallback;
        $value    = sanitize_textarea_field($raw);
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }

    private static function url($array, $key, $fallback = '') {
        $fallback = is_scalar($fallback) ? (string) $fallback : '';
        return isset($array[$key]) && is_scalar($array[$key]) ? esc_url_raw((string) $array[$key]) : esc_url_raw($fallback);
    }

    private static function id($array, $key, $fallback = 0) {
        $fallback = is_scalar($fallback) ? absint($fallback) : 0;
        return isset($array[$key]) && is_scalar($array[$key]) ? absint($array[$key]) : $fallback;
    }

    private static function number($array, $key, $min, $max, $fallback) {
        $fallback = is_scalar($fallback) ? absint($fallback) : $min;
        $value    = isset($array[$key]) && is_scalar($array[$key]) ? absint($array[$key]) : $fallback;
        return max($min, min($max, $value));
    }

    private static function choice($array, $key, $allowed, $fallback) {
        $fallback = is_scalar($fallback) ? sanitize_key((string) $fallback) : '';
        if (! in_array($fallback, $allowed, true)) {
            $fallback = reset($allowed);
        }
        $value = isset($array[$key]) && is_scalar($array[$key]) ? sanitize_key((string) $array[$key]) : $fallback;
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
