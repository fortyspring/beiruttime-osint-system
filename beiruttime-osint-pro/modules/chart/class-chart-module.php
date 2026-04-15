<?php
/**
 * Chart Module
 * 
 * Data visualization and charting module for OSINT analytics.
 * Provides various chart types: line, bar, pie, radar, heatmap.
 * 
 * @package BeirutTime_OSINT_Pro
 * @subpackage Modules/Chart
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__DIR__) . '/modules/class-base-module.php';

class OSINT_Chart_Module extends OSINT_Base_Module {
    
    /**
     * {@inheritdoc}
     */
    public function get_id() {
        return 'chart';
    }
    
    /**
     * {@inheritdoc}
     */
    public function get_name() {
        return __('الرسوم البيانية', 'beiruttime-osint-pro');
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
        
        // Register AJAX handlers
        add_action('wp_ajax_osint_chart_get_data', array($this, 'ajax_get_chart_data'));
        add_action('wp_ajax_osint_chart_export', array($this, 'ajax_export_chart'));
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        $this->log('Chart module initialized');
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'osint') === false) {
            return;
        }
        
        // Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            array(),
            '4.4.0',
            true
        );
        
        // Custom chart script
        wp_enqueue_script(
            'osint-chart-script',
            BEIRUTTIME_OSINT_PRO_URL . 'assets/js/charts.js',
            array('jquery', 'chartjs'),
            $this->version,
            true
        );
        
        wp_localize_script('osint-chart-script', 'osintChart', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('osint_chart_nonce'),
            'i18n' => array(
                'export' => __('تصدير', 'beiruttime-osint-pro'),
                'loading' => __('جاري التحميل...', 'beiruttime-osint-pro'),
            )
        ));
    }
    
    /**
     * Get chart data by type
     * 
     * @param string $type Chart type
     * @param array $filters Optional filters
     * @return array
     */
    public function get_chart_data($type, $filters = array()) {
        switch ($type) {
            case 'events_trend':
                return $this->get_events_trend_data($filters);
            case 'threat_distribution':
                return $this->get_threat_distribution_data($filters);
            case 'actor_analysis':
                return $this->get_actor_analysis_data($filters);
            case 'hybrid_layers':
                return $this->get_hybrid_layers_data($filters);
            case 'geographic_distribution':
                return $this->get_geographic_distribution_data($filters);
            default:
                return array();
        }
    }
    
    /**
     * Get events trend data
     * 
     * @param array $filters
     * @return array
     */
    private function get_events_trend_data($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        return $this->get_cached('chart_events_trend_' . md5(json_encode($filters)), function() use ($wpdb, $table, $filters) {
            $days = isset($filters['days']) ? intval($filters['days']) : 30;
            $from = time() - ($days * DAY_IN_SECONDS);
            
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT DATE(FROM_UNIXTIME(event_timestamp)) as date, COUNT(*) as count
                 FROM $table
                 WHERE event_timestamp >= %d
                 GROUP BY date
                 ORDER BY date ASC",
                $from
            ), ARRAY_A);
            
            return array(
                'labels' => array_column($results, 'date'),
                'data' => array_map('intval', array_column($results, 'count')),
            );
        }, 600);
    }
    
    /**
     * Get threat distribution data
     * 
     * @param array $filters
     * @return array
     */
    private function get_threat_distribution_data($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        return $this->get_cached('chart_threat_dist', function() use ($wpdb, $table, $filters) {
            $results = $wpdb->get_row(
                "SELECT 
                    SUM(CASE WHEN threat_score < 30 THEN 1 ELSE 0 END) as low,
                    SUM(CASE WHEN threat_score BETWEEN 30 AND 60 THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN threat_score BETWEEN 61 AND 80 THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN threat_score > 80 THEN 1 ELSE 0 END) as critical
                 FROM $table",
                ARRAY_A
            );
            
            return array(
                'labels' => array(__('منخفض', 'beiruttime-osint-pro'), __('متوسط', 'beiruttime-osint-pro'), __('عالي', 'beiruttime-osint-pro'), __('حرج', 'beiruttime-osint-pro')),
                'data' => array(
                    intval($results['low'] ?? 0),
                    intval($results['medium'] ?? 0),
                    intval($results['high'] ?? 0),
                    intval($results['critical'] ?? 0),
                ),
                'colors' => array('#28a745', '#ffc107', '#fd7e14', '#dc3545'),
            );
        }, 900);
    }
    
    /**
     * Get actor analysis data
     * 
     * @param array $filters
     * @return array
     */
    private function get_actor_analysis_data($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        return $this->get_cached('chart_actors', function() use ($wpdb, $table) {
            $results = $wpdb->get_results(
                "SELECT primary_actor, COUNT(*) as count
                 FROM $table
                 WHERE primary_actor IS NOT NULL AND primary_actor != ''
                 GROUP BY primary_actor
                 ORDER BY count DESC
                 LIMIT 10",
                ARRAY_A
            );
            
            return array(
                'labels' => array_column($results, 'primary_actor'),
                'data' => array_map('intval', array_column($results, 'count')),
            );
        }, 1200);
    }
    
    /**
     * Get hybrid warfare layers data
     * 
     * @param array $filters
     * @return array
     */
    private function get_hybrid_layers_data($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        return $this->get_cached('chart_hybrid_layers', function() use ($wpdb, $table) {
            // Count events by hybrid layer
            $layers = array(
                'military' => __('عسكري', 'beiruttime-osint-pro'),
                'security' => __('أمني', 'beiruttime-osint-pro'),
                'cyber' => __('سيبراني', 'beiruttime-osint-pro'),
                'political' => __('سياسي', 'beiruttime-osint-pro'),
                'economic' => __('اقتصادي', 'beiruttime-osint-pro'),
                'social' => __('اجتماعي', 'beiruttime-osint-pro'),
                'energy' => __('طاقة', 'beiruttime-osint-pro'),
                'infrastructure' => __('مرافق', 'beiruttime-osint-pro'),
                'geostrategic' => __('جيواستراتيجي', 'beiruttime-osint-pro'),
            );
            
            $data = array();
            foreach ($layers as $key => $label) {
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE hybrid_layers LIKE %s",
                    '%' . $key . '%'
                ));
                
                $data[] = array(
                    'layer' => $key,
                    'label' => $label,
                    'count' => intval($count),
                );
            }
            
            return array(
                'labels' => array_column($data, 'label'),
                'data' => array_column($data, 'count'),
            );
        }, 1800);
    }
    
    /**
     * Get geographic distribution data
     * 
     * @param array $filters
     * @return array
     */
    private function get_geographic_distribution_data($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        return $this->get_cached('chart_geo_dist', function() use ($wpdb, $table) {
            $results = $wpdb->get_results(
                "SELECT geo_country, COUNT(*) as count
                 FROM $table
                 WHERE geo_country IS NOT NULL AND geo_country != ''
                 GROUP BY geo_country
                 ORDER BY count DESC
                 LIMIT 15",
                ARRAY_A
            );
            
            return array(
                'labels' => array_column($results, 'geo_country'),
                'data' => array_map('intval', array_column($results, 'count')),
            );
        }, 900);
    }
    
    /**
     * AJAX: Get chart data
     */
    public function ajax_get_chart_data() {
        check_ajax_referer('osint_chart_nonce', 'nonce');
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $filters = isset($_POST['filters']) ? json_decode(stripslashes($_POST['filters']), true) : array();
        
        if (empty($type)) {
            wp_send_json_error(array('message' => __('نوع الرسم مطلوب', 'beiruttime-osint-pro')));
        }
        
        $data = $this->get_chart_data($type, $filters);
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Export chart
     */
    public function ajax_export_chart() {
        check_ajax_referer('osint_chart_nonce', 'nonce');
        
        $chart_id = isset($_POST['chart_id']) ? sanitize_text_field($_POST['chart_id']) : '';
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'png';
        
        // Generate export URL or file
        $export_url = add_query_arg(array(
            'chart' => $chart_id,
            'format' => $format,
            't' => time(),
        ), admin_url('admin-ajax.php?action=osint_chart_download'));
        
        wp_send_json_success(array(
            'url' => $export_url,
            'message' => __('تم تجهيز التصدير', 'beiruttime-osint-pro'),
        ));
    }
    
    /**
     * Render chart container
     * 
     * @param string $type Chart type
     * @param array $options Chart options
     * @return string HTML
     */
    public function render_chart($type, $options = array()) {
        $chart_id = 'osint-chart-' . uniqid();
        $width = isset($options['width']) ? $options['width'] : '100%';
        $height = isset($options['height']) ? $options['height'] : '400px';
        
        ob_start();
        ?>
        <div class="osint-chart-container" id="<?php echo esc_attr($chart_id); ?>" style="width: <?php echo esc_attr($width); ?>; height: <?php echo esc_attr($height); ?>;">
            <canvas id="<?php echo esc_attr($chart_id); ?>-canvas"></canvas>
        </div>
        <script>
            jQuery(document).ready(function($) {
                osintChartModule.renderChart('<?php echo esc_js($chart_id); ?>', '<?php echo esc_js($type); ?>', <?php echo json_encode($options); ?>);
            });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * {@inheritdoc}
     */
    public function render() {
        include dirname(__FILE__) . '/views/charts-page.php';
    }
    
    /**
     * {@inheritdoc}
     */
    protected function get_default_config() {
        return array(
            'enabled' => true,
            'cache_ttl' => 600,
            'default_chart_type' => 'line',
            'animation_enabled' => true,
            'responsive' => true,
        );
    }
}
