<?php
/**
 * Beiruttime OSINT Pro - Main Plugin File
 * 
 * @package BeiruttimeOSINTPro
 * @version 3.0.0
 */

/**
 * Plugin Name: Beiruttime OSINT Pro
 * Plugin URI: https://beiruttime.com/osint-pro
 * Description: نظام متقدم للرصد والتحليل الاستخباراتي من المصادر المفتوحة مع دعم الحرب المركبة وGraphQL
 * Version: 3.0.0
 * Author: Beiruttime Team
 * Author URI: https://beiruttime.com
 * License: GPL v2 or later
 * Text Domain: beiruttime-osint-pro
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// تعريف الثوابت
define('BEIRUTTIME_OSINT_VERSION', '3.0.0');
define('BEIRUTTIME_OSINT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BEIRUTTIME_OSINT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BEIRUTTIME_OSINT_INCLUDES_DIR', BEIRUTTIME_OSINT_PLUGIN_DIR . 'includes/');
define('BEIRUTTIME_OSINT_MODULES_DIR', BEIRUTTIME_OSINT_PLUGIN_DIR . 'modules/');
define('BEIRUTTIME_OSINT_ASSETS_DIR', BEIRUTTIME_OSINT_PLUGIN_DIR . 'assets/');
define('BEIRUTTIME_OSINT_LOGS_DIR', WP_CONTENT_DIR . '/uploads/beiruttime-osint-logs/');

// إنشاء مجلد السجلات إذا لم يكن موجوداً
if (!file_exists(BEIRUTTIME_OSINT_LOGS_DIR)) {
    wp_mkdir_p(BEIRUTTIME_OSINT_LOGS_DIR);
}

/**
 * فئة التحميل التلقائي للفئات
 */
spl_autoload_register(function ($class) {
    $prefix = 'Beiruttime\\OSINT\\';
    $base_dir = BEIRUTTIME_OSINT_INCLUDES_DIR;
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . strtolower(str_replace('_', '-', $relative_class)) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

/**
 * تحميل ملفات الوحدات الأساسية
 */
function beiruttime_osint_load_modules() {
    // تحميل واجهة الوحدات
    require_once BEIRUTTIME_OSINT_MODULES_DIR . 'class-module-interface.php';
    
    // تحميل الفئة الأساسية للوحدات
    require_once BEIRUTTIME_OSINT_MODULES_DIR . 'class-base-module.php';
    
    // تحميل محمّل الوحدات
    require_once BEIRUTTIME_OSINT_MODULES_DIR . 'class-module-loader.php';
    
    // تحميل الوحدات الرئيسية
    $modules = array(
        'dashboard' => BEIRUTTIME_OSINT_MODULES_DIR . 'dashboard/class-dashboard-module.php',
        'map' => BEIRUTTIME_OSINT_MODULES_DIR . 'map/class-map-module.php',
        'chart' => BEIRUTTIME_OSINT_MODULES_DIR . 'chart/class-chart-module.php',
        'analysis' => BEIRUTTIME_OSINT_MODULES_DIR . 'analysis/class-analysis-module.php',
    );
    
    foreach ($modules as $module_name => $module_file) {
        if (file_exists($module_file)) {
            require_once $module_file;
        }
    }
}

/**
 * تحميل محرك OSINT
 */
function beiruttime_osint_load_engine() {
    require_once BEIRUTTIME_OSINT_INCLUDES_DIR . 'class-osint-engine.php';
    require_once BEIRUTTIME_OSINT_INCLUDES_DIR . 'class-hybrid-warfare.php';
    require_once BEIRUTTIME_OSINT_INCLUDES_DIR . 'class-verification.php';
    require_once BEIRUTTIME_OSINT_INCLUDES_DIR . 'class-early-warning.php';
}

/**
 * تحميل نظام الإدارة
 */
function beiruttime_osint_load_admin() {
    if (is_admin()) {
        require_once BEIRUTTIME_OSINT_INCLUDES_DIR . 'class-admin-menu.php';
        require_once BEIRUTTIME_OSINT_INCLUDES_DIR . 'class-admin-pages.php';
        require_once BEIRUTTIME_OSINT_INCLUDES_DIR . 'class-ajax-handlers.php';
    }
}

/**
 * تحميل الواجهة الأمامية
 */
function beiruttime_osint_load_frontend() {
    require_once BEIRUTTIME_OSINT_INCLUDES_DIR . 'class-shortcodes.php';
    require_once BEIRUTTIME_OSINT_INCLUDES_DIR . 'class-assets.php';
}

/**
 * تحميل الميزات المتقدمة
 */
function beiruttime_osint_load_advanced() {
    require_once BEIRUTTIME_OSINT_INCLUDES_DIR . 'class-graphql-api.php';
    require_once BEIRUTTIME_OSINT_INCLUDES_DIR . 'class-notification-system.php';
    require_once BEIRUTTIME_OSINT_INCLUDES_DIR . 'class-queue-system.php';
    require_once BEIRUTTIME_OSINT_INCLUDES_DIR . 'class-performance-monitor.php';
}

/**
 * تهيئة قاعدة البيانات
 */
function beiruttime_osint_install() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'so_news_events';
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        title varchar(500) NOT NULL,
        war_data longtext,
        war_data_clean longtext,
        actor_v2 varchar(255),
        region varchar(100),
        score int(11) DEFAULT 0,
        event_timestamp bigint(20) NOT NULL,
        publish_timestamp bigint(20) NOT NULL,
        osint_type varchar(50) DEFAULT 'general',
        hybrid_layers json,
        event_category varchar(100),
        strategic_category varchar(100),
        operational_level varchar(50),
        political_weight int(3) DEFAULT 0,
        economic_weight int(3) DEFAULT 0,
        social_impact int(3) DEFAULT 0,
        cyber_impact int(3) DEFAULT 0,
        primary_actor varchar(255),
        secondary_actor varchar(255),
        actor_network json,
        actor_relationships json,
        sponsor_entity varchar(255),
        funding_entity varchar(255),
        media_operator varchar(255),
        geo_country varchar(100),
        geo_region varchar(100),
        geo_city varchar(100),
        geo_district varchar(100),
        geo_coordinates varchar(50),
        geo_accuracy varchar(20),
        geo_sensitivity int(3) DEFAULT 0,
        event_start_time bigint(20),
        event_end_time bigint(20),
        publish_delay int(11) DEFAULT 0,
        time_accuracy varchar(20),
        verification_status varchar(50) DEFAULT 'unverified',
        verified_sources_count int(3) DEFAULT 0,
        has_visual_evidence tinyint(1) DEFAULT 0,
        has_satellite_imagery tinyint(1) DEFAULT 0,
        has_official_statement tinyint(1) DEFAULT 0,
        source_conflict tinyint(1) DEFAULT 0,
        verification_notes text,
        sentiment_score int(3) DEFAULT 0,
        threat_score int(3) DEFAULT 0,
        escalation_score int(3) DEFAULT 0,
        confidence_score int(3) DEFAULT 0,
        stability_index int(3) DEFAULT 50,
        aggression_index int(3) DEFAULT 0,
        risk_level varchar(20) DEFAULT 'low',
        impact_radius varchar(20) DEFAULT 'local',
        urgency_level varchar(20) DEFAULT 'normal',
        probable_intent text,
        probable_goal text,
        political_driver text,
        military_driver text,
        economic_driver text,
        media_driver text,
        trigger_event text,
        general_context text,
        linked_previous_event bigint(20),
        pattern_type varchar(100),
        pattern_frequency varchar(50),
        trend_direction varchar(50),
        cycle_detected tinyint(1) DEFAULT 0,
        signature_behavior text,
        escalation_chain_id varchar(100),
        likely_scenario text,
        alternative_scenario text,
        prediction_timeframe varchar(100),
        prediction_confidence int(3) DEFAULT 0,
        escalation_probability int(3) DEFAULT 0,
        containment_probability int(3) DEFAULT 0,
        spread_probability int(3) DEFAULT 0,
        alert_flag tinyint(1) DEFAULT 0,
        alert_type varchar(50),
        alert_reason text,
        alert_threshold int(3) DEFAULT 0,
        alert_priority varchar(20),
        alert_status varchar(20) DEFAULT 'pending',
        warfare_layers json,
        multi_domain_score int(3) DEFAULT 0,
        strategic_impact int(3) DEFAULT 0,
        asymmetric_indicator tinyint(1) DEFAULT 0,
        cognitive_warfare_flag tinyint(1) DEFAULT 0,
        information_operation tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_verification (verification_status, confidence_score),
        KEY idx_threat (threat_score, alert_flag),
        KEY idx_hybrid (multi_domain_score, risk_level),
        KEY idx_timestamp (event_timestamp),
        KEY idx_actor (primary_actor),
        KEY idx_region (geo_country, geo_region)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // جدول نظام الطابور
    $queue_table = $wpdb->prefix . 'osint_queue';
    $queue_sql = "CREATE TABLE $queue_table (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        job_name varchar(100) NOT NULL,
        job_data longtext,
        status varchar(20) DEFAULT 'pending',
        priority int(3) DEFAULT 5,
        attempts int(3) DEFAULT 0,
        max_attempts int(3) DEFAULT 3,
        scheduled_at datetime DEFAULT CURRENT_TIMESTAMP,
        started_at datetime NULL,
        completed_at datetime NULL,
        failed_at datetime NULL,
        error_message text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_status (status, priority),
        KEY idx_scheduled (scheduled_at)
    ) $charset_collate;";
    
    dbDelta($queue_sql);
    
    // جدول الإشعارات
    $notifications_table = $wpdb->prefix . 'osint_notifications';
    $notifications_sql = "CREATE TABLE $notifications_table (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        title varchar(255) NOT NULL,
        message text NOT NULL,
        type varchar(50) DEFAULT 'info',
        priority varchar(20) DEFAULT 'normal',
        is_read tinyint(1) DEFAULT 0,
        related_event_id bigint(20),
        action_url varchar(500),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        read_at datetime NULL,
        PRIMARY KEY (id),
        KEY idx_user (user_id, is_read),
        KEY idx_created (created_at)
    ) $charset_collate;";
    
    dbDelta($notifications_sql);
    
    update_option('beiruttime_osint_version', BEIRUTTIME_OSINT_VERSION);
    update_option('beiruttime_osint_installed', time());
}

/**
 * تفعيل الإضافة
 */
function beiruttime_osint_activate() {
    beiruttime_osint_install();
    beiruttime_osint_load_modules();
    
    // جدولة المهام الدورية
    if (!wp_next_scheduled('beiruttime_osint_hourly_cleanup')) {
        wp_schedule_event(time(), 'hourly', 'beiruttime_osint_hourly_cleanup');
    }
    
    if (!wp_next_scheduled('beiruttime_osint_daily_analysis')) {
        wp_schedule_event(time(), 'daily', 'beiruttime_osint_daily_analysis');
    }
    
    flush_rewrite_rules();
}

/**
 * إلغاء تنشيط الإضافة
 */
function beiruttime_osint_deactivate() {
    wp_clear_scheduled_hook('beiruttime_osint_hourly_cleanup');
    wp_clear_scheduled_hook('beiruttime_osint_daily_analysis');
    flush_rewrite_rules();
}

/**
 * حذف الإضافة
 */
function beiruttime_osint_uninstall() {
    // يمكن إضافة كود لحذف الجداول إذا لزم الأمر
    delete_option('beiruttime_osint_version');
    delete_option('beiruttime_osint_installed');
}

// تسجيل هوك التفعيل والإلغاء
register_activation_hook(__FILE__, 'beiruttime_osint_activate');
register_deactivation_hook(__FILE__, 'beiruttime_osint_deactivate');
register_uninstall_hook(__FILE__, 'beiruttime_osint_uninstall');

/**
 * تهيئة الإضافة
 */
function beiruttime_osint_init() {
    // تحميل النصوص المترجمة
    load_plugin_textdomain('beiruttime-osint-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // تحميل المكونات
    beiruttime_osint_load_modules();
    beiruttime_osint_load_engine();
    beiruttime_osint_load_admin();
    beiruttime_osint_load_frontend();
    beiruttime_osint_load_advanced();
    
    // تهيئة محمّل الوحدات
    if (class_exists('Beiruttime\OSINT\Module_Loader')) {
        Beiruttime\OSINT\Module_Loader::init();
    }
}
add_action('plugins_loaded', 'beiruttime_osint_init');

/**
 * إضافة قائمة الإعدادات
 */
function beiruttime_osint_admin_menu() {
    add_menu_page(
        __('Beiruttime OSINT', 'beiruttime-osint-pro'),
        __('OSINT Pro', 'beiruttime-osint-pro'),
        'manage_options',
        'beiruttime-osint',
        'beiruttime_osint_dashboard_page',
        'dashicons-shield-alt',
        30
    );
    
    add_submenu_page(
        'beiruttime-osint',
        __('لوحة التحكم', 'beiruttime-osint-pro'),
        __('لوحة التحكم', 'beiruttime-osint-pro'),
        'manage_options',
        'beiruttime-osint',
        'beiruttime_osint_dashboard_page'
    );
    
    add_submenu_page(
        'beiruttime-osint',
        __('الخريطة التفاعلية', 'beiruttime-osint-pro'),
        __('الخريطة', 'beiruttime-osint-pro'),
        'manage_options',
        'beiruttime-osint-map',
        'beiruttime_osint_map_page'
    );
    
    add_submenu_page(
        'beiruttime-osint',
        __('التحليلات', 'beiruttime-osint-pro'),
        __('التحليلات', 'beiruttime-osint-pro'),
        'manage_options',
        'beiruttime-osint-analysis',
        'beiruttime_osint_analysis_page'
    );
    
    add_submenu_page(
        'beiruttime-osint',
        __('الإعدادات', 'beiruttime-osint-pro'),
        __('الإعدادات', 'beiruttime-osint-pro'),
        'manage_options',
        'beiruttime-osint-settings',
        'beiruttime_osint_settings_page'
    );
}
add_action('admin_menu', 'beiruttime_osint_admin_menu');

/**
 * دوال عرض الصفحات
 */
function beiruttime_osint_dashboard_page() {
    include BEIRUTTIME_OSINT_MODULES_DIR . 'dashboard/views/dashboard-page.php';
}

function beiruttime_osint_map_page() {
    include BEIRUTTIME_OSINT_MODULES_DIR . 'map/views/map-widget.php';
}

function beiruttime_osint_analysis_page() {
    echo '<div class="wrap"><h1>' . __('التحليلات المتقدمة', 'beiruttime-osint-pro') . '</h1>';
    echo '<div id="beiruttime-analysis-content"></div></div>';
}

function beiruttime_osint_settings_page() {
    echo '<div class="wrap"><h1>' . __('إعدادات النظام', 'beiruttime-osint-pro') . '</h1>';
    echo '<p>' . __('قريباً...', 'beiruttime-osint-pro') . '</p></div>';
}

/**
 * تسجيل AJAX Handlers
 */
function beiruttime_osint_ajax_init() {
    // Dashboard AJAX
    add_action('wp_ajax_beiruttime_get_quick_stats', 'beiruttime_ajax_get_quick_stats');
    add_action('wp_ajax_beiruttime_get_recent_alerts', 'beiruttime_ajax_get_recent_alerts');
    add_action('wp_ajax_beiruttime_get_activity_chart', 'beiruttime_ajax_get_activity_chart');
    
    // Map AJAX
    add_action('wp_ajax_beiruttime_get_map_events', 'beiruttime_ajax_get_map_events');
    add_action('wp_ajax_beiruttime_get_heatmap_data', 'beiruttime_ajax_get_heatmap_data');
    add_action('wp_ajax_beiruttime_get_clustered_events', 'beiruttime_ajax_get_clustered_events');
    
    // Chart AJAX
    add_action('wp_ajax_beiruttime_get_chart_data', 'beiruttime_ajax_get_chart_data');
    add_action('wp_ajax_beiruttime_get_comparison_data', 'beiruttime_ajax_get_comparison_data');
    
    // Analysis AJAX
    add_action('wp_ajax_beiruttime_get_pattern_analysis', 'beiruttime_ajax_get_pattern_analysis');
    add_action('wp_ajax_beiruttime_get_trend_analysis', 'beiruttime_ajax_get_trend_analysis');
    add_action('wp_ajax_beiruttime_get_prediction_report', 'beiruttime_ajax_get_prediction_report');
    add_action('wp_ajax_beiruttime_generate_full_report', 'beiruttime_ajax_generate_full_report');
    
    // GraphQL
    add_action('wp_ajax_beiruttime_graphql', 'beiruttime_ajax_graphql_handler');
    add_action('wp_ajax_nopriv_beiruttime_graphql', 'beiruttime_ajax_graphql_handler');
}
add_action('admin_init', 'beiruttime_osint_ajax_init');

/**
 * معالجة طلبات GraphQL
 */
function beiruttime_ajax_graphql_handler() {
    check_ajax_referer('beiruttime_graphql_nonce', 'nonce');
    
    if (!class_exists('Beiruttime\\OSINT\\GraphQL_API')) {
        wp_send_json_error(array('message' => 'GraphQL API not available'));
    }
    
    $graphql = new Beiruttime\\OSINT\\GraphQL_API();
    $query = isset($_POST['query']) ? sanitize_text_field(wp_unslash($_POST['query'])) : '';
    $variables = isset($_POST['variables']) ? json_decode(wp_unslash($_POST['variables']), true) : array();
    
    $result = $graphql->execute_query($query, $variables);
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}

/**
 * دوال AJAX stub (سيتم تنفيذها في class-ajax-handlers.php)
 */
function beiruttime_ajax_get_quick_stats() {
    if (class_exists('Beiruttime\\OSINT\\Ajax_Handlers')) {
        Beiruttime\\OSINT\\Ajax_Handlers::handle_get_quick_stats();
    } else {
        wp_send_json_error(array('message' => 'Handler not available'));
    }
}

function beiruttime_ajax_get_recent_alerts() {
    if (class_exists('Beiruttime\\OSINT\\Ajax_Handlers')) {
        Beiruttime\\OSINT\\Ajax_Handlers::handle_get_recent_alerts();
    } else {
        wp_send_json_error(array('message' => 'Handler not available'));
    }
}

function beiruttime_ajax_get_activity_chart() {
    if (class_exists('Beiruttime\\OSINT\\Ajax_Handlers')) {
        Beiruttime\\OSINT\\Ajax_Handlers::handle_get_activity_chart();
    } else {
        wp_send_json_error(array('message' => 'Handler not available'));
    }
}

function beiruttime_ajax_get_map_events() {
    if (class_exists('Beiruttime\\OSINT\\Ajax_Handlers')) {
        Beiruttime\\OSINT\\Ajax_Handlers::handle_get_map_events();
    } else {
        wp_send_json_error(array('message' => 'Handler not available'));
    }
}

function beiruttime_ajax_get_heatmap_data() {
    if (class_exists('Beiruttime\\OSINT\\Ajax_Handlers')) {
        Beiruttime\\OSINT\\Ajax_Handlers::handle_get_heatmap_data();
    } else {
        wp_send_json_error(array('message' => 'Handler not available'));
    }
}

function beiruttime_ajax_get_clustered_events() {
    if (class_exists('Beiruttime\\OSINT\\Ajax_Handlers')) {
        Beiruttime\\OSINT\\Ajax_Handlers::handle_get_clustered_events();
    } else {
        wp_send_json_error(array('message' => 'Handler not available'));
    }
}

function beiruttime_ajax_get_chart_data() {
    if (class_exists('Beiruttime\\OSINT\\Ajax_Handlers')) {
        Beiruttime\\OSINT\\Ajax_Handlers::handle_get_chart_data();
    } else {
        wp_send_json_error(array('message' => 'Handler not available'));
    }
}

function beiruttime_ajax_get_comparison_data() {
    if (class_exists('Beiruttime\\OSINT\\Ajax_Handlers')) {
        Beiruttime\\OSINT\\Ajax_Handlers::handle_get_comparison_data();
    } else {
        wp_send_json_error(array('message' => 'Handler not available'));
    }
}

function beiruttime_ajax_get_pattern_analysis() {
    if (class_exists('Beiruttime\\OSINT\\Ajax_Handlers')) {
        Beiruttime\\OSINT\\Ajax_Handlers::handle_get_pattern_analysis();
    } else {
        wp_send_json_error(array('message' => 'Handler not available'));
    }
}

function beiruttime_ajax_get_trend_analysis() {
    if (class_exists('Beiruttime\\OSINT\\Ajax_Handlers')) {
        Beiruttime\\OSINT\\Ajax_Handlers::handle_get_trend_analysis();
    } else {
        wp_send_json_error(array('message' => 'Handler not available'));
    }
}

function beiruttime_ajax_get_prediction_report() {
    if (class_exists('Beiruttime\\OSINT\\Ajax_Handlers')) {
        Beiruttime\\OSINT\\Ajax_Handlers::handle_get_prediction_report();
    } else {
        wp_send_json_error(array('message' => 'Handler not available'));
    }
}

function beiruttime_ajax_generate_full_report() {
    if (class_exists('Beiruttime\\OSINT\\Ajax_Handlers')) {
        Beiruttime\\OSINT\\Ajax_Handlers::handle_generate_full_report();
    } else {
        wp_send_json_error(array('message' => 'Handler not available'));
    }
}

/**
 * تنظيف دوري
 */
function beiruttime_osint_hourly_cleanup() {
    if (class_exists('Beiruttime\\OSINT\\Queue_System')) {
        Beiruttime\\OSINT\\Queue_System::cleanup_completed_jobs();
    }
}
add_action('beiruttime_osint_hourly_cleanup', 'beiruttime_osint_hourly_cleanup');

/**
 * تحليل يومي
 */
function beiruttime_osint_daily_analysis() {
    if (class_exists('Beiruttime\\OSINT\\Early_Warning')) {
        $warning = new Beiruttime\\OSINT\\Early_Warning();
        $warning->generate_daily_digest();
    }
}
add_action('beiruttime_osint_daily_analysis', 'beiruttime_osint_daily_analysis');
