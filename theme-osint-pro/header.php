<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#primary"><?php _e('انتقل إلى المحتوى', 'osint-pro-theme'); ?></a>

    <header id="masthead" class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="site-branding">
                    <?php if (has_custom_logo()) : ?>
                        <div class="site-logo">
                            <?php the_custom_logo(); ?>
                        </div>
                    <?php else : ?>
                        <h1 class="site-title">
                            <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                                <?php bloginfo('name'); ?>
                            </a>
                        </h1>
                        <p class="site-description"><?php bloginfo('description'); ?></p>
                    <?php endif; ?>
                </div>

                <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                    <span class="screen-reader-text"><?php _e('القائمة', 'osint-pro-theme'); ?></span>
                    <span>&#9776;</span>
                </button>

                <nav id="site-navigation" class="main-navigation">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'menu_id'        => 'primary-menu',
                        'container'      => false,
                    ));
                    ?>
                </nav>

                <div class="header-actions">
                    <?php if (!is_user_logged_in()) : ?>
                        <a href="<?php echo wp_login_url(); ?>" class="login-btn">
                            <?php _e('تسجيل الدخول', 'osint-pro-theme'); ?>
                        </a>
                    <?php else : ?>
                        <a href="<?php echo wp_logout_url(); ?>" class="logout-btn">
                            <?php _e('خروج', 'osint-pro-theme'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php osint_pro_display_ad('header'); ?>
    </header>
