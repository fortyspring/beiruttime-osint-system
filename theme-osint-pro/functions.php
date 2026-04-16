<?php
/**
 * OSINT Pro Theme Functions
 *
 * @package OSINT_Pro_Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Setup
 */
function osint_pro_theme_setup() {
    // Add default posts and comments RSS feed links to head
    add_theme_support('automatic-feed-links');

    // Let WordPress manage the document title
    add_theme_support('title-tag');

    // Enable support for Post Thumbnails
    add_theme_support('post-thumbnails');
    set_post_thumbnail_size(400, 250, true);
    add_image_size('osint-large', 1200, 600, true);
    add_image_size('osint-medium', 600, 400, true);

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('القائمة الرئيسية', 'osint-pro-theme'),
        'footer'  => __('قائمة الفوتر', 'osint-pro-theme'),
    ));

    // Switch default core markup for HTML5
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));

    // Add theme support for selective refresh for widgets
    add_theme_support('customize-selective-refresh-widgets');

    // Add support for custom logo
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ));

    // Add support for custom background
    add_theme_support('custom-background');

    // Add support for RTL
    add_theme_support('rtl');

    // Declare WooCommerce support
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}
add_action('after_setup_theme', 'osint_pro_theme_setup');

/**
 * Enqueue scripts and styles
 */
function osint_pro_theme_scripts() {
    // Main stylesheet
    wp_enqueue_style('osint-pro-style', get_stylesheet_uri(), array(), '1.0.0');

    // Google Fonts (Arabic)
    wp_enqueue_style('osint-pro-google-fonts', 
        'https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap',
        array(), 
        null
    );

    // Main JavaScript
    wp_enqueue_script('osint-pro-main', 
        get_template_directory_uri() . '/assets/js/main.js', 
        array('jquery'), 
        '1.0.0', 
        true
    );

    // Comment reply script
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }

    // Localize script for AJAX
    wp_localize_script('osint-pro-main', 'osintProAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('osint-pro-nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'osint_pro_theme_scripts');

/**
 * Register widget areas
 */
function osint_pro_theme_widgets_init() {
    register_sidebar(array(
        'name'          => __('الشريط الجانبي', 'osint-pro-theme'),
        'id'            => 'sidebar-1',
        'description'   => __('أضف الأدوات هنا للظهور في الشريط الجانبي', 'osint-pro-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => __('الفوتر 1', 'osint-pro-theme'),
        'id'            => 'footer-1',
        'description'   => __('منطقة الفوتر الأولى', 'osint-pro-theme'),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3>',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => __('الفوتر 2', 'osint-pro-theme'),
        'id'            => 'footer-2',
        'description'   => __('منطقة الفوتر الثانية', 'osint-pro-theme'),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3>',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => __('الفوتر 3', 'osint-pro-theme'),
        'id'            => 'footer-3',
        'description'   => __('منطقة الفوتر الثالثة', 'osint-pro-theme'),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3>',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'osint_pro_theme_widgets_init');

/**
 * Custom excerpt length
 */
function osint_pro_excerpt_length($length) {
    return 25;
}
add_filter('excerpt_length', 'osint_pro_excerpt_length');

/**
 * Custom excerpt more
 */
function osint_pro_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'osint_pro_excerpt_more');

/**
 * Check if user has premium subscription
 */
function osint_pro_is_premium_user() {
    if (!is_user_logged_in()) {
        return false;
    }

    $user_id = get_current_user_id();
    
    // Check for WooCommerce subscriptions
    if (class_exists('WC_Subscriptions_Customer')) {
        if (wcs_user_has_subscription($user_id, '', 'active')) {
            return true;
        }
    }

    // Check for custom user meta
    $is_premium = get_user_meta($user_id, '_osint_premium_member', true);
    if ($is_premium) {
        return true;
    }

    // Check for membership plugin
    if (class_exists('MS_Model_Member')) {
        $membership = MS_Model_Member::get_member_by_user_id($user_id);
        if ($membership && $membership->has_access()) {
            return true;
        }
    }

    return false;
}

/**
 * Lock premium content
 */
function osint_pro_lock_content($content, $is_premium_content = false) {
    if (!$is_premium_content) {
        return $content;
    }

    if (osint_pro_is_premium_user()) {
        return $content;
    }

    $login_url = wp_login_url(get_permalink());
    $register_url = wp_registration_url();

    $lock_message = sprintf('
        <div class="premium-lock">
            <div class="lock-message">
                <div class="lock-icon">🔒</div>
                <h3>%s</h3>
                <p>%s</p>
                <a href="%s" class="subscribe-btn">%s</a>
                <p style="margin-top: 15px; font-size: 0.9rem;">
                    %s <a href="%s">%s</a>
                </p>
            </div>
            %s
        </div>
    ',
        __('محتوى مميز للمشتركين فقط', 'osint-pro-theme'),
        __('اشترك الآن للوصول إلى هذا المحتوى الحصري', 'osint-pro-theme'),
        esc_url(wc_get_page_permalink('myaccount')),
        __('اشترك الآن', 'osint-pro-theme'),
        __('لديك حساب بالفعل؟', 'osint-pro-theme'),
        esc_url($login_url),
        __('تسجيل الدخول', 'osint-pro-theme'),
        wp_trim_words($content, 10, '...')
    );

    return $lock_message;
}

/**
 * Add custom body classes
 */
function osint_pro_body_classes($classes) {
    if (osint_pro_is_premium_user()) {
        $classes[] = 'premium-user';
    }
    
    if (is_rtl()) {
        $classes[] = 'rtl';
    }

    return $classes;
}
add_filter('body_class', 'osint_pro_body_classes');

/**
 * Google Analytics Integration
 */
function osint_pro_google_analytics() {
    $tracking_id = get_theme_mod('osint_google_analytics_id', '');
    
    if (!empty($tracking_id)) {
        ?>
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($tracking_id); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo esc_attr($tracking_id); ?>');
        </script>
        <?php
    }
}
add_action('wp_head', 'osint_pro_google_analytics', 1);

/**
 * Ad management functions
 */
function osint_pro_get_ad_code($position = 'header') {
    $ad_code = get_theme_mod("osint_ad_{$position}", '');
    return $ad_code;
}

function osint_pro_display_ad($position = 'header') {
    $ad_code = osint_pro_get_ad_code($position);
    
    if (!empty($ad_code)) {
        echo '<div class="ad-space">';
        echo '<div class="ad-label">' . __('إعلان', 'osint-pro-theme') . '</div>';
        echo $ad_code;
        echo '</div>';
    }
}

/**
 * OSINT Pro Plugin Integration Check
 */
function osint_pro_plugin_active() {
    return class_exists('OSINT_Pro_Main') || file_exists(WP_PLUGIN_DIR . '/beiruttime-osint-pro/beiruttime-osint-pro.php');
}

/**
 * Add OSINT Pro shortcodes support
 */
function osint_pro_register_shortcodes() {
    // Subscription shortcode
    add_shortcode('osint_subscribe', 'osint_pro_subscribe_shortcode');
    
    // Premium content shortcode
    add_shortcode('osint_premium', 'osint_pro_premium_shortcode');
    
    // Ad shortcode
    add_shortcode('osint_ad', 'osint_pro_ad_shortcode');
}
add_action('init', 'osint_pro_register_shortcodes');

/**
 * Subscribe button shortcode
 */
function osint_pro_subscribe_shortcode($atts) {
    $atts = shortcode_atts(array(
        'text' => __('اشترك الآن', 'osint-pro-theme'),
        'plan' => '',
    ), $atts);

    $url = wc_get_page_permalink('myaccount');
    if (!empty($atts['plan'])) {
        $url = add_query_arg('plan', $atts['plan'], $url);
    }

    return sprintf('<a href="%s" class="subscribe-btn">%s</a>', esc_url($url), esc_html($atts['text']));
}

/**
 * Premium content shortcode
 */
function osint_pro_premium_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'locked' => 'true',
    ), $atts);

    if ($atts['locked'] === 'true') {
        return osint_pro_lock_content(do_shortcode($content), true);
    }

    return do_shortcode($content);
}

/**
 * Ad display shortcode
 */
function osint_pro_ad_shortcode($atts) {
    $atts = shortcode_atts(array(
        'position' => 'content',
    ), $atts);

    ob_start();
    osint_pro_display_ad($atts['position']);
    return ob_get_clean();
}

/**
 * Customizer settings
 */
function osint_pro_customize_register($wp_customize) {
    // Google Analytics Section
    $wp_customize->add_section('osint_analytics', array(
        'title'    => __('تتبع Google', 'osint-pro-theme'),
        'priority' => 30,
    ));

    $wp_customize->add_setting('osint_google_analytics_id', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('osint_google_analytics_id', array(
        'label'       => __('معرف تتبع Google Analytics', 'osint-pro-theme'),
        'section'     => 'osint_analytics',
        'type'        => 'text',
        'description' => __('أدخل معرف التتبع مثل: UA-XXXXX-Y أو G-XXXXXX', 'osint-pro-theme'),
    ));

    // Ads Section
    $wp_customize->add_section('osint_ads', array(
        'title'    => __('الإعلانات', 'osint-pro-theme'),
        'priority' => 31,
    ));

    $ad_positions = array(
        'header'  => __('أعلى الصفحة', 'osint-pro-theme'),
        'content' => __('داخل المحتوى', 'osint-pro-theme'),
        'sidebar' => __('الشريط الجانبي', 'osint-pro-theme'),
        'footer'  => __('أسفل الصفحة', 'osint-pro-theme'),
    );

    foreach ($ad_positions as $position => $label) {
        $wp_customize->add_setting("osint_ad_{$position}", array(
            'sanitize_callback' => 'wp_kses_post',
        ));

        $wp_customize->add_control("osint_ad_{$position}", array(
            'label'       => sprintf(__('كود الإعلان - %s', 'osint-pro-theme'), $label),
            'section'     => 'osint_ads',
            'type'        => 'textarea',
            'description' => __('ألصق كود الإعلان هنا (Google AdSense، إلخ)', 'osint-pro-theme'),
        ));
    }

    // Colors Section
    $wp_customize->add_section('osint_colors', array(
        'title'    => __('الألوان', 'osint-pro-theme'),
        'priority' => 35,
    ));

    $wp_customize->add_setting('osint_primary_color', array(
        'default'           => '#0073aa',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'osint_primary_color', array(
        'label'    => __('اللون الأساسي', 'osint-pro-theme'),
        'section'  => 'osint_colors',
    )));
}
add_action('customize_register', 'osint_pro_customize_register');

/**
 * WooCommerce integration
 */
function osint_pro_woocommerce_support() {
    add_theme_support('woocommerce');
}
add_action('after_setup_theme', 'osint_pro_woocommerce_support');

/**
 * Add subscription product type check
 */
function osint_pro_check_subscription_product($product_id) {
    if (class_exists('WC_Product_Subscription')) {
        $product = wc_get_product($product_id);
        if ($product && $product->is_type('subscription')) {
            return true;
        }
    }
    return false;
}

/**
 * Redirect after login based on subscription status
 */
function osint_pro_login_redirect($redirect_to, $request, $user) {
    if (osint_pro_is_premium_user()) {
        return home_url('/premium-content/');
    }
    return home_url();
}
add_filter('login_redirect', 'osint_pro_login_redirect', 10, 3);
