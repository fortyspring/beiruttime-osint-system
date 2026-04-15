<?php
/**
 * Dashboard Module
 * 
 * Main dashboard module for OSINT intelligence display.
 * Provides widgets, metrics, and real-time data visualization.
 * 
 * @package BeirutTime_OSINT_Pro
 * @subpackage Modules/Dashboard
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__DIR__) . '/modules/class-base-module.php';

class OSINT_Dashboard_Module extends OSINT_Base_Module {
    
    /**
     * {@inheritdoc}
     */
    public function get_id() {
        return 'dashboard';
    }
    
    /**
     * {@inheritdoc}
     */
    public function get_name() {
        return __('لوحة التحكم الرئيسية', 'beiruttime-osint-pro');
    }
    
    /**
     * {@inheritdoc}
     */
    public function get_version() {
        return '2.0.0';
    }
    
    /**
     * {@inheritdoc}
     */
    public function init() {
        if (!$this->is_active()) {
            return;
        }
        
        // Register admin menu
        add_action('admin_menu', array($this, 'register_admin_menu'));
        
        // Register AJAX handlers
        add_action('wp_ajax_osint_dashboard_get_data', array($this, 'ajax_get_dashboard_data'));
        add_action('wp_ajax_osint_dashboard_refresh', array($this, 'ajax_refresh_dashboard'));
        add_action('wp_ajax_osint_dashboard_save_layout', array($this, 'ajax_save_layout'));
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Register widgets
        add_action('wp_dashboard_setup', array($this, 'register_wp_widgets'));
        
        $this->log('Dashboard module initialized');
    }
    
    /**
     * Register admin menu page
     */
    public function register_admin_menu() {
        add_menu_page(
            __('Beiruttime OSINT Dashboard', 'beiruttime-osint-pro'),
            __('لوحة التحكم', 'beiruttime-osint-pro'),
            'manage_options',
            'osint-dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-dashboard',
            3
        );
        
        add_submenu_page(
            'osint-dashboard',
            __('Dashboard Overview', 'beiruttime-osint-pro'),
            __('نظرة عامة', 'beiruttime-osint-pro'),
            'manage_options',
            'osint-dashboard',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'osint-dashboard',
            __('Dashboard Settings', 'beiruttime-osint-pro'),
            __('الإعدادات', 'beiruttime-osint-pro'),
            'manage_options',
            'osint-dashboard-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Render main dashboard page
     */
    public function render_dashboard_page() {
        include dirname(__FILE__) . '/views/dashboard-page.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        include dirname(__FILE__) . '/views/settings-page.php';
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'osint-dashboard') === false) {
            return;
        }
        
        wp_enqueue_style(
            'osint-dashboard-style',
            BEIRUTTIME_OSINT_PRO_URL . 'assets/css/dashboard.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'osint-dashboard-script',
            BEIRUTTIME_OSINT_PRO_URL . 'assets/js/dashboard.js',
            array('jquery', 'wp-api'),
            $this->version,
            true
        );
        
        wp_localize_script('osint-dashboard-script', 'osintDashboard', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('osint_dashboard_nonce'),
            'refreshInterval' => $this->config['refresh_interval'] ?? 60,
            'i18n' => array(
                'loading' => __('جاري التحميل...', 'beiruttime-osint-pro'),
                'error' => __('حدث خطأ', 'beiruttime-osint-pro'),
                'lastUpdate' => __('آخر تحديث', 'beiruttime-osint-pro'),
            )
        ));
    }
    
    /**
     * Register WordPress dashboard widgets
     */
    public function register_wp_widgets() {
        wp_add_dashboard_widget(
            'osint_quick_stats',
            __('إحصائيات OSINT السريعة', 'beiruttime-osint-pro'),
            array($this, 'render_quick_stats_widget')
        );
        
        wp_add_dashboard_widget(
            'osint_recent_alerts',
            __('التنبيهات الأخيرة', 'beiruttime-osint-pro'),
            array($this, 'render_recent_alerts_widget')
        );
    }
    
    /**
     * Render quick stats widget
     */
    public function render_quick_stats_widget() {
        $stats = $this->get_quick_stats();
        include dirname(__FILE__) . '/views/widgets/quick-stats.php';
    }
    
    /**
     * Render recent alerts widget
     */
    public function render_recent_alerts_widget() {
        $alerts = $this->get_recent_alerts();
        include dirname(__FILE__) . '/views/widgets/recent-alerts.php';
    }
    
    /**
     * Get quick statistics
     * 
     * @return array
     */
    public function get_quick_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        return $this->get_cached('quick_stats', function() use ($wpdb, $table) {
            $now = time();
            $day_ago = $now - DAY_IN_SECONDS;
            $week_ago = $now - WEEK_IN_SECONDS;
            
            $total_events = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            $today_events = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE event_timestamp >= %d",
                $day_ago
            ));
            $week_events = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE event_timestamp >= %d",
                $week_ago
            ));
            
            $high_threat_events = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE threat_score >= 70 AND event_timestamp >= %d",
                $day_ago
            ));
            
            $active_alerts = $wpdb->get_var(
                "SELECT COUNT(*) FROM $table WHERE alert_flag = 1 AND alert_status = 'active'"
            );
            
            $hybrid_events = $wpdb->get_var(
                "SELECT COUNT(*) FROM $table WHERE multi_domain_score >= 30"
            );
            
            return array(
                'total_events' => (int)$total_events,
                'today_events' => (int)$today_events,
                'week_events' => (int)$week_events,
                'high_threat_events' => (int)$high_threat_events,
                'active_alerts' => (int)$active_alerts,
                'hybrid_events' => (int)$hybrid_events,
                'last_updated' => $now,
            );
        }, 300); // 5 minutes cache
    }
    
    /**
     * Get recent alerts
     * 
     * @param int $limit Number of alerts to retrieve
     * @return array
     */
    public function get_recent_alerts($limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        return $this->get_cached('recent_alerts_' . $limit, function() use ($wpdb, $table, $limit) {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT id, title, event_type, threat_score, alert_type, alert_priority, event_timestamp
                 FROM $table
                 WHERE alert_flag = 1
                 ORDER BY alert_priority DESC, event_timestamp DESC
                 LIMIT %d",
                $limit
            ), ARRAY_A);
            
            return $results ?: array();
        }, 120); // 2 minutes cache
    }
    
    /**
     * Get comprehensive dashboard data
     * 
     * @param array $filters Optional filters
     * @return array
     */
    public function get_dashboard_data($filters = array()) {
        $data = array(
            'stats' => $this->get_quick_stats(),
            'alerts' => $this->get_recent_alerts(20),
            'trends' => $this->get_trend_data($filters),
            'maps' => $this->get_map_summary($filters),
            'widgets' => $this->get_widget_configs(),
        );
        
        return $data;
    }
    
    /**
     * Get trend data for charts
     * 
     * @param array $filters
     * @return array
     */
    private function get_trend_data($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        return $this->get_cached('trend_data_' . md5(json_encode($filters)), function() use ($wpdb, $table, $filters) {
            $days = isset($filters['days']) ? intval($filters['days']) : 30;
            $from = time() - ($days * DAY_IN_SECONDS);
            
            // Events per day
            $events_per_day = $wpdb->get_results($wpdb->prepare(
                "SELECT DATE(FROM_UNIXTIME(event_timestamp)) as date, COUNT(*) as count
                 FROM $table
                 WHERE event_timestamp >= %d
                 GROUP BY date
                 ORDER BY date ASC",
                $from
            ), ARRAY_A);
            
            // Threat level distribution
            $threat_distribution = $wpdb->get_results(
                "SELECT 
                    SUM(CASE WHEN threat_score < 30 THEN 1 ELSE 0 END) as low,
                    SUM(CASE WHEN threat_score BETWEEN 30 AND 60 THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN threat_score BETWEEN 61 AND 80 THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN threat_score > 80 THEN 1 ELSE 0 END) as critical
                 FROM $table
                 WHERE event_timestamp >= %d",
                $from,
                ARRAY_A
            );
            
            return array(
                'events_per_day' => $events_per_day ?: array(),
                'threat_distribution' => $threat_distribution[0] ?? array(),
            );
        }, 600);
    }
    
    /**
     * Get map summary data
     * 
     * @param array $filters
     * @return array
     */
    private function get_map_summary($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        return $this->get_cached('map_summary', function() use ($wpdb, $table) {
            $locations = $wpdb->get_results(
                "SELECT geo_country, geo_region, geo_city, geo_coordinates, COUNT(*) as event_count, AVG(threat_score) as avg_threat
                 FROM $table
                 WHERE geo_coordinates IS NOT NULL AND geo_coordinates != ''
                 GROUP BY geo_coordinates
                 ORDER BY event_count DESC
                 LIMIT 100",
                ARRAY_A
            );
            
            return array(
                'locations' => $locations ?: array(),
                'total_locations' => count($locations),
            );
        }, 900);
    }
    
    /**
     * Get widget configurations
     * 
     * @return array
     */
    private function get_widget_configs() {
        $saved = get_option('osint_dashboard_layout', array());
        
        $default_widgets = array(
            array('id' => 'quick_stats', 'title' => __('إحصائيات سريعة', 'beiruttime-osint-pro'), 'enabled' => true, 'position' => 1),
            array('id' => 'alerts', 'title' => __('التنبيهات', 'beiruttime-osint-pro'), 'enabled' => true, 'position' => 2),
            array('id' => 'trends', 'title' => __('الاتجاهات', 'beiruttime-osint-pro'), 'enabled' => true, 'position' => 3),
            array('id' => 'map', 'title' => __('الخريطة', 'beiruttime-osint-pro'), 'enabled' => true, 'position' => 4),
        );
        
        return array_merge($default_widgets, $saved);
    }
    
    /**
     * AJAX: Get dashboard data
     */
    public function ajax_get_dashboard_data() {
        check_ajax_referer('osint_dashboard_nonce', 'nonce');
        
        $filters = isset($_POST['filters']) ? json_decode(stripslashes($_POST['filters']), true) : array();
        $data = $this->get_dashboard_data($filters);
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Refresh dashboard
     */
    public function ajax_refresh_dashboard() {
        check_ajax_referer('osint_dashboard_nonce', 'nonce');
        
        $this->clear_cache();
        $data = $this->get_dashboard_data();
        
        wp_send_json_success(array(
            'message' => __('تم تحديث البيانات', 'beiruttime-osint-pro'),
            'data' => $data,
        ));
    }
    
    /**
     * AJAX: Save dashboard layout
     */
    public function ajax_save_layout() {
        check_ajax_referer('osint_dashboard_nonce', 'nonce');
        
        $layout = isset($_POST['layout']) ? json_decode(stripslashes($_POST['layout']), true) : array();
        
        update_option('osint_dashboard_layout', $layout);
        
        wp_send_json_success(array(
            'message' => __('تم حفظ التخطيط', 'beiruttime-osint-pro'),
        ));
    }
    
    /**
     * {@inheritdoc}
     */
    public function render() {
        ob_start();
        $this->render_dashboard_page();
        return ob_get_clean();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function get_default_config() {
        return array(
            'enabled' => true,
            'cache_ttl' => 300,
            'refresh_interval' => 60,
            'show_widgets' => array('quick_stats', 'alerts', 'trends', 'map'),
            'default_view' => 'grid',
            'auto_refresh' => true,
        );
    }
}
