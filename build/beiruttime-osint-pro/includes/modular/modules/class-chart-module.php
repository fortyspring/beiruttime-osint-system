<?php
/**
 * OSINT Chart Module - Modular System
 * Data visualization and charting capabilities
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSINT_Chart_Module {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', [$this, 'register_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_osint_get_chart_data', [$this, 'ajax_get_chart_data']);
    }
    
    public function register_submenu() {
        add_submenu_page(
            'osint-pro-dashboard',
            'Charts & Analytics',
            'Charts',
            'manage_options',
            'osint-pro-charts',
            [$this, 'render_charts']
        );
    }
    
    public function enqueue_assets($hook) {
        if ($hook !== 'osint-pro-page_osint-pro-charts') {
            return;
        }
        
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', [], '4.4.0', true);
        wp_enqueue_script('osint-charts', OSINT_PRO_PLUGIN_URL . 'assets/js/chart-module.js', ['jquery', 'chartjs'], OSINT_PRO_VERSION, true);
        
        wp_localize_script('osint-charts', 'osintChartConfig', [
            'colors' => [
                'critical' => '#dc3545',
                'high' => '#fd7e14',
                'medium' => '#ffc107',
                'low' => '#28a745',
                'info' => '#17a2b8'
            ],
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('osint_pro_dashboard_nonce')
        ]);
    }
    
    public function render_charts() {
        ?>
        <div class="wrap osint-pro-charts">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="osint-chart-controls">
                <select id="osint-chart-period">
                    <option value="7">Last 7 Days</option>
                    <option value="30">Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                    <option value="365">Last Year</option>
                </select>
                <select id="osint-chart-type">
                    <option value="timeline">Timeline</option>
                    <option value="severity">By Severity</option>
                    <option value="sources">By Source</option>
                    <option value="categories">By Category</option>
                </select>
                <button id="osint-chart-generate" class="button button-primary">Generate Chart</button>
            </div>
            
            <div class="osint-charts-grid">
                <div class="osint-chart-container">
                    <h3>Event Timeline</h3>
                    <canvas id="osint-timeline-chart"></canvas>
                </div>
                
                <div class="osint-chart-container">
                    <h3>Severity Distribution</h3>
                    <canvas id="osint-severity-chart"></canvas>
                </div>
                
                <div class="osint-chart-container">
                    <h3>Source Breakdown</h3>
                    <canvas id="osint-sources-chart"></canvas>
                </div>
                
                <div class="osint-chart-container">
                    <h3>Top Categories</h3>
                    <canvas id="osint-categories-chart"></canvas>
                </div>
            </div>
            
            <div class="osint-chart-stats">
                <h3>Quick Statistics</h3>
                <div id="osint-chart-statistics">
                    <?php $this->render_quick_stats(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function ajax_get_chart_data() {
        check_ajax_referer('osint_pro_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'osint_pro_events';
        
        $period = intval($_POST['period'] ?? 30);
        $type = sanitize_text_field($_POST['type'] ?? 'timeline');
        
        $start_date = date('Y-m-d', strtotime("-{$period} days"));
        
        switch ($type) {
            case 'timeline':
                $data = $this->get_timeline_data($table, $start_date);
                break;
            case 'severity':
                $data = $this->get_severity_data($table, $start_date);
                break;
            case 'sources':
                $data = $this->get_sources_data($table, $start_date);
                break;
            case 'categories':
                $data = $this->get_categories_data($table, $start_date);
                break;
            default:
                $data = [];
        }
        
        wp_send_json_success($data);
    }
    
    private function get_timeline_data($table, $start_date) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM {$table} 
             WHERE created_at >= %s 
             GROUP BY DATE(created_at) 
             ORDER BY date ASC",
            $start_date
        ));
        
        return [
            'labels' => array_column($results, 'date'),
            'values' => array_column($results, 'count')
        ];
    }
    
    private function get_severity_data($table, $start_date) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT severity, COUNT(*) as count 
             FROM {$table} 
             WHERE created_at >= %s 
             GROUP BY severity",
            $start_date
        ));
        
        return [
            'labels' => array_column($results, 'severity'),
            'values' => array_column($results, 'count')
        ];
    }
    
    private function get_sources_data($table, $start_date) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT source, COUNT(*) as count 
             FROM {$table} 
             WHERE created_at >= %s 
             GROUP BY source 
             ORDER BY count DESC 
             LIMIT 10",
            $start_date
        ));
        
        return [
            'labels' => array_column($results, 'source'),
            'values' => array_column($results, 'count')
        ];
    }
    
    private function get_categories_data($table, $start_date) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT category, COUNT(*) as count 
             FROM {$table} 
             WHERE created_at >= %s 
             GROUP BY category 
             ORDER BY count DESC 
             LIMIT 10",
            $start_date
        ));
        
        return [
            'labels' => array_column($results, 'category'),
            'values' => array_column($results, 'count')
        ];
    }
    
    private function render_quick_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'osint_pro_events';
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $critical = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE severity = 'critical'"));
        $today = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = CURDATE()");
        $avg_per_day = round($total / max(1, DATEDIFF(CURDATE(), (SELECT MIN(created_at) FROM {$table}))), 2);
        
        ?>
        <div class="osint-stat-box">
            <span class="stat-label">Total Events</span>
            <span class="stat-value"><?php echo number_format($total); ?></span>
        </div>
        <div class="osint-stat-box">
            <span class="stat-label">Critical</span>
            <span class="stat-value critical"><?php echo number_format($critical); ?></span>
        </div>
        <div class="osint-stat-box">
            <span class="stat-label">Today</span>
            <span class="stat-value"><?php echo number_format($today); ?></span>
        </div>
        <div class="osint-stat-box">
            <span class="stat-label">Avg/Day</span>
            <span class="stat-value"><?php echo number_format($avg_per_day, 1); ?></span>
        </div>
        <?php
    }
}
