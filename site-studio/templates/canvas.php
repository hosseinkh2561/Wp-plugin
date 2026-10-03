<?php
/**
 * Full-page canvas used when Site Studio is set to replace the homepage.
 */

if (! defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('arena-studio-canvas'); ?>>
<?php if (function_exists('wp_body_open')) {
    wp_body_open();
} ?>
<?php echo Arena\SiteStudio\Plugin::instance()->renderer()->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php wp_footer(); ?>
</body>
</html>
