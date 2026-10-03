<?php
/**
 * Site Studio page fragment.
 *
 * @var array    $settings
 * @var Renderer $this
 */

if (! defined('ABSPATH')) {
    exit;
}

$header = $settings['header'];
$design = $settings['design'];
$hero   = $settings['hero'];
$story  = $settings['story'];
$footer = $settings['footer'];
$brand  = $settings['brand_name'];

$use_elementor_locations = ! empty($settings['integrations']['use_elementor_locations']) && function_exists('elementor_theme_do_location');
$elementor_header_html   = '';
$elementor_header_active = false;
if ($use_elementor_locations) {
    ob_start();
    $elementor_header_active = (bool) elementor_theme_do_location('header');
    $elementor_header_html   = ob_get_clean();
    $elementor_header_active = $elementor_header_active || (bool) trim($elementor_header_html);
}
$elementor_footer_html   = '';
$elementor_footer_active = false;
if ($use_elementor_locations) {
    ob_start();
    $elementor_footer_active = (bool) elementor_theme_do_location('footer');
    $elementor_footer_html   = ob_get_clean();
    $elementor_footer_active = $elementor_footer_active || (bool) trim($elementor_footer_html);
}

$cart_count = 0;
if (function_exists('WC') && WC() && isset(WC()->cart) && WC()->cart) {
    $cart_count = WC()->cart->get_cart_contents_count();
}
$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url();
$cart_url    = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/#cart');
?>
<div class="arena-studio" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>" data-preset="<?php echo esc_attr($settings['preset']); ?>" data-columns="<?php echo esc_attr($design['columns']); ?>">
    <?php if ($elementor_header_active) : ?>
        <?php echo $elementor_header_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php else : ?>
    <?php if (! empty($header['announcement_enabled']) && $header['announcement']) : ?>
        <div class="studio-announcement">
            <div class="studio-container studio-announcement-inner">
                <span><?php echo esc_html($header['announcement']); ?></span>
                <a href="#products-best_sellers">مشاهده پیشنهادها <span aria-hidden="true">↗</span></a>
            </div>
        </div>
    <?php endif; ?>

    <header class="studio-header studio-header-<?php echo esc_attr($design['header_style']); ?><?php echo ! empty($header['sticky']) ? ' studio-header-sticky' : ''; ?>">
        <div class="studio-container studio-header-inner">
            <a class="studio-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr($brand); ?>">
                <?php if (! empty($settings['logo_id'])) : ?>
                    <?php echo $this->image($settings['logo_id'], 'medium', 'studio-logo-image', $brand); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php else : ?>
                    <span class="studio-brand-mark" aria-hidden="true"><span></span></span>
                    <span class="studio-brand-copy"><strong><?php echo esc_html($brand); ?></strong><small><?php echo esc_html($settings['tagline']); ?></small></span>
                <?php endif; ?>
            </a>

            <nav class="studio-desktop-nav" aria-label="منوی اصلی">
                <?php echo $this->navigation($settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </nav>

            <div class="studio-header-actions">
                <?php if (! empty($header['show_search'])) : ?>
                    <button type="button" class="studio-icon-button" data-studio-search-toggle aria-label="نمایش جستجو">⌕</button>
                <?php endif; ?>
                <?php if (! empty($header['show_account'])) : ?>
                    <a class="studio-icon-button studio-account-link" href="<?php echo esc_url($account_url); ?>" aria-label="حساب کاربری">◎</a>
                <?php endif; ?>
                <?php if (! empty($header['show_cart'])) : ?>
                    <a class="studio-icon-button studio-cart-link" href="<?php echo esc_url($cart_url); ?>" aria-label="سبد خرید">
                        ♧<?php if ($cart_count) : ?><span class="studio-cart-count"><?php echo esc_html($cart_count); ?></span><?php endif; ?>
                    </a>
                <?php endif; ?>
                <?php if (! empty($header['cta_enabled'])) : ?>
                    <a class="studio-button studio-button-small" href="<?php echo $this->link($header['cta_url']); ?>"><?php echo esc_html($header['cta_text']); ?></a>
                <?php endif; ?>
                <button type="button" class="studio-menu-toggle" data-studio-menu-toggle aria-expanded="false" aria-controls="studio-mobile-menu" aria-label="باز کردن منو"><span></span><span></span></button>
            </div>
        </div>
        <?php if (! empty($header['show_search'])) : ?>
            <div class="studio-search-panel" data-studio-search-panel hidden>
                <div class="studio-container">
                    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                        <label for="studio-search-input">جستجو در سایت</label>
                        <div><input id="studio-search-input" type="search" name="s" placeholder="چه چیزی پیدا می‌کنید؟"><button type="submit">جستجو</button></div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        <div class="studio-mobile-menu" id="studio-mobile-menu" data-studio-mobile-menu hidden>
            <div class="studio-container"><?php echo $this->navigation($settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
        </div>
    </header>
    <?php endif; ?>

    <main class="studio-main">
        <?php if (! empty($hero['enabled'])) : ?>
            <section class="studio-hero studio-container studio-hero-<?php echo esc_attr($hero['image_position']); ?>">
                <div class="studio-hero-content">
                    <span class="studio-kicker"><?php echo esc_html($hero['eyebrow']); ?></span>
                    <h1><?php echo esc_html($hero['title']); ?></h1>
                    <p><?php echo esc_html($hero['description']); ?></p>
                    <div class="studio-hero-actions">
                        <a class="studio-button" href="<?php echo $this->link($hero['button_url']); ?>"><?php echo esc_html($hero['button_text']); ?><span aria-hidden="true">↗</span></a>
                        <?php if ($hero['secondary_text']) : ?>
                            <a class="studio-text-link" href="<?php echo $this->link($hero['secondary_url']); ?>"><?php echo esc_html($hero['secondary_text']); ?><span aria-hidden="true">→</span></a>
                        <?php endif; ?>
                    </div>
                    <div class="studio-hero-meta"><span class="studio-meta-avatar">✦</span><span><strong>تجربه‌ای متفاوت</strong><small>طراحی شده با توجه به شما</small></span></div>
                </div>
                <div class="studio-hero-visual" aria-label="تصویر معرفی">
                    <?php echo $this->image($hero['image_id'], 'large', 'studio-hero-image', $hero['title']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <span class="studio-hero-sticker">برای شما<br><strong>انتخاب شده</strong></span>
                    <span class="studio-hero-orbit studio-hero-orbit-one"></span><span class="studio-hero-orbit studio-hero-orbit-two"></span>
                </div>
            </section>
        <?php endif; ?>

        <?php if (! empty($settings['features']['enabled'])) : ?>
            <section class="studio-container studio-features" aria-label="مزایای خرید">
                <?php foreach ($settings['features']['items'] as $feature) : ?>
                    <div class="studio-feature">
                        <span class="studio-feature-icon" aria-hidden="true"><?php echo esc_html($feature['icon']); ?></span>
                        <div><h2><?php echo esc_html($feature['title']); ?></h2><p><?php echo esc_html($feature['description']); ?></p></div>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <div class="studio-container studio-product-sections" id="products">
            <?php echo $this->product_section('best_sellers', $settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->product_section('new_arrivals', $settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->product_section('on_sale', $settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>

        <?php if (! empty($story['enabled'])) : ?>
            <section class="studio-container studio-story studio-story-<?php echo esc_attr($story['image_side']); ?>" id="story">
                <div class="studio-story-visual"><?php echo $this->image($story['image_id'], 'large', 'studio-story-image', $story['title']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                <div class="studio-story-content">
                    <span class="studio-kicker"><?php echo esc_html($story['eyebrow']); ?></span>
                    <h2><?php echo esc_html($story['title']); ?></h2>
                    <p><?php echo esc_html($story['description']); ?></p>
                    <?php if ($story['button_text']) : ?><a class="studio-text-link" href="<?php echo $this->link($story['button_url']); ?>"><?php echo esc_html($story['button_text']); ?><span aria-hidden="true">↗</span></a><?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php if ($elementor_footer_active) : ?>
        <?php echo $elementor_footer_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php elseif (! empty($footer['enabled'])) : ?>
        <footer class="studio-footer" id="contact">
            <div class="studio-container studio-footer-main">
                <div class="studio-footer-intro">
                    <?php if (! empty($footer['image_id'])) : ?>
                        <div class="studio-footer-image"><?php echo $this->image($footer['image_id'], 'medium', '', $brand); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                    <?php else : ?>
                        <span class="studio-brand-mark studio-brand-mark-footer" aria-hidden="true"><span></span></span>
                    <?php endif; ?>
                    <span class="studio-kicker"><?php echo esc_html($footer['eyebrow']); ?></span>
                    <p><?php echo esc_html($footer['description']); ?></p>
                </div>
                <?php if (! empty($footer['show_menu'])) : ?>
                    <div class="studio-footer-column"><h2>دسترسی سریع</h2><?php echo $this->navigation($settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                <?php endif; ?>
                <div class="studio-footer-column"><h2>تماس با ما</h2>
                    <?php if ($footer['email']) : ?><a href="mailto:<?php echo esc_attr($footer['email']); ?>"><?php echo esc_html($footer['email']); ?></a><?php endif; ?>
                    <?php if ($footer['phone']) : ?><a href="tel:<?php echo esc_attr($footer['phone']); ?>"><?php echo esc_html($footer['phone']); ?></a><?php endif; ?>
                    <?php if ($footer['address']) : ?><span><?php echo esc_html($footer['address']); ?></span><?php endif; ?>
                </div>
                <div class="studio-footer-column studio-footer-newsletter"><h2>همراه ما باشید</h2><p>برای پیشنهادهای تازه ایمیل‌تان را ثبت کنید.</p><form action="" method="post" data-studio-newsletter><label class="screen-reader-text" for="studio-footer-email">ایمیل</label><input id="studio-footer-email" type="email" placeholder="ایمیل شما" required><button type="submit" aria-label="ثبت ایمیل">↗</button></form></div>
            </div>
            <div class="studio-container studio-footer-bottom"><span><?php echo esc_html($footer['copyright']); ?></span><span>ساخته شده با <b>استودیو پوسته</b></span></div>
        </footer>
    <?php endif; ?>
</div>
