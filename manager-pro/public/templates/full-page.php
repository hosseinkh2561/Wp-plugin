<?php if (!defined('ABSPATH')) exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <title><?php wp_title(); ?></title>
    <?php wp_head(); ?>
    <style>body { direction: rtl; text-align: right; }</style>
</head>
<body>
    <header><h1>Header (Manager Pro)</h1></header>
    <main><?php while(have_posts()): the_post(); the_content(); endwhile; ?></main>
    <footer><p>Footer (Manager Pro) - Dev: HOSSEINKHORRAMI</p></footer>
    <?php wp_footer(); ?>
</body>
</html>
