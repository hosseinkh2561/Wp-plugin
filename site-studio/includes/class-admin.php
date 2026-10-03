<?php
/**
 * Admin screen and actions for Site Studio.
 *
 * @package ArenaSiteStudio
 */

namespace Arena\SiteStudio;

if (! defined('ABSPATH')) {
    exit;
}

final class Admin {
    /** @var Settings */
    private $settings;

    /** @var Renderer */
    private $renderer;

    /** @var Elementor */
    private $elementor;

    /** @var string */
    private $page_hook = '';

    public function __construct(Settings $settings, Renderer $renderer, Elementor $elementor) {
        $this->settings  = $settings;
        $this->renderer  = $renderer;
        $this->elementor = $elementor;
        add_action('wp_ajax_arena_site_studio_apply_preset', array($this, 'apply_preset'));
    }

    public function menu() {
        $this->page_hook = add_menu_page(
            'استودیو پوسته سایت',
            'استودیو پوسته',
            'manage_options',
            'arena-site-studio',
            array($this, 'page'),
            'dashicons-layout',
            58
        );
    }

    public function assets($hook) {
        if ($hook !== $this->page_hook && 'toplevel_page_arena-site-studio' !== $hook) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style(
            'arena-site-studio-admin',
            ARENA_SITE_STUDIO_URL . 'assets/css/admin.css',
            array(),
            ARENA_SITE_STUDIO_VERSION
        );
        wp_enqueue_script(
            'arena-site-studio-admin',
            ARENA_SITE_STUDIO_URL . 'assets/js/admin.js',
            array('jquery'),
            ARENA_SITE_STUDIO_VERSION,
            true
        );
        wp_localize_script(
            'arena-site-studio-admin',
            'ArenaSiteStudioAdmin',
            array(
                'ajaxUrl'      => admin_url('admin-ajax.php'),
                'nonce'        => wp_create_nonce('arena_site_studio_admin'),
                'homeUrl'      => home_url('/'),
                'successLabel' => 'ذخیره شد',
                'errorLabel'   => 'خطایی رخ داد',
            )
        );
    }

    public function notices() {
        if (! current_user_can('manage_options')) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (! $screen || 'toplevel_page_arena-site-studio' !== $screen->id) {
            return;
        }
        $options = $this->settings->get();
        if (empty($options['enabled'])) {
            echo '<div class="notice notice-info is-dismissible"><p><strong>استودیو پوسته آماده است.</strong> تنظیمات را انجام دهید و گزینه «انتشار استودیو در صفحه اصلی» را فعال کنید.</p></div>';
        }
        if (isset($_GET['settings-updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>طراحی سایت با موفقیت ذخیره شد.</p></div>';
        }
    }

    public function plugin_links($links) {
        array_unshift($links, '<a href="' . esc_url(admin_url('admin.php?page=arena-site-studio')) . '">تنظیمات استودیو</a>');
        return $links;
    }

    /**
     * Main settings screen.
     */
    public function page() {
        if (! current_user_can('manage_options')) {
            wp_die('شما اجازه دسترسی به این صفحه را ندارید.');
        }

        $s          = $this->settings->get();
        $elementor  = $this->elementor->is_active();
        $installed  = $this->elementor->is_installed();
        $woocommerce = defined('WC_VERSION') || class_exists('WooCommerce');
        $menus      = wp_get_nav_menus();
        $home_url   = home_url('/');
        ?>
        <div class="wrap arena-studio-admin" dir="rtl">
            <div class="studio-admin-header">
                <div>
                    <span class="studio-admin-eyebrow">SITE STUDIO / <?php echo esc_html(ARENA_SITE_STUDIO_VERSION); ?></span>
                    <h1>استودیو پوسته سایت</h1>
                    <p>یک مرکز کنترل متمرکز برای ساخت هدر، صفحه اصلی، ویترین محصولات و فوتر؛ بدون درگیری با تنظیمات پراکنده قالب.</p>
                </div>
                <div class="studio-admin-header-actions">
                    <a class="button button-secondary" href="<?php echo esc_url($home_url); ?>" target="_blank" rel="noopener">پیش‌نمایش سایت <span aria-hidden="true">↗</span></a>
                    <a class="button button-primary" href="#studio-save">ذخیره تغییرات</a>
                </div>
            </div>

            <div class="studio-status-grid">
                <div class="studio-status-card <?php echo $woocommerce ? 'is-ready' : 'is-muted'; ?>">
                    <span class="studio-status-icon">▦</span><div><strong>فروشگاه محصولات</strong><small><?php echo $woocommerce ? 'WooCommerce فعال است؛ اسلایدرها آماده‌اند.' : 'برای نمایش محصولات، WooCommerce را نصب کنید.'; ?></small></div>
                    <?php if (! $woocommerce) : ?><a href="<?php echo esc_url(admin_url('plugin-install.php?s=woocommerce&tab=search&type=term')); ?>">نصب</a><?php endif; ?>
                </div>
                <div class="studio-status-card <?php echo $elementor ? 'is-ready' : 'is-muted'; ?>">
                    <span class="studio-status-icon">✦</span><div><strong>اتصال به Elementor</strong><small><?php echo $elementor ? 'ویجت استودیو در Elementor فعال است.' : ($installed ? 'Elementor نصب است و باید فعال شود.' : 'اختیاری است؛ استودیو مستقل هم کار می‌کند.'); ?></small></div>
                    <?php if (! $elementor) : ?><button type="button" class="link-button" data-studio-elementor><?php echo $installed ? 'فعال‌سازی' : 'نصب و فعال‌سازی'; ?></button><?php endif; ?>
                </div>
                <div class="studio-status-card <?php echo ! empty($s['enabled']) ? 'is-ready' : 'is-muted'; ?>">
                    <span class="studio-status-icon">◉</span><div><strong>وضعیت انتشار</strong><small><?php echo ! empty($s['enabled']) ? 'استودیو روی صفحه اصلی منتشر است.' : 'پیش‌نمایش خصوصی؛ هنوز منتشر نشده.'; ?></small></div>
                    <span class="studio-status-dot"></span>
                </div>
            </div>

            <div class="studio-preset-bar">
                <div><strong>شروع سریع با چیدمان آماده</strong><span>یک سبک را انتخاب کنید؛ تصاویر و هویت برند شما حفظ می‌شود.</span></div>
                <div class="studio-preset-buttons">
                    <?php foreach ($this->presets() as $key => $label) : ?>
                        <button type="button" class="studio-preset-button<?php echo $s['preset'] === $key ? ' is-active' : ''; ?>" data-studio-preset="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></button>
                    <?php endforeach; ?>
                </div>
                <span class="studio-save-feedback" data-studio-feedback aria-live="polite"></span>
            </div>

            <form action="options.php" method="post" class="studio-settings-form">
                <?php settings_fields(Settings::GROUP); ?>
                <input type="hidden" id="studio-preset-value" name="<?php echo esc_attr($this->name('preset')); ?>" value="<?php echo esc_attr($s['preset']); ?>">
                <div class="studio-admin-layout">
                    <main class="studio-admin-main">
                        <section class="studio-admin-card studio-publish-card" id="studio-save">
                            <div class="studio-card-heading"><div><span class="studio-step">۰۱</span><h2>انتشار و هویت برند</h2><p>هسته اصلی استودیو را روشن کنید و نامی را که در هدر دیده می‌شود وارد کنید.</p></div><span class="studio-card-symbol">◉</span></div>
                            <?php echo $this->checkbox('enabled', ! empty($s['enabled']), 'انتشار استودیو در صفحه اصلی', 'با فعال کردن، صفحه اصلی با چیدمان استودیو نمایش داده می‌شود. در حالت خاموش، سایت فعلی شما بدون تغییر باقی می‌ماند.'); ?>
                            <div class="studio-grid studio-grid-3">
                                <div class="studio-field"><label for="studio-brand-name">نام برند / سایت</label><input id="studio-brand-name" type="text" name="<?php echo esc_attr($this->name('brand_name')); ?>" value="<?php echo esc_attr($s['brand_name']); ?>" placeholder="نام فروشگاه شما"></div>
                                <div class="studio-field"><label for="studio-tagline">شعار کوتاه</label><input id="studio-tagline" type="text" name="<?php echo esc_attr($this->name('tagline')); ?>" value="<?php echo esc_attr($s['tagline']); ?>"></div>
                                <div class="studio-field"><label for="studio-homepage-mode">روش نمایش</label><select id="studio-homepage-mode" name="<?php echo esc_attr($this->name('homepage_mode')); ?>"><option value="canvas" <?php selected($s['homepage_mode'], 'canvas'); ?>>صفحه کامل — کنترل هدر و فوتر با استودیو</option><option value="content" <?php selected($s['homepage_mode'], 'content'); ?>>داخل قالب فعلی — فقط محتوای استودیو</option><option value="disabled" <?php selected($s['homepage_mode'], 'disabled'); ?>>غیرفعال</option></select></div>
                            </div>
                            <div class="studio-grid studio-grid-2">
                                <?php echo $this->media_field('logo_id', $s['logo_id'], 'لوگوی هدر', 'تصویر لوگو در هدر. اگر خالی باشد، نام برند به صورت متنی نمایش داده می‌شود.'); ?>
                                <div class="studio-field"><label for="studio-menu">منوی اصلی</label><select id="studio-menu" name="<?php echo esc_attr($this->name('menu_id')); ?>"><option value="0">منوی خودکار استودیو</option><?php foreach ($menus as $menu) : ?><option value="<?php echo esc_attr($menu->term_id); ?>" <?php selected($s['menu_id'], $menu->term_id); ?>><?php echo esc_html($menu->name); ?></option><?php endforeach; ?></select><small>برای کنترل کامل لینک‌ها، یک فهرست وردپرس انتخاب کنید.</small></div>
                            </div>
                        </section>

                        <section class="studio-admin-card" id="studio-design">
                            <div class="studio-card-heading"><div><span class="studio-step">۰۲</span><h2>رنگ، چیدمان و ظاهر</h2><p>تعداد ستون‌ها، عرض سایت، گوشه کارت‌ها و رنگ‌های اصلی را بدون کدنویسی کنترل کنید.</p></div><span class="studio-card-symbol">◌</span></div>
                            <div class="studio-color-grid">
                                <?php foreach (array('primary' => 'رنگ اصلی', 'secondary' => 'رنگ تیره', 'accent' => 'رنگ تأکیدی', 'background' => 'پس‌زمینه', 'surface' => 'سطح کارت', 'text' => 'متن', 'muted' => 'متن کم‌رنگ') as $key => $label) : ?>
                                    <label class="studio-color-field"><span><?php echo esc_html($label); ?></span><input type="color" name="<?php echo esc_attr($this->name(array('design', $key))); ?>" value="<?php echo esc_attr($s['design'][$key]); ?>"><code><?php echo esc_html($s['design'][$key]); ?></code></label>
                                <?php endforeach; ?>
                            </div>
                            <div class="studio-grid studio-grid-4 studio-design-options">
                                <div class="studio-field"><label for="studio-container">عرض محتوای سایت</label><input id="studio-container" type="number" min="960" max="1600" step="10" name="<?php echo esc_attr($this->name(array('design', 'container'))); ?>" value="<?php echo esc_attr($s['design']['container']); ?>"><small>پیکسل</small></div>
                                <div class="studio-field"><label for="studio-columns">تعداد ستون محصولات</label><select id="studio-columns" name="<?php echo esc_attr($this->name(array('design', 'columns'))); ?>"><?php for ($i = 2; $i <= 5; $i++) : ?><option value="<?php echo esc_attr($i); ?>" <?php selected($s['design']['columns'], $i); ?>><?php echo esc_html($i); ?> ستونه</option><?php endfor; ?></select></div>
                                <div class="studio-field"><label for="studio-radius">گردی کارت‌ها</label><input id="studio-radius" type="range" min="0" max="48" name="<?php echo esc_attr($this->name(array('design', 'radius'))); ?>" value="<?php echo esc_attr($s['design']['radius']); ?>" data-range-output="#studio-radius-value"><output id="studio-radius-value"><?php echo esc_html($s['design']['radius']); ?>px</output></div>
                                <div class="studio-field"><label for="studio-header-style">سبک هدر</label><select id="studio-header-style" name="<?php echo esc_attr($this->name(array('design', 'header_style'))); ?>"><option value="floating" <?php selected($s['design']['header_style'], 'floating'); ?>>شناور و مدرن</option><option value="line" <?php selected($s['design']['header_style'], 'line'); ?>>خطی و مینیمال</option><option value="solid" <?php selected($s['design']['header_style'], 'solid'); ?>>پررنگ و یکپارچه</option></select></div>
                            </div>
                            <div class="studio-grid studio-grid-2"><div class="studio-field"><label for="studio-font">حس تایپوگرافی</label><select id="studio-font" name="<?php echo esc_attr($this->name(array('design', 'font'))); ?>"><option value="system" <?php selected($s['design']['font'], 'system'); ?>>سیستم و خوانا</option><option value="modern" <?php selected($s['design']['font'], 'modern'); ?>>مدرن و ساده</option><option value="classic" <?php selected($s['design']['font'], 'classic'); ?>>کلاسیک و تحریری</option></select></div><div class="studio-field"><label for="studio-shadow">سایه کارت‌ها</label><select id="studio-shadow" name="<?php echo esc_attr($this->name(array('design', 'shadow'))); ?>"><option value="none" <?php selected($s['design']['shadow'], 'none'); ?>>بدون سایه</option><option value="soft" <?php selected($s['design']['shadow'], 'soft'); ?>>نرم</option><option value="strong" <?php selected($s['design']['shadow'], 'strong'); ?>>پررنگ</option></select></div></div>
                        </section>

                        <section class="studio-admin-card" id="studio-header">
                            <div class="studio-card-heading"><div><span class="studio-step">۰۳</span><h2>هدر و منوی سایت</h2><p>نوار اطلاع‌رسانی، دکمه اصلی، جستجو و دسترسی‌های هدر را تنظیم کنید.</p></div><span class="studio-card-symbol">⌂</span></div>
                            <div class="studio-grid studio-grid-2">
                                <?php echo $this->checkbox(array('header', 'announcement_enabled'), ! empty($s['header']['announcement_enabled']), 'نمایش نوار اطلاع‌رسانی'); ?>
                                <?php echo $this->checkbox(array('header', 'sticky'), ! empty($s['header']['sticky']), 'چسبان شدن هدر هنگام اسکرول'); ?>
                                <?php echo $this->checkbox(array('header', 'show_search'), ! empty($s['header']['show_search']), 'نمایش جستجو'); ?>
                                <?php echo $this->checkbox(array('header', 'show_cart'), ! empty($s['header']['show_cart']), 'نمایش سبد خرید'); ?>
                                <?php echo $this->checkbox(array('header', 'show_account'), ! empty($s['header']['show_account']), 'نمایش حساب کاربری'); ?>
                                <?php echo $this->checkbox(array('header', 'cta_enabled'), ! empty($s['header']['cta_enabled']), 'نمایش دکمه اقدام اصلی'); ?>
                                <?php echo $this->checkbox(array('integrations', 'use_elementor_locations'), ! empty($s['integrations']['use_elementor_locations']), 'استفاده از هدر/فوتر Elementor Pro', 'اگر Theme Builder هدر یا فوتر دارد، در صورت وجود همان را به‌جای نسخه استودیو نمایش می‌دهد.'); ?>
                            </div>
                            <div class="studio-grid studio-grid-3"><div class="studio-field studio-field-wide"><label>متن نوار اطلاع‌رسانی</label><input type="text" name="<?php echo esc_attr($this->name(array('header', 'announcement'))); ?>" value="<?php echo esc_attr($s['header']['announcement']); ?>"></div><div class="studio-field"><label>متن دکمه هدر</label><input type="text" name="<?php echo esc_attr($this->name(array('header', 'cta_text'))); ?>" value="<?php echo esc_attr($s['header']['cta_text']); ?>"></div><div class="studio-field"><label>لینک دکمه هدر</label><input type="url" name="<?php echo esc_attr($this->name(array('header', 'cta_url'))); ?>" value="<?php echo esc_attr($s['header']['cta_url']); ?>" placeholder="https://"></div></div>
                        </section>

                        <section class="studio-admin-card" id="studio-hero">
                            <div class="studio-card-heading"><div><span class="studio-step">۰۴</span><h2>قهرمان صفحه و معرفی سایت</h2><p>اولین چیزی که مخاطب می‌بیند؛ تیتر، توضیحات، دو CTA و تصویر اصلی را بسازید.</p></div><span class="studio-card-symbol">✧</span></div>
                            <?php echo $this->checkbox(array('hero', 'enabled'), ! empty($s['hero']['enabled']), 'نمایش بخش معرفی اصلی'); ?>
                            <div class="studio-grid studio-grid-2"><div class="studio-field"><label>برچسب بالای تیتر</label><input type="text" name="<?php echo esc_attr($this->name(array('hero', 'eyebrow'))); ?>" value="<?php echo esc_attr($s['hero']['eyebrow']); ?>"></div><div class="studio-field"><label>جای تصویر</label><select name="<?php echo esc_attr($this->name(array('hero', 'image_position'))); ?>"><option value="right" <?php selected($s['hero']['image_position'], 'right'); ?>>سمت راست</option><option value="left" <?php selected($s['hero']['image_position'], 'left'); ?>>سمت چپ</option></select></div></div>
                            <div class="studio-field"><label>تیتر اصلی</label><textarea rows="2" name="<?php echo esc_attr($this->name(array('hero', 'title'))); ?>"><?php echo esc_textarea($s['hero']['title']); ?></textarea></div><div class="studio-field"><label>توضیحات معرفی</label><textarea rows="3" name="<?php echo esc_attr($this->name(array('hero', 'description'))); ?>"><?php echo esc_textarea($s['hero']['description']); ?></textarea></div>
                            <div class="studio-grid studio-grid-4"><div class="studio-field"><label>متن دکمه اول</label><input type="text" name="<?php echo esc_attr($this->name(array('hero', 'button_text'))); ?>" value="<?php echo esc_attr($s['hero']['button_text']); ?>"></div><div class="studio-field"><label>لینک دکمه اول</label><input type="url" name="<?php echo esc_attr($this->name(array('hero', 'button_url'))); ?>" value="<?php echo esc_attr($s['hero']['button_url']); ?>"></div><div class="studio-field"><label>متن لینک دوم</label><input type="text" name="<?php echo esc_attr($this->name(array('hero', 'secondary_text'))); ?>" value="<?php echo esc_attr($s['hero']['secondary_text']); ?>"></div><div class="studio-field"><label>لینک دوم</label><input type="text" name="<?php echo esc_attr($this->name(array('hero', 'secondary_url'))); ?>" value="<?php echo esc_attr($s['hero']['secondary_url']); ?>"></div></div>
                            <?php echo $this->media_field(array('hero', 'image_id'), $s['hero']['image_id'], 'تصویر اصلی معرفی', 'یک تصویر افقی یا مربعی باکیفیت انتخاب کنید.'); ?>
                        </section>

                        <section class="studio-admin-card" id="studio-benefits">
                            <div class="studio-card-heading"><div><span class="studio-step">۰۵</span><h2>مزیت‌ها و اعتمادسازی</h2><p>سه یا چهار دلیل کوتاه برای اینکه مخاطب با خیال راحت جلو بیاید.</p></div><span class="studio-card-symbol">♡</span></div>
                            <?php echo $this->checkbox(array('features', 'enabled'), ! empty($s['features']['enabled']), 'نمایش نوار مزیت‌ها'); ?>
                            <div class="studio-feature-editor">
                                <?php foreach ($s['features']['items'] as $index => $feature) : ?><div class="studio-feature-edit"><div class="studio-field"><label>آیکن</label><input maxlength="8" type="text" name="<?php echo esc_attr($this->name(array('features', 'items', $index, 'icon'))); ?>" value="<?php echo esc_attr($feature['icon']); ?>"></div><div class="studio-field"><label>عنوان</label><input type="text" name="<?php echo esc_attr($this->name(array('features', 'items', $index, 'title'))); ?>" value="<?php echo esc_attr($feature['title']); ?>"></div><div class="studio-field"><label>توضیح</label><input type="text" name="<?php echo esc_attr($this->name(array('features', 'items', $index, 'description'))); ?>" value="<?php echo esc_attr($feature['description']); ?>"></div></div><?php endforeach; ?>
                            </div>
                        </section>

                        <section class="studio-admin-card" id="studio-products">
                            <div class="studio-card-heading"><div><span class="studio-step">۰۶</span><h2>اسلایدرهای محصولات</h2><p>پرفروش‌ها، تازه‌رسیده‌ها و تخفیف‌ها به‌صورت ریل اسکرولی و واکنش‌گرا از WooCommerce خوانده می‌شوند.</p></div><span class="studio-card-symbol">▤</span></div>
                            <?php foreach (array('best_sellers' => 'پرفروش‌ترین‌ها', 'new_arrivals' => 'تازه‌رسیده‌ها', 'on_sale' => 'محصولات تخفیف‌خورده') as $key => $label) : $product = $s['products'][$key]; ?>
                                <div class="studio-product-editor"><div class="studio-product-editor-title"><span class="studio-product-index"><?php echo $key === 'best_sellers' ? '۱' : ($key === 'new_arrivals' ? '۲' : '۳'); ?></span><div><h3><?php echo esc_html($label); ?></h3><small><?php echo $key === 'best_sellers' ? 'مرتب‌سازی بر اساس فروش' : ($key === 'new_arrivals' ? 'مرتب‌سازی بر اساس تاریخ' : 'فقط کالاهای دارای قیمت فروش ویژه'); ?></small></div><?php echo $this->checkbox(array('products', $key, 'enabled'), ! empty($product['enabled']), 'فعال'); ?></div><div class="studio-grid studio-grid-4"><div class="studio-field"><label>عنوان بخش</label><input type="text" name="<?php echo esc_attr($this->name(array('products', $key, 'title'))); ?>" value="<?php echo esc_attr($product['title']); ?>"></div><div class="studio-field"><label>توضیح کوتاه</label><input type="text" name="<?php echo esc_attr($this->name(array('products', $key, 'description'))); ?>" value="<?php echo esc_attr($product['description']); ?>"></div><div class="studio-field"><label>تعداد کالا</label><input type="number" min="3" max="24" name="<?php echo esc_attr($this->name(array('products', $key, 'limit'))); ?>" value="<?php echo esc_attr($product['limit']); ?>"></div><div class="studio-field"><label>ستون در دسکتاپ</label><select name="<?php echo esc_attr($this->name(array('products', $key, 'columns'))); ?>"><?php for ($i = 2; $i <= 5; $i++) : ?><option value="<?php echo esc_attr($i); ?>" <?php selected($product['columns'], $i); ?>><?php echo esc_html($i); ?></option><?php endfor; ?></select></div></div><?php echo $this->checkbox(array('products', $key, 'autoplay'), ! empty($product['autoplay']), 'حرکت خودکار اسلایدها'); ?></div>
                            <?php endforeach; ?>
                        </section>

                        <section class="studio-admin-card" id="studio-story">
                            <div class="studio-card-heading"><div><span class="studio-step">۰۷</span><h2>متن و معرفی برند</h2><p>زیر محصولات، داستان برند و یک تصویر مکمل قرار دهید تا صفحه فقط یک ویترین نباشد.</p></div><span class="studio-card-symbol">▱</span></div>
                            <?php echo $this->checkbox(array('story', 'enabled'), ! empty($s['story']['enabled']), 'نمایش بخش داستان برند'); ?>
                            <div class="studio-grid studio-grid-2"><div class="studio-field"><label>برچسب</label><input type="text" name="<?php echo esc_attr($this->name(array('story', 'eyebrow'))); ?>" value="<?php echo esc_attr($s['story']['eyebrow']); ?>"></div><div class="studio-field"><label>جای تصویر</label><select name="<?php echo esc_attr($this->name(array('story', 'image_side'))); ?>"><option value="left" <?php selected($s['story']['image_side'], 'left'); ?>>سمت چپ</option><option value="right" <?php selected($s['story']['image_side'], 'right'); ?>>سمت راست</option></select></div></div><div class="studio-field"><label>عنوان داستان</label><textarea rows="2" name="<?php echo esc_attr($this->name(array('story', 'title'))); ?>"><?php echo esc_textarea($s['story']['title']); ?></textarea></div><div class="studio-field"><label>متن داستان</label><textarea rows="3" name="<?php echo esc_attr($this->name(array('story', 'description'))); ?>"><?php echo esc_textarea($s['story']['description']); ?></textarea></div><div class="studio-grid studio-grid-3"><div class="studio-field"><label>متن دکمه</label><input type="text" name="<?php echo esc_attr($this->name(array('story', 'button_text'))); ?>" value="<?php echo esc_attr($s['story']['button_text']); ?>"></div><div class="studio-field studio-field-wide"><label>لینک دکمه</label><input type="url" name="<?php echo esc_attr($this->name(array('story', 'button_url'))); ?>" value="<?php echo esc_attr($s['story']['button_url']); ?>"></div></div><?php echo $this->media_field(array('story', 'image_id'), $s['story']['image_id'], 'تصویر داستان برند', 'تصویر مکمل برای بخش معرفی.'); ?>
                        </section>

                        <section class="studio-admin-card" id="studio-footer">
                            <div class="studio-card-heading"><div><span class="studio-step">۰۸</span><h2>فوتر و ارتباط با مخاطب</h2><p>اطلاعات تماس، لینک‌ها، تصویر فوتر و فرم خبرنامه را در انتهای صفحه قرار دهید.</p></div><span class="studio-card-symbol">⌄</span></div>
                            <?php echo $this->checkbox(array('footer', 'enabled'), ! empty($s['footer']['enabled']), 'نمایش فوتر استودیو'); ?>
                            <div class="studio-grid studio-grid-2"><div class="studio-field"><label>عنوان کوچک</label><input type="text" name="<?php echo esc_attr($this->name(array('footer', 'eyebrow'))); ?>" value="<?php echo esc_attr($s['footer']['eyebrow']); ?>"></div><div class="studio-field"><label>ایمیل</label><input type="email" name="<?php echo esc_attr($this->name(array('footer', 'email'))); ?>" value="<?php echo esc_attr($s['footer']['email']); ?>"></div><div class="studio-field"><label>تلفن</label><input type="text" name="<?php echo esc_attr($this->name(array('footer', 'phone'))); ?>" value="<?php echo esc_attr($s['footer']['phone']); ?>"></div><div class="studio-field"><label>نشانی</label><input type="text" name="<?php echo esc_attr($this->name(array('footer', 'address'))); ?>" value="<?php echo esc_attr($s['footer']['address']); ?>"></div></div><div class="studio-field"><label>متن معرفی فوتر</label><textarea rows="2" name="<?php echo esc_attr($this->name(array('footer', 'description'))); ?>"><?php echo esc_textarea($s['footer']['description']); ?></textarea></div><div class="studio-field"><label>متن کپی‌رایت</label><input type="text" name="<?php echo esc_attr($this->name(array('footer', 'copyright'))); ?>" value="<?php echo esc_attr($s['footer']['copyright']); ?>"></div><?php echo $this->checkbox(array('footer', 'show_menu'), ! empty($s['footer']['show_menu']), 'نمایش منوی دسترسی سریع در فوتر'); ?><?php echo $this->media_field(array('footer', 'image_id'), $s['footer']['image_id'], 'تصویر فوتر', 'لوگو یا تصویری کوچک برای معرفی برند در فوتر.'); ?>
                        </section>

                        <section class="studio-admin-card" id="studio-advanced">
                            <div class="studio-card-heading"><div><span class="studio-step">+</span><h2>تنظیمات تکمیلی</h2><p>برای اصلاح‌های بسیار جزئی، CSS سفارشی را با احتیاط و فقط با کلاس‌های استودیو وارد کنید.</p></div><span class="studio-card-symbol">{ }</span></div>
                            <div class="studio-field"><label for="studio-custom-css">CSS سفارشی</label><textarea id="studio-custom-css" class="studio-code" rows="8" name="<?php echo esc_attr($this->name('custom_css')); ?>" placeholder=".arena-studio .studio-hero h1 { ... }"><?php echo esc_textarea($s['custom_css']); ?></textarea><small>کدهای اسکریپت و تگ‌های HTML حذف می‌شوند. برای حفظ سازگاری، استایل‌ها را با <code>.arena-studio</code> شروع کنید.</small></div>
                        </section>

                        <?php submit_button('ذخیره و انتشار طراحی', 'primary large', 'submit', false, array('id' => 'studio-submit')); ?>
                    </main>

                    <aside class="studio-admin-sidebar">
                        <div class="studio-preview-card">
                            <div class="studio-preview-top"><strong>نقشه صفحه</strong><span>زنده با تنظیمات</span></div>
                            <div class="studio-mini-preview"><div class="mini-announcement"></div><div class="mini-header"><i></i><i></i><i></i></div><div class="mini-hero"><span></span><b></b></div><div class="mini-cards"><i></i><i></i><i></i><i></i></div><div class="mini-text"></div><div class="mini-footer"></div></div>
                            <p>بعد از ذخیره، با دکمه «پیش‌نمایش سایت» نتیجه واقعی را در تب جدید ببینید.</p><a class="button button-secondary button-block" href="<?php echo esc_url($home_url); ?>" target="_blank" rel="noopener">باز کردن سایت</a>
                        </div>
                        <div class="studio-side-card"><strong>دسترسی سریع</strong><a href="#studio-design">رنگ و چیدمان</a><a href="#studio-header">هدر و منو</a><a href="#studio-hero">معرفی و عکس اصلی</a><a href="#studio-products">اسلایدر محصولات</a><a href="#studio-footer">فوتر و تماس</a></div>
                        <div class="studio-side-card studio-side-help"><span class="studio-help-icon">?</span><strong>راهنمای کوتاه</strong><p>اول یک چیدمان آماده انتخاب کنید، بعد تصاویر و متن‌های خودتان را بگذارید. هیچ بخشی بدون فعال‌سازی شما روی سایت منتشر نمی‌شود.</p><a href="https://wordpress.org/plugins/elementor/" target="_blank" rel="noopener">درباره Elementor ↗</a></div>
                        <div class="studio-side-card studio-danger-zone"><strong>ابزارهای سایت</strong><a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=arena_site_studio_export'), 'arena_site_studio_export')); ?>">خروجی گرفتن از تنظیمات JSON</a><form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" onsubmit="return confirm('همه تنظیمات استودیو به حالت اولیه برگردد؟');"><input type="hidden" name="action" value="arena_site_studio_reset"><?php wp_nonce_field('arena_site_studio_reset'); ?><button type="submit" class="link-button danger">بازنشانی همه تنظیمات</button></form></div>
                    </aside>
                </div>
            </form>
        </div>
        <?php
    }

    public function apply_preset() {
        check_ajax_referer('arena_site_studio_admin', 'nonce');
        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'دسترسی مجاز نیست.'), 403);
        }
        $raw_key = isset($_POST['preset']) && is_scalar($_POST['preset']) ? wp_unslash($_POST['preset']) : '';
        $key = sanitize_key((string) $raw_key);
        if (! array_key_exists($key, $this->presets())) {
            wp_send_json_error(array('message' => 'چیدمان انتخاب‌شده معتبر نیست.'), 400);
        }
        $new = Settings::apply_preset($key, $this->settings->get());
        update_option(Settings::OPTION, Settings::sanitize($new));
        wp_send_json_success(array('message' => 'چیدمان آماده اعمال شد. در حال تازه‌سازی تنظیمات…'));
    }

    public function reset() {
        if (! current_user_can('manage_options')) {
            wp_die('دسترسی مجاز نیست.');
        }
        check_admin_referer('arena_site_studio_reset');
        update_option(Settings::OPTION, Settings::defaults());
        wp_safe_redirect(add_query_arg('settings-updated', '1', admin_url('admin.php?page=arena-site-studio')));
        exit;
    }

    public function export() {
        if (! current_user_can('manage_options')) {
            wp_die('دسترسی مجاز نیست.');
        }
        check_admin_referer('arena_site_studio_export');
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=site-studio-settings.json');
        echo wp_json_encode($this->settings->get(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    private function presets() {
        return array(
            'commerce'   => 'فروشگاهی',
            'editorial'  => 'مجله‌ای',
            'minimal'    => 'مینیمال',
            'marketplace' => 'بازارچه',
        );
    }

    private function name($path) {
        $path = is_array($path) ? $path : array($path);
        $name = Settings::OPTION;
        foreach ($path as $part) {
            $name .= '[' . $part . ']';
        }
        return $name;
    }

    private function checkbox($path, $checked, $label, $description = '') {
        $html  = '<label class="studio-toggle-field"><input type="hidden" name="' . esc_attr($this->name($path)) . '" value="0"><input type="checkbox" name="' . esc_attr($this->name($path)) . '" value="1" ' . checked($checked, true, false) . '><span class="studio-toggle-ui"></span><span><strong>' . esc_html($label) . '</strong>';
        if ($description) {
            $html .= '<small>' . esc_html($description) . '</small>';
        }
        $html .= '</span></label>';
        return $html;
    }

    private function media_field($path, $value, $label, $description = '') {
        $id       = is_array($path) ? implode('-', $path) : $path;
        $target   = 'studio-media-' . sanitize_html_class($id);
        $image    = $value ? wp_get_attachment_image_url(absint($value), 'thumbnail') : '';
        $html     = '<div class="studio-media-field"><div class="studio-media-heading"><div><label>' . esc_html($label) . '</label>';
        if ($description) {
            $html .= '<small>' . esc_html($description) . '</small>';
        }
        $html .= '</div><button type="button" class="button button-secondary" data-studio-media data-target="' . esc_attr($target) . '">انتخاب تصویر</button></div><input type="hidden" id="' . esc_attr($target) . '" name="' . esc_attr($this->name($path)) . '" value="' . esc_attr($value) . '"><div class="studio-media-preview" data-preview-for="' . esc_attr($target) . '">' . ($image ? '<img src="' . esc_url($image) . '" alt=""><button type="button" class="studio-media-remove" data-remove-media="' . esc_attr($target) . '" aria-label="حذف تصویر">×</button>' : '<span>هنوز تصویری انتخاب نشده است</span>') . '</div></div>';
        return $html;
    }
}
