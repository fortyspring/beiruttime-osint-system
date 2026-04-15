<?php
/**
 * Analysis Module
 * 
 * Advanced OSINT analysis module for pattern detection, trend analysis,
 * and predictive intelligence.
 * 
 * @package BeirutTime_OSINT_Pro
 * @subpackage Modules/Analysis
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__DIR__) . '/modules/class-base-module.php';

class OSINT_Analysis_Module extends OSINT_Base_Module {
    
    /**
     * {@inheritdoc}
     */
    public function get_id() {
        return 'analysis';
    }
    
    /**
     * {@inheritdoc}
     */
    public function get_name() {
        return __('التحليل الاستخباراتي', 'beiruttime-osint-pro');
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
        add_action('wp_ajax_osint_analysis_get_patterns', array($this, 'ajax_get_patterns'));
        add_action('wp_ajax_osint_analysis_get_trends', array($this, 'ajax_get_trends'));
        add_action('wp_ajax_osint_analysis_get_predictions', array($this, 'ajax_get_predictions'));
        add_action('wp_ajax_osint_analysis_generate_report', array($this, 'ajax_generate_report'));
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        $this->log('Analysis module initialized');
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'osint') === false) {
            return;
        }
        
        wp_enqueue_script(
            'osint-analysis-script',
            BEIRUTTIME_OSINT_PRO_URL . 'assets/js/analysis.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('osint-analysis-script', 'osintAnalysis', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('osint_analysis_nonce'),
            'i18n' => array(
                'generating' => __('جاري إنشاء التقرير...', 'beiruttime-osint-pro'),
                'error' => __('حدث خطأ', 'beiruttime-osint-pro'),
            )
        ));
    }
    
    /**
     * Detect patterns in events
     * 
     * @param array $filters
     * @return array
     */
    public function detect_patterns($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        return $this->get_cached('analysis_patterns_' . md5(json_encode($filters)), function() use ($wpdb, $table, $filters) {
            $days = isset($filters['days']) ? intval($filters['days']) : 30;
            $from = time() - ($days * DAY_IN_SECONDS);
            
            // Pattern: Recurring events by actor
            $actor_patterns = $wpdb->get_results($wpdb->prepare(
                "SELECT primary_actor, COUNT(*) as frequency, AVG(threat_score) as avg_threat
                 FROM $table
                 WHERE event_timestamp >= %d AND primary_actor IS NOT NULL
                 GROUP BY primary_actor
                 HAVING COUNT(*) > 2
                 ORDER BY frequency DESC",
                $from
            ), ARRAY_A);
            
            // Pattern: Geographic clustering
            $geo_patterns = $wpdb->get_results($wpdb->prepare(
                "SELECT geo_country, geo_city, COUNT(*) as frequency
                 FROM $table
                 WHERE event_timestamp >= %d AND geo_country IS NOT NULL
                 GROUP BY geo_country, geo_city
                 HAVING COUNT(*) > 3
                 ORDER BY frequency DESC
                 LIMIT 20",
                $from
            ), ARRAY_A);
            
            // Pattern: Time-based patterns
            $time_patterns = $wpdb->get_results($wpdb->prepare(
                "SELECT HOUR(FROM_UNIXTIME(event_timestamp)) as hour, COUNT(*) as count
                 FROM $table
                 WHERE event_timestamp >= %d
                 GROUP BY hour
                 ORDER BY count DESC",
                $from
            ), ARRAY_A);
            
            return array(
                'actor_patterns' => $actor_patterns ?: array(),
                'geo_patterns' => $geo_patterns ?: array(),
                'time_patterns' => $time_patterns ?: array(),
                'total_patterns' => count($actor_patterns) + count($geo_patterns),
            );
        }, 1800);
    }
    
    /**
     * Analyze trends
     * 
     * @param array $filters
     * @return array
     */
    public function analyze_trends($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        return $this->get_cached('analysis_trends_' . md5(json_encode($filters)), function() use ($wpdb, $table, $filters) {
            $days = isset($filters['days']) ? intval($filters['days']) : 30;
            $from = time() - ($days * DAY_IN_SECONDS);
            $half_period = $from + (($days * DAY_IN_SECONDS) / 2);
            
            // Compare first half vs second half
            $first_half = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE event_timestamp BETWEEN %d AND %d",
                $from, $half_period
            ));
            
            $second_half = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE event_timestamp BETWEEN %d AND %d",
                $half_period, time()
            ));
            
            $trend_direction = 'stable';
            $trend_percentage = 0;
            
            if ($first_half > 0) {
                $trend_percentage = (($second_half - $first_half) / $first_half) * 100;
                
                if ($trend_percentage > 20) {
                    $trend_direction = 'increasing';
                } elseif ($trend_percentage < -20) {
                    $trend_direction = 'decreasing';
                }
            }
            
            // Threat trend
            $avg_threat_first = $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(threat_score) FROM $table WHERE event_timestamp BETWEEN %d AND %d",
                $from, $half_period
            ));
            
            $avg_threat_second = $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(threat_score) FROM $table WHERE event_timestamp BETWEEN %d AND %d",
                $half_period, time()
            ));
            
            $threat_trend = 'stable';
            if ($avg_threat_second > $avg_threat_first + 10) {
                $threat_trend = 'escalating';
            } elseif ($avg_threat_second < $avg_threat_first - 10) {
                $threat_trend = 'de-escalating';
            }
            
            // Hybrid warfare trend
            $hybrid_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE multi_domain_score >= 30 AND event_timestamp >= %d",
                $from
            ));
            
            $hybrid_percentage = $first_half > 0 ? ($hybrid_count / ($first_half + $second_half)) * 100 : 0;
            
            return array(
                'event_trend' => array(
                    'direction' => $trend_direction,
                    'percentage' => round($trend_percentage, 2),
                    'first_half' => intval($first_half),
                    'second_half' => intval($second_half),
                ),
                'threat_trend' => array(
                    'direction' => $threat_trend,
                    'first_avg' => round($avg_threat_first ?? 0, 2),
                    'second_avg' => round($avg_threat_second ?? 0, 2),
                ),
                'hybrid_warfare' => array(
                    'count' => intval($hybrid_count),
                    'percentage' => round($hybrid_percentage, 2),
                ),
            );
        }, 1800);
    }
    
    /**
     * Generate predictions
     * 
     * @param array $filters
     * @return array
     */
    public function generate_predictions($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        return $this->get_cached('analysis_predictions', function() use ($wpdb, $table) {
            $predictions = array();
            
            // Prediction: Escalation risk
            $recent_high_threat = $wpdb->get_var(
                "SELECT COUNT(*) FROM $table WHERE threat_score >= 70 AND event_timestamp >= UNIX_TIMESTAMP(NOW() - INTERVAL 7 DAY)"
            );
            
            $escalation_risk = 'low';
            if ($recent_high_threat >= 10) {
                $escalation_risk = 'critical';
            } elseif ($recent_high_threat >= 5) {
                $escalation_risk = 'high';
            } elseif ($recent_high_threat >= 2) {
                $escalation_risk = 'medium';
            }
            
            $predictions[] = array(
                'type' => 'escalation',
                'risk_level' => $escalation_risk,
                'indicator' => sprintf(__('عدد الأحداث عالية التهديد خلال 7 أيام: %d', 'beiruttime-osint-pro'), $recent_high_threat),
                'recommendation' => $this->get_escalation_recommendation($escalation_risk),
            );
            
            // Prediction: Geographic spread
            $active_regions = $wpdb->get_var(
                "SELECT COUNT(DISTINCT geo_region) FROM $table WHERE event_timestamp >= UNIX_TIMESTAMP(NOW() - INTERVAL 14 DAY)"
            );
            
            $spread_risk = 'low';
            if ($active_regions >= 10) {
                $spread_risk = 'high';
            } elseif ($active_regions >= 5) {
                $spread_risk = 'medium';
            }
            
            $predictions[] = array(
                'type' => 'geographic_spread',
                'risk_level' => $spread_risk,
                'indicator' => sprintf(__('المناطق النشطة: %d', 'beiruttime-osint-pro'), $active_regions),
                'recommendation' => $this->get_spread_recommendation($spread_risk),
            );
            
            return array(
                'predictions' => $predictions,
                'generated_at' => time(),
                'confidence' => 'medium',
            );
        }, 3600);
    }
    
    /**
     * Get escalation recommendation
     * 
     * @param string $risk_level
     * @return string
     */
    private function get_escalation_recommendation($risk_level) {
        $recommendations = array(
            'low' => __('مواصلة المراقبة الروتينية', 'beiruttime-osint-pro'),
            'medium' => __('زيادة وتيرة المتابعة وتحديث التقييمات', 'beiruttime-osint-pro'),
            'high' => __('تفعيل بروتوكولات الإنذار المبكر وإبلاغ الجهات المعنية', 'beiruttime-osint-pro'),
            'critical' => __('غرفة عمليات فورية وتنسيق عاجل مع جميع الأطراف', 'beiruttime-osint-pro'),
        );
        
        return $recommendations[$risk_level] ?? $recommendations['low'];
    }
    
    /**
     * Get spread recommendation
     * 
     * @param string $risk_level
     * @return string
     */
    private function get_spread_recommendation($risk_level) {
        $recommendations = array(
            'low' => __('الوضع تحت السيطرة الجغرافية', 'beiruttime-osint-pro'),
            'medium' => __('مراقبة توسع النطاق الجغرافي للأحداث', 'beiruttime-osint-pro'),
            'high' => __('استعداد لانتشار إقليمي واسع وتحضير سيناريوهات احتواء', 'beiruttime-osint-pro'),
        );
        
        return $recommendations[$risk_level] ?? $recommendations['low'];
    }
    
    /**
     * Generate analysis report
     * 
     * @param array $options
     * @return array
     */
    public function generate_report($options = array()) {
        $patterns = $this->detect_patterns($options);
        $trends = $this->analyze_trends($options);
        $predictions = $this->generate_predictions($options);
        
        $report = array(
            'title' => __('تقرير التحليل الاستخباراتي', 'beiruttime-osint-pro'),
            'generated_at' => date('Y-m-d H:i:s'),
            'period' => sprintf(__('آخر %d يوم', 'beiruttime-osint-pro'), $options['days'] ?? 30),
            'sections' => array(
                'patterns' => $patterns,
                'trends' => $trends,
                'predictions' => $predictions,
            ),
            'summary' => $this->generate_summary($patterns, $trends, $predictions),
        );
        
        return $report;
    }
    
    /**
     * Generate executive summary
     * 
     * @param array $patterns
     * @param array $trends
     * @param array $predictions
     * @return string
     */
    private function generate_summary($patterns, $trends, $predictions) {
        $summary_parts = array();
        
        // Event trend summary
        if ($trends['event_trend']['direction'] === 'increasing') {
            $summary_parts[] = sprintf(
                __('شهدت الفترة زيادة بنسبة %d%% في عدد الأحداث', 'beiruttime-osint-pro'),
                $trends['event_trend']['percentage']
            );
        } elseif ($trends['event_trend']['direction'] === 'decreasing') {
            $summary_parts[] = sprintf(
                __('شهدت الفترة انخفاضاً بنسبة %d%% في عدد الأحداث', 'beiruttime-osint-pro'),
                abs($trends['event_trend']['percentage'])
            );
        }
        
        // Threat trend summary
        if ($trends['threat_trend']['direction'] === 'escalating') {
            $summary_parts[] = __('مؤشرات تصعيد في مستوى التهديدات', 'beiruttime-osint-pro');
        }
        
        // Prediction summary
        foreach ($predictions['predictions'] as $prediction) {
            if (in_array($prediction['risk_level'], array('high', 'critical'))) {
                $summary_parts[] = sprintf(
                    __('تحذير: خطر %s - %s', 'beiruttime-osint-pro'),
                    $prediction['type'],
                    $prediction['recommendation']
                );
            }
        }
        
        return implode('. ', $summary_parts);
    }
    
    /**
     * AJAX: Get patterns
     */
    public function ajax_get_patterns() {
        check_ajax_referer('osint_analysis_nonce', 'nonce');
        
        $filters = isset($_POST['filters']) ? json_decode(stripslashes($_POST['filters']), true) : array();
        $patterns = $this->detect_patterns($filters);
        
        wp_send_json_success($patterns);
    }
    
    /**
     * AJAX: Get trends
     */
    public function ajax_get_trends() {
        check_ajax_referer('osint_analysis_nonce', 'nonce');
        
        $filters = isset($_POST['filters']) ? json_decode(stripslashes($_POST['filters']), true) : array();
        $trends = $this->analyze_trends($filters);
        
        wp_send_json_success($trends);
    }
    
    /**
     * AJAX: Get predictions
     */
    public function ajax_get_predictions() {
        check_ajax_referer('osint_analysis_nonce', 'nonce');
        
        $predictions = $this->generate_predictions();
        
        wp_send_json_success($predictions);
    }
    
    /**
     * AJAX: Generate report
     */
    public function ajax_generate_report() {
        check_ajax_referer('osint_analysis_nonce', 'nonce');
        
        $options = isset($_POST['options']) ? json_decode(stripslashes($_POST['options']), true) : array();
        $report = $this->generate_report($options);
        
        wp_send_json_success($report);
    }
    
    /**
     * {@inheritdoc}
     */
    public function render() {
        include dirname(__FILE__) . '/views/analysis-page.php';
    }
    
    /**
     * {@inheritdoc}
     */
    protected function get_default_config() {
        return array(
            'enabled' => true,
            'cache_ttl' => 1800,
            'auto_analysis' => true,
            'analysis_interval' => 3600,
            'enable_predictions' => true,
        );
    }
}
