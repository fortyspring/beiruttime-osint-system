<?php
/**
 * محرك OSINT المعياري
 * 
 * @package Beiruttime\OSINT
 */

namespace Beiruttime\OSINT;

class OSINT_Engine {
    /**
     * مثيل الفئة الوحيد
     */
    private static $instance = null;
    
    /**
     * كائن قاعدة البيانات
     */
    protected $wpdb;
    
    /**
     * جدول الأحداث
     */
    protected $table_name;
    
    /**
     * إعدادات المحرك
     */
    protected $settings = array();
    
    /**
     * الحصول على المثيل الوحيد
     */
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * الإنشاء
     */
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'so_news_events';
        $this->load_settings();
    }
    
    /**
     * تحميل الإعدادات
     */
    protected function load_settings() {
        $this->settings = get_option('beiruttime_osint_engine_settings', array(
            'auto_classify' => true,
            'auto_verify' => true,
            'auto_calculate_scores' => true,
            'enable_hybrid_warfare' => true,
            'cache_enabled' => true,
            'cache_time' => 300,
        ));
    }
    
    /**
     * إضافة حدث جديد
     */
    public function add_event($data) {
        $defaults = array(
            'title' => '',
            'war_data' => '',
            'actor_v2' => '',
            'region' => '',
            'score' => 0,
            'event_timestamp' => time(),
            'publish_timestamp' => time(),
        );
        
        $event_data = wp_parse_args($data, $defaults);
        
        // تنظيف البيانات
        $event_data['war_data_clean'] = $this->clean_content($event_data['war_data']);
        
        // التصنيف التلقائي
        if ($this->settings['auto_classify']) {
            $event_data = $this->classify_event($event_data);
        }
        
        // التحقق التلقائي
        if ($this->settings['auto_verify']) {
            $event_data = $this->verify_event($event_data);
        }
        
        // حساب النتائج
        if ($this->settings['auto_calculate_scores']) {
            $event_data = $this->calculate_scores($event_data);
        }
        
        // إدراج في قاعدة البيانات
        $inserted = $this->wpdb->insert($this->table_name, $event_data);
        
        if ($inserted) {
            $event_id = $this->wpdb->insert_id;
            do_action('beiruttime_osint_event_added', $event_id, $event_data);
            return $event_id;
        }
        
        return false;
    }
    
    /**
     * تنظيف المحتوى
     */
    protected function clean_content($content) {
        // إزالة الوسوم HTML غير المرغوبة
        $content = strip_tags($content, '<p><br><strong><em>');
        
        // تطبيع المسافات
        $content = preg_replace('/\s+/', ' ', $content);
        
        return trim($content);
    }
    
    /**
     * تصنيف الحدث
     */
    protected function classify_event($data) {
        $title = $data['title'];
        $content = $data['war_data'];
        
        // تحديد نوع OSINT
        $data['osint_type'] = $this->detect_osint_type($title, $content);
        
        // تحديد طبقات الحرب المركبة
        if ($this->settings['enable_hybrid_warfare']) {
            $data['hybrid_layers'] = $this->detect_hybrid_layers($title, $content);
            $data['multi_domain_score'] = $this->calculate_multi_domain_score($data['hybrid_layers']);
        }
        
        // استخراج الفاعلين
        $actors = $this->extract_actors($title, $content);
        $data['primary_actor'] = $actors['primary'];
        $data['secondary_actor'] = $actors['secondary'];
        
        // الاستخراج الجغرافي
        $geo = $this->extract_geo($title, $content);
        $data['geo_country'] = $geo['country'];
        $data['geo_region'] = $geo['region'];
        $data['geo_city'] = $geo['city'];
        
        return $data;
    }
    
    /**
     * كشف نوع OSINT
     */
    protected function detect_osint_type($title, $content) {
        $text = strtolower($title . ' ' . $content);
        
        $types = array(
            'military' => array('غارة', 'قصف', 'استهداف', 'هجوم', 'اشتباك', 'صاروخ', 'طيران'),
            'security' => array('اعتقال', 'مداهمة', 'تفكيك', 'خلية', 'أمني'),
            'cyber' => array('اختراق', 'سيبراني', 'رقمي', 'تسريب', 'قرصنة'),
            'political' => array('تصريح', 'عقوبات', 'مفاوضات', 'دبلوماسي', 'وزير'),
            'economic' => array('اقتصادي', 'نفط', 'غاز', 'سوق', 'عملة', 'تجارة'),
            'social' => array('احتجاج', 'تظاهرة', 'اجتماعي', 'تحريض'),
            'energy' => array('كهرباء', 'طاقة', 'مصفاة', 'محطة'),
        );
        
        foreach ($types as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return $type;
                }
            }
        }
        
        return 'general';
    }
    
    /**
     * كشف طبقات الحرب المركبة
     */
    protected function detect_hybrid_layers($title, $content) {
        $layers = array();
        $text = strtolower($title . ' ' . $content);
        
        $layer_keywords = array(
            'military' => array('غارة', 'قصف', 'استهداف', 'ضربة'),
            'security' => array('اعتقال', 'مداهمة', 'أمني'),
            'cyber' => array('اختراق', 'سيبراني', 'رقمي'),
            'political' => array('تصريح', 'عقوبات', 'دبلوماسي'),
            'economic' => array('اقتصادي', 'نفط', 'غاز', 'عقوبات مالية'),
            'media' => array('إعلامي', 'دعاية', 'نشر'),
            'cognitive' => array('نفسي', 'رعب', 'ذعر', 'معنويات'),
            'energy' => array('كهرباء', 'طاقة', 'نفط', 'غاز'),
            'strategic' => array('مضيق', 'ممر', 'قاعدة', 'استراتيجي'),
        );
        
        foreach ($layer_keywords as $layer => $keywords) {
            $count = 0;
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $count++;
                }
            }
            
            if ($count > 0) {
                $layers[$layer] = min($count * 20, 100);
            }
        }
        
        return !empty($layers) ? json_encode($layers, JSON_UNESCAPED_UNICODE) : null;
    }
    
    /**
     * حساب درجة التعددية المجال
     */
    protected function calculate_multi_domain_score($hybrid_layers_json) {
        if (empty($hybrid_layers_json)) {
            return 0;
        }
        
        $layers = json_decode($hybrid_layers_json, true);
        if (!is_array($layers)) {
            return 0;
        }
        
        $active_count = count($layers);
        $avg_intensity = array_sum($layers) / $active_count;
        
        return min(round(($active_count * 10) + ($avg_intensity / 5)), 100);
    }
    
    /**
     * استخراج الفاعلين
     */
    protected function extract_actors($title, $content) {
        // تبسيط: يمكن تطوير هذا باستخدام NLP متقدم
        $actors = array(
            'primary' => '',
            'secondary' => '',
        );
        
        $actor_patterns = array(
            'جيش العدو الإسرائيلي' => 'israeli_military',
            'المقاومة الإسلامية' => 'resistance',
            'الجيش السوري' => 'syrian_army',
            'التحالف' => 'coalition',
            'الحوثيين' => 'houthis',
        );
        
        foreach ($actor_patterns as $actor_name => $actor_key) {
            if (strpos($title, $actor_name) !== false || strpos($content, $actor_name) !== false) {
                if (empty($actors['primary'])) {
                    $actors['primary'] = $actor_name;
                } else {
                    $actors['secondary'] = $actor_name;
                    break;
                }
            }
        }
        
        return $actors;
    }
    
    /**
     * الاستخراج الجغرافي
     */
    protected function extract_geo($title, $content) {
        $geo = array(
            'country' => '',
            'region' => '',
            'city' => '',
        );
        
        $countries = array('سوريا', 'لبنان', 'فلسطين', 'العراق', 'اليمن', 'إيران');
        $regions = array('دمشق', 'حلب', 'بيروت', 'غزة', 'بغداد', 'صنعاء');
        
        foreach ($countries as $country) {
            if (strpos($title, $country) !== false || strpos($content, $country) !== false) {
                $geo['country'] = $country;
                break;
            }
        }
        
        foreach ($regions as $region) {
            if (strpos($title, $region) !== false || strpos($content, $region) !== false) {
                $geo['region'] = $region;
                break;
            }
        }
        
        return $geo;
    }
    
    /**
     * التحقق من الحدث
     */
    protected function verify_event($data) {
        $verification = array(
            'status' => 'unverified',
            'sources_count' => 0,
            'has_visual' => false,
            'confidence' => 0,
        );
        
        $content = strtolower($data['war_data']);
        
        // كشف المصادر الموثوقة
        $trusted_sources = array('رويترز', 'associated press', 'france24', 'bbc', 'aljazeera');
        foreach ($trusted_sources as $source) {
            if (strpos($content, $source) !== false) {
                $verification['sources_count']++;
            }
        }
        
        // كشف الأدلة البصرية
        if (preg_match('/(صورة|فيديو|تصوير|footage|video|photo)/i', $content)) {
            $verification['has_visual'] = true;
        }
        
        // تحديد حالة التحقق
        if ($verification['sources_count'] >= 2) {
            $verification['status'] = 'verified';
            $verification['confidence'] = min(80 + ($verification['sources_count'] * 5), 100);
        } elseif ($verification['sources_count'] >= 1) {
            $verification['status'] = 'likely';
            $verification['confidence'] = 50 + ($verification['sources_count'] * 10);
        } else {
            $verification['confidence'] = 20;
        }
        
        $data['verification_status'] = $verification['status'];
        $data['verified_sources_count'] = $verification['sources_count'];
        $data['has_visual_evidence'] = $verification['has_visual'] ? 1 : 0;
        $data['confidence_score'] = $verification['confidence'];
        
        return $data;
    }
    
    /**
     * حساب النتائج
     */
    protected function calculate_scores($data) {
        $content = strtolower($data['war_data'] . ' ' . $data['title']);
        
        // حساب Sentiment Score
        $sentiment_keywords = array(
            'negative' => array('قتل', 'دمار', 'تدمير', 'ضحية', 'إصابة', 'خسائر', 'هجوم', 'عدوان'),
            'positive' => array('انتصار', 'نجاح', 'إنجاز', 'سلام', 'هدنة', 'اتفاق'),
        );
        
        $negative_count = 0;
        $positive_count = 0;
        
        foreach ($sentiment_keywords['negative'] as $word) {
            if (strpos($content, $word) !== false) {
                $negative_count++;
            }
        }
        
        foreach ($sentiment_keywords['positive'] as $word) {
            if (strpos($content, $word) !== false) {
                $positive_count++;
            }
        }
        
        $data['sentiment_score'] = max(-100, min(100, ($positive_count - $negative_count) * 10));
        
        // حساب Threat Score
        $threat_indicators = array('تهديد', 'وعيد', 'تصعيد', 'حرب', 'معركة', 'عملية');
        $threat_count = 0;
        foreach ($threat_indicators as $indicator) {
            if (strpos($content, $indicator) !== false) {
                $threat_count++;
            }
        }
        
        $data['threat_score'] = min(100, $threat_count * 15);
        
        // حساب Escalation Score
        $escalation_words = array('تصعيد', 'توتر', 'استنفار', 'جاهزية', 'حشود');
        $escalation_count = 0;
        foreach ($escalation_words as $word) {
            if (strpos($content, $word) !== false) {
                $escalation_count++;
            }
        }
        
        $data['escalation_score'] = min(100, $escalation_count * 20);
        
        // تحديد مستوى الخطر
        $total_threat = ($data['threat_score'] + $data['escalation_score']) / 2;
        if ($total_threat >= 75) {
            $data['risk_level'] = 'critical';
        } elseif ($total_threat >= 50) {
            $data['risk_level'] = 'high';
        } elseif ($total_threat >= 25) {
            $data['risk_level'] = 'medium';
        } else {
            $data['risk_level'] = 'low';
        }
        
        return $data;
    }
    
    /**
     * الحصول على حدث
     */
    public function get_event($id) {
        $cached = wp_cache_get($id, 'beiruttime_osint_event');
        if ($cached !== false) {
            return $cached;
        }
        
        $event = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id),
            ARRAY_A
        );
        
        if ($event) {
            wp_cache_set($id, $event, 'beiruttime_osint_event', 300);
        }
        
        return $event;
    }
    
    /**
     * البحث عن أحداث
     */
    public function search_events($args = array()) {
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'osint_type' => '',
            'risk_level' => '',
            'verification_status' => '',
            'date_from' => '',
            'date_to' => '',
        );
        
        $query_args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $values = array();
        
        if (!empty($query_args['osint_type'])) {
            $where[] = 'osint_type = %s';
            $values[] = $query_args['osint_type'];
        }
        
        if (!empty($query_args['risk_level'])) {
            $where[] = 'risk_level = %s';
            $values[] = $query_args['risk_level'];
        }
        
        if (!empty($query_args['verification_status'])) {
            $where[] = 'verification_status = %s';
            $values[] = $query_args['verification_status'];
        }
        
        if (!empty($query_args['date_from'])) {
            $where[] = 'event_timestamp >= %d';
            $values[] = strtotime($query_args['date_from']);
        }
        
        if (!empty($query_args['date_to'])) {
            $where[] = 'event_timestamp <= %d';
            $values[] = strtotime($query_args['date_to']);
        }
        
        $where_clause = implode(' AND ', $where);
        $values[] = $query_args['limit'];
        $values[] = $query_args['offset'];
        
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE $where_clause ORDER BY event_timestamp DESC LIMIT %d OFFSET %d",
            $values
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * تحديث حدث
     */
    public function update_event($id, $data) {
        $updated = $this->wpdb->update(
            $this->table_name,
            $data,
            array('id' => $id)
        );
        
        if ($updated !== false) {
            wp_cache_delete($id, 'beiruttime_osint_event');
            do_action('beiruttime_osint_event_updated', $id, $data);
            return true;
        }
        
        return false;
    }
    
    /**
     * حذف حدث
     */
    public function delete_event($id) {
        $deleted = $this->wpdb->delete($this->table_name, array('id' => $id));
        
        if ($deleted) {
            wp_cache_delete($id, 'beiruttime_osint_event');
            do_action('beiruttime_osint_event_deleted', $id);
            return true;
        }
        
        return false;
    }
    
    /**
     * الحصول على إحصائيات سريعة
     */
    public function get_quick_stats() {
        $cache_key = 'quick_stats';
        $cached = wp_cache_get($cache_key, 'beiruttime_osint');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $stats = array(
            'total_events' => (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}"),
            'last_24h' => (int) $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_name} WHERE event_timestamp >= %d",
                    time() - DAY_IN_SECONDS
                )
            ),
            'high_threat' => (int) $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE threat_score >= 60"
            ),
            'verified' => (int) $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE verification_status = 'verified'"
            ),
            'alerts_active' => (int) $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE alert_flag = 1 AND alert_status = 'pending'"
            ),
        );
        
        wp_cache_set($cache_key, $stats, 'beiruttime_osint', 60);
        
        return $stats;
    }
}
