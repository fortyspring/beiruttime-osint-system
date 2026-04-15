<?php
/**
 * Beiruttime OSINT - Hybrid Warfare Module
 * وحدة الحرب المركبة المتقدمة
 * 
 * تعرض مؤشرات الحرب المركبة، الطبقات النشطة، والتحليلات المتقدمة
 * 
 * @package Beiruttime_OSINT_Pro
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__DIR__) . '/class-base-module.php';

class OSINT_Hybrid_Warfare_Module extends OSINT_Base_Module {
    
    /**
     * معرف الوحدة
     */
    protected $module_id = 'hybrid_warfare';
    
    /**
     * اسم الوحدة
     */
    protected $module_name = 'الحرب المركبة';
    
    /**
     * وصف الوحدة
     */
    protected $module_description = 'نظام متقدم لتحليل وعرض طبقات الحرب المركبة ومؤشراتها';
    
    /**
     * قاموس طبقات الحرب المركبة التسع
     */
    private $warfare_layers = array(
        'military' => array(
            'name' => 'الطبقة العسكرية',
            'icon' => 'dashicons-shield',
            'keywords' => array('غارة', 'قصف', 'استهداف', 'هجوم', 'اشتباك', 'صاروخ', 'دبابة', 'طائرة', 'جنود', 'جبهة', 'معارك')
        ),
        'security' => array(
            'name' => 'الطبقة الأمنية',
            'icon' => 'dashicons-id',
            'keywords' => array('اعتقال', 'مداهمة', 'تفكيك', 'خلية', 'أمن', 'حاجز', 'تفتيش', 'مطلوب')
        ),
        'cyber' => array(
            'name' => 'الطبقة السيبرانية',
            'icon' => 'dashicons-networking',
            'keywords' => array('اختراق', 'قرصنة', 'تسريب', 'سيبراني', 'رقمي', 'برمجيات', 'بيانات', 'تشفير')
        ),
        'geographic' => array(
            'name' => 'الطبقة الجغرافية',
            'icon' => 'dashicons-location-alt',
            'keywords' => array('قمر صناعي', 'صورة فضائية', 'إحداثيات', 'موقع', 'خريطة', 'تحصين', 'تدمير')
        ),
        'political' => array(
            'name' => 'الطبقة السياسية',
            'icon' => 'dashicons-flag',
            'keywords' => array('تصريح', 'عقوبة', 'مفاوضة', 'دبلوماسي', 'قرار', 'بيان', 'زيارة', 'وفد')
        ),
        'economic' => array(
            'name' => 'الطبقة الاقتصادية',
            'icon' => 'dashicons-chart-line',
            'keywords' => array('نفط', 'غاز', 'اقتصاد', 'سوق', 'عملة', 'تجارة', 'ميناء', 'عقوبات مالية')
        ),
        'social' => array(
            'name' => 'الطبقة الاجتماعية',
            'icon' => 'dashicons-groups',
            'keywords' => array('احتجاج', 'تظاهر', 'تحريض', 'مجتمع', 'رأي عام', 'حشد', 'تعبئة')
        ),
        'energy' => array(
            'name' => 'طبقة الطاقة',
            'icon' => 'dashicons-lightbulb',
            'keywords' => array('كهرباء', 'طاقة', 'محطة توليد', 'مصفاة', 'أنابيب', 'وقود', 'غاز طبيعي')
        ),
        'strategic' => array(
            'name' => 'الطبقة الاستراتيجية',
            'icon' => 'dashicons-world',
            'keywords' => array('مضيق', 'ممر بحري', 'قاعدة', 'نقطة استراتيجية', 'نفوذ', 'سيطرة', 'مياه دولية')
        )
    );
    
    /**
     * تهيئة الوحدة
     */
    public function init() {
        parent::init();
        
        // تسجيل نقاط AJAX
        add_action('wp_ajax_osint_get_hybrid_dashboard', array($this, 'ajax_get_hybrid_dashboard'));
        add_action('wp_ajax_osint_get_layer_distribution', array($this, 'ajax_get_layer_distribution'));
        add_action('wp_ajax_osint_get_high_threat_events', array($this, 'ajax_get_high_threat_events'));
        add_action('wp_ajax_osint_get_multi_domain_events', array($this, 'ajax_get_multi_domain_events'));
        
        // إضافة قائمة الإدارة
        add_filter('osint_admin_menu_items', array($this, 'add_admin_menu_item'));
        
        // تسجيل الـ Shortcode
        add_shortcode('osint_hybrid_dashboard', array($this, 'render_dashboard_shortcode'));
    }
    
    /**
     * إضافة عنصر القائمة للإدارة
     */
    public function add_admin_menu_item($items) {
        $items[] = array(
            'id' => 'hybrid-warfare',
            'title' => 'الحرب المركبة',
            'callback' => array($this, 'render_admin_page'),
            'icon' => 'dashicons-shield',
            'position' => 5
        );
        return $items;
    }
    
    /**
     * عرض صفحة الإدارة الرئيسية للحرب المركبة
     */
    public function render_admin_page() {
        ?>
        <div class="wrap osint-hybrid-warfare-page">
            <h1 style="margin-bottom: 20px;">
                <span class="dashicons dashicons-shield" style="font-size: 30px; vertical-align: middle;"></span>
                <?php _e('لوحة الحرب المركبة', 'beiruttime-osint'); ?>
            </h1>
            
            <!-- بطاقات المؤشرات السريعة -->
            <div class="osint-cards-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <?php $this->render_indicator_cards(); ?>
            </div>
            
            <!-- الرسم البياني لتوزيع الطبقات -->
            <div class="osint-chart-container" style="background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2><?php _e('توزيع الأحداث حسب الطبقات', 'beiruttime-osint'); ?></h2>
                <canvas id="layerDistributionChart" height="100"></canvas>
            </div>
            
            <!-- جدول الأحداث عالية الخطورة -->
            <div class="osint-table-container" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2><?php _e('الأحداث عالية الخطورة', 'beiruttime-osint'); ?></h2>
                <div id="highThreatEventsTable">
                    <p style="text-align: center; color: #666;">جاري التحميل...</p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // تحميل توزيع الطبقات
            osintLoadLayerDistribution();
            
            // تحميل الأحداث عالية الخطورة
            osintLoadHighThreatEvents();
        });
        
        function osintLoadLayerDistribution() {
            jQuery.post(ajaxurl, {
                action: 'osint_get_layer_distribution',
                nonce: '<?php echo wp_create_nonce("osint_hybrid_nonce"); ?>'
            }, function(response) {
                if (response.success) {
                    osintRenderLayerChart(response.data);
                }
            });
        }
        
        function osintLoadHighThreatEvents() {
            jQuery.post(ajaxurl, {
                action: 'osint_get_high_threat_events',
                limit: 10,
                nonce: '<?php echo wp_create_nonce("osint_hybrid_nonce"); ?>'
            }, function(response) {
                if (response.success) {
                    osintRenderHighThreatTable(response.data);
                } else {
                    jQuery('#highThreatEventsTable').html('<p>لا توجد أحداث عالية الخطورة</p>');
                }
            });
        }
        
        function osintRenderLayerChart(data) {
            var ctx = document.getElementById('layerDistributionChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'عدد الأحداث',
                        data: data.values,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(199, 199, 199, 0.7)',
                            'rgba(83, 102, 255, 0.7)',
                            'rgba(255, 99, 255, 0.7)'
                        ],
                        borderColor: [
                            'rgb(255, 99, 132)',
                            'rgb(54, 162, 235)',
                            'rgb(255, 206, 86)',
                            'rgb(75, 192, 192)',
                            'rgb(153, 102, 255)',
                            'rgb(255, 159, 64)',
                            'rgb(199, 199, 199)',
                            'rgb(83, 102, 255)',
                            'rgb(255, 99, 255)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        function osintRenderHighThreatTable(events) {
            var html = '<table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr>';
            html += '<th><?php _e("العنوان", "beiruttime-osint"); ?></th>';
            html += '<th><?php _e("الطبقات", "beiruttime-osint"); ?></th>';
            html += '<th><?php _e("درجة التهديد", "beiruttime-osint"); ?></th>';
            html += '<th><?php _e("التاريخ", "beiruttime-osint"); ?></th>';
            html += '</tr></thead><tbody>';
            
            events.forEach(function(event) {
                html += '<tr>';
                html += '<td>' + event.title + '</td>';
                html += '<td>' + event.layers.join(', ') + '</td>';
                html += '<td><span style="color: red; font-weight: bold;">' + event.threat_score + '</span></td>';
                html += '<td>' + event.date + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            jQuery('#highThreatEventsTable').html(html);
        }
        </script>
        <?php
    }
    
    /**
     * عرض بطاقات المؤشرات
     */
    private function render_indicator_cards() {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        // إجمالي الأحداث (آخر 24 ساعة)
        $total_events = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE event_timestamp >= UNIX_TIMESTAMP(NOW() - INTERVAL 24 HOUR)");
        
        // أحداث متعددة المجالات
        $multi_domain = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE multi_domain_score >= 30 AND event_timestamp >= UNIX_TIMESTAMP(NOW() - INTERVAL 24 HOUR)");
        
        // أحداث عالية التهديد
        $high_threat = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE threat_score >= 60 AND event_timestamp >= UNIX_TIMESTAMP(NOW() - INTERVAL 24 HOUR)");
        
        // تنبيهات نشطة
        $active_alerts = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE alert_flag = 1 AND alert_status = 'active' AND event_timestamp >= UNIX_TIMESTAMP(NOW() - INTERVAL 24 HOUR)");
        
        $cards = array(
            array(
                'title' => 'إجمالي الأحداث (24س)',
                'value' => $total_events,
                'icon' => 'dashicons-media-clip',
                'color' => '#2271b1'
            ),
            array(
                'title' => 'متعددة المجالات',
                'value' => $multi_domain,
                'icon' => 'dashicons-layers',
                'color' => '#8c5ccc'
            ),
            array(
                'title' => 'عالية التهديد',
                'value' => $high_threat,
                'icon' => 'dashicons-warning',
                'color' => '#d63638'
            ),
            array(
                'title' => 'تنبيهات نشطة',
                'value' => $active_alerts,
                'icon' => 'dashicons-bell',
                'color' => '#dba617'
            )
        );
        
        foreach ($cards as $card) {
            ?>
            <div class="osint-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-right: 4px solid <?php echo $card['color']; ?>;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #666;"><?php echo $card['title']; ?></h3>
                        <p style="margin: 0; font-size: 32px; font-weight: bold; color: #333;"><?php echo number_format($card['value']); ?></p>
                    </div>
                    <div style="font-size: 40px; color: <?php echo $card['color']; ?>;">
                        <span class="dashicons <?php echo $card['icon']; ?>"></span>
                    </div>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * AJAX: الحصول على بيانات لوحة الحرب المركبة
     */
    public function ajax_get_hybrid_dashboard() {
        check_ajax_referer('osint_hybrid_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        $data = array(
            'total_events' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE event_timestamp >= UNIX_TIMESTAMP(NOW() - INTERVAL 24 HOUR)"),
            'multi_domain' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE multi_domain_score >= 30 AND event_timestamp >= UNIX_TIMESTAMP(NOW() - INTERVAL 24 HOUR)"),
            'high_threat' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE threat_score >= 60 AND event_timestamp >= UNIX_TIMESTAMP(NOW() - INTERVAL 24 HOUR)"),
            'active_alerts' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE alert_flag = 1 AND alert_status = 'active'")
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: الحصول على توزيع الطبقات
     */
    public function ajax_get_layer_distribution() {
        check_ajax_referer('osint_hybrid_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        $labels = array();
        $values = array();
        
        foreach ($this->warfare_layers as $key => $layer) {
            $labels[] = $layer['name'];
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE hybrid_layers LIKE %s AND event_timestamp >= UNIX_TIMESTAMP(NOW() - INTERVAL 7 DAY)",
                '%' . $key . '%'
            ));
            $values[] = intval($count);
        }
        
        wp_send_json_success(array(
            'labels' => $labels,
            'values' => $values
        ));
    }
    
    /**
     * AJAX: الحصول على الأحداث عالية الخطورة
     */
    public function ajax_get_high_threat_events() {
        check_ajax_referer('osint_hybrid_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT title, hybrid_layers, threat_score, event_timestamp 
             FROM $table 
             WHERE threat_score >= 60 
             ORDER BY threat_score DESC 
             LIMIT %d",
            $limit
        ), ARRAY_A);
        
        $events = array();
        foreach ($results as $row) {
            $layers = json_decode($row['hybrid_layers'], true);
            $layer_names = array();
            if (is_array($layers)) {
                foreach ($layers as $layer_key) {
                    if (isset($this->warfare_layers[$layer_key])) {
                        $layer_names[] = $this->warfare_layers[$layer_key]['name'];
                    }
                }
            }
            
            $events[] = array(
                'title' => $row['title'],
                'layers' => $layer_names,
                'threat_score' => $row['threat_score'],
                'date' => date('Y-m-d H:i', $row['event_timestamp'])
            );
        }
        
        wp_send_json_success($events);
    }
    
    /**
     * AJAX: الحصول على الأحداث متعددة المجالات
     */
    public function ajax_get_multi_domain_events() {
        check_ajax_referer('osint_hybrid_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'غير مصرح'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT title, hybrid_layers, multi_domain_score, event_timestamp 
             FROM $table 
             WHERE multi_domain_score >= 30 
             ORDER BY multi_domain_score DESC 
             LIMIT %d",
            $limit
        ), ARRAY_A);
        
        $events = array();
        foreach ($results as $row) {
            $layers = json_decode($row['hybrid_layers'], true);
            $layer_count = is_array($layers) ? count($layers) : 0;
            
            $events[] = array(
                'title' => $row['title'],
                'layer_count' => $layer_count,
                'multi_domain_score' => $row['multi_domain_score'],
                'date' => date('Y-m-d H:i', $row['event_timestamp'])
            );
        }
        
        wp_send_json_success($events);
    }
    
    /**
     * عرض Dashboard كـ Shortcode
     */
    public function render_dashboard_shortcode($atts) {
        ob_start();
        $this->render_admin_page();
        return ob_get_clean();
    }
    
    /**
     * تحليل نص لاكتشاف الطبقات النشطة
     */
    public function detect_active_layers($title, $content) {
        $text = $title . ' ' . $content;
        $active_layers = array();
        
        foreach ($this->warfare_layers as $key => $layer) {
            foreach ($layer['keywords'] as $keyword) {
                if (stripos($text, $keyword) !== false) {
                    if (!in_array($key, $active_layers)) {
                        $active_layers[] = $key;
                    }
                    break;
                }
            }
        }
        
        return $active_layers;
    }
    
    /**
     * حساب درجة التعددية المجال
     */
    public function calculate_multi_domain_score($active_layers) {
        $layer_count = count($active_layers);
        
        if ($layer_count >= 5) {
            return 100;
        } elseif ($layer_count >= 4) {
            return 80;
        } elseif ($layer_count >= 3) {
            return 60;
        } elseif ($layer_count >= 2) {
            return 40;
        } else {
            return 0;
        }
    }
}

// تسجيل الوحدة
function osint_register_hybrid_warfare_module() {
    if (class_exists('OSINT_Module_Loader')) {
        OSINT_Module_Loader::register_module(new OSINT_Hybrid_Warfare_Module());
    }
}
add_action('plugins_loaded', 'osint_register_hybrid_warfare_module', 20);
