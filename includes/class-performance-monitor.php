<?php
/**
 * فئة مراقبة الأداء - محسّنة
 */

namespace Beiruttime\OSINT\Performance;

if (!defined('ABSPATH')) {
    exit;
}

class Performance_Monitor {
    
    private $start_time;
    private $queries = array();
    private $markers = array();
    private $log_file = null;
    
    public function __construct() {
        add_action('shutdown', array($this, 'log_performance'));
        
        // تحديد ملف السجل إذا تم تعريفه
        if (defined('OSINT_PERF_LOG_FILE')) {
            $this->log_file = OSINT_PERF_LOG_FILE;
        }
    }
    
    /**
     * بدء المؤقت
     */
    public function start_timer() {
        $this->start_time = microtime(true);
    }
    
    /**
     * إيقاف المؤقت والحصول على الوقت المنقضي
     * 
     * @return float الوقت بالثواني
     */
    public function stop_timer() {
        if (!$this->start_time) return 0;
        return microtime(true) - $this->start_time;
    }
    
    /**
     * وضع علامة زمنية لتتبع الأقسام المختلفة
     * 
     * @param string $name اسم العلامة
     */
    public function mark($name) {
        $this->markers[$name] = array(
            'time' => microtime(true),
            'memory' => memory_get_usage(true),
            'timestamp' => current_time('mysql')
        );
    }
    
    /**
     * تسجيل استعلام SQL مع وقت التنفيذ
     * 
     * @param string $sql نص الاستعلام
     * @param float $time وقت التنفيذ بالثواني
     * @param array $backtrace تتبع الاستدعاء (اختياري)
     */
    public function log_query($sql, $time, $backtrace = null) {
        $this->queries[] = array(
            'sql' => $sql,
            'time' => $time,
            'timestamp' => current_time('mysql'),
            'backtrace' => $backtrace ?: debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        );
        
        // تسجيل الاستعلامات البطيئة جداً فوراً
        if ($time > 2.0 && $this->log_file) {
            $this->log_slow_query_immediately($sql, $time);
        }
    }
    
    /**
     * الحصول على الاستعلامات البطيئة
     * 
     * @param float $threshold العتبة بالثواني
     * @return array
     */
    public function get_slow_queries($threshold = 1.0) {
        return array_filter($this->queries, function($q) use ($threshold) {
            return $q['time'] > $threshold;
        });
    }
    
    /**
     * تسجيل الاستعلامات البطيئة جداً فوراً في ملف منفصل
     * 
     * @param string $sql نص الاستعلام
     * @param float $time وقت التنفيذ
     */
    private function log_slow_query_immediately($sql, $time) {
        $log_entry = sprintf(
            "[%s] SLOW QUERY: %.3fs\nSQL: %s\n\n",
            current_time('mysql'),
            $time,
            substr($sql, 0, 500)
        );
        
        error_log($log_entry, 3, $this->log_file . '.slow');
    }
    
    /**
     * تسجيل أداء الطلب
     */
    public function log_performance() {
        $total_time = $this->stop_timer();
        $slow_queries = $this->get_slow_queries();
        $peak_memory = memory_get_peak_usage(true);
        
        // عتبات التنبيه
        $time_threshold = defined('OSINT_PERF_TIME_THRESHOLD') ? OSINT_PERF_TIME_THRESHOLD : 2.0;
        $queries_threshold = defined('OSINT_PERF_QUERIES_THRESHOLD') ? OSINT_PERF_QUERIES_THRESHOLD : 50;
        $slow_threshold = defined('OSINT_PERF_SLOW_THRESHOLD') ? OSINT_PERF_SLOW_THRESHOLD : 5;
        
        $should_log = (
            $total_time > $time_threshold ||
            count($slow_queries) > $slow_threshold ||
            count($this->queries) > $queries_threshold
        );
        
        if ($should_log) {
            $stats = $this->get_stats();
            
            $log_message = sprintf(
                "[OSINT Performance] Total: %.3fs | Queries: %d | Slow: %d | Memory: %.2fMB",
                $stats['execution_time'],
                $stats['total_queries'],
                $stats['slow_queries'],
                $stats['memory_usage_mb']
            );
            
            if ($this->log_file) {
                error_log($log_message . "\n", 3, $this->log_file);
                
                // إضافة تفاصيل الاستعلامات البطيئة
                if (!empty($slow_queries)) {
                    foreach ($slow_queries as $query) {
                        $detail = sprintf(
                            "  - %.3fs: %s\n",
                            $query['time'],
                            substr(trim(preg_replace('/\s+/', ' ', $query['sql'])), 0, 200)
                        );
                        error_log($detail, 3, $this->log_file);
                    }
                }
            } else {
                error_log($log_message);
            }
        }
        
        // تنظيف البيانات القديمة من الذاكرة
        $this->cleanup_old_data();
    }
    
    /**
     * الحصول على إحصائيات الأداء الشاملة
     * 
     * @return array
     */
    public function get_stats() {
        global $wpdb;
        
        $execution_time = $this->stop_timer();
        $total_queries = count($this->queries);
        $slow_queries_count = count($this->get_slow_queries());
        
        // حساب متوسط وقت الاستعلام
        $avg_query_time = $total_queries > 0 
            ? array_sum(array_column($this->queries, 'time')) / $total_queries 
            : 0;
        
        // حساب الوقت الأقصى للاستعلام
        $max_query_time = !empty($this->queries) 
            ? max(array_column($this->queries, 'time')) 
            : 0;
        
        return array(
            'execution_time' => round($execution_time, 4),
            'total_queries' => $total_queries,
            'slow_queries' => $slow_queries_count,
            'memory_usage' => memory_get_peak_usage(true),
            'memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'cache_hits' => wp_cache_get('osint_cache_hits', 'osint') ?: 0,
            'cache_misses' => wp_cache_get('osint_cache_misses', 'osint') ?: 0,
            'avg_query_time' => round($avg_query_time, 4),
            'max_query_time' => round($max_query_time, 4),
            'markers' => $this->get_marker_times(),
            'timestamp' => current_time('mysql')
        );
    }
    
    /**
     * حساب أوقات العلامات الزمنية
     * 
     * @return array
     */
    private function get_marker_times() {
        if (empty($this->markers) || !$this->start_time) {
            return array();
        }
        
        $result = array();
        $last_time = $this->start_time;
        
        foreach ($this->markers as $name => $data) {
            $result[$name] = array(
                'elapsed' => round($data['time'] - $this->start_time, 4),
                'delta' => round($data['time'] - $last_time, 4),
                'memory' => $data['memory']
            );
            $last_time = $data['time'];
        }
        
        return $result;
    }
    
    /**
     * تحليل نمط الاستعلامات للعثور على المشاكل المحتملة
     * 
     * @return array
     */
    public function analyze_query_patterns() {
        $patterns = array(
            'duplicate_queries' => array(),
            'n_plus_one' => false,
            'missing_indexes' => array()
        );
        
        // البحث عن الاستعلامات المكررة
        $query_counts = array_count_values(
            array_map(function($q) {
                // تطبيع الاستعلام للمقارنة
                return preg_replace('/\d+/', 'N', trim(preg_replace('/\s+/', ' ', $q['sql'])));
            }, $this->queries)
        );
        
        foreach ($query_counts as $query => $count) {
            if ($count > 3) {
                $patterns['duplicate_queries'][] = array(
                    'query' => $query,
                    'count' => $count
                );
            }
        }
        
        // كشف نمط N+1
        $select_patterns = array_filter($this->queries, function($q) {
            return stripos($q['sql'], 'SELECT') === 0;
        });
        
        if (count($select_patterns) > 10) {
            $similar_count = 0;
            $base_pattern = preg_replace('/WHERE.*$/', '', $select_patterns[0]['sql']);
            
            foreach ($select_patterns as $q) {
                $pattern = preg_replace('/WHERE.*$/', '', $q['sql']);
                if ($pattern === $base_pattern) {
                    $similar_count++;
                }
            }
            
            if ($similar_count > 5) {
                $patterns['n_plus_one'] = true;
            }
        }
        
        return $patterns;
    }
    
    /**
     * تنظيف البيانات القديمة لتوفير الذاكرة
     */
    private function cleanup_old_data() {
        // الاحتفاظ فقط بآخر 100 استعلام في الذاكرة
        if (count($this->queries) > 100) {
            $this->queries = array_slice($this->queries, -100);
        }
        
        // مسح العلامات بعد التسجيل
        $this->markers = array();
    }
    
    /**
     * تصدير تقرير الأداء بتنسيق JSON
     * 
     * @return string
     */
    public function export_report() {
        return json_encode(array(
            'stats' => $this->get_stats(),
            'slow_queries' => $this->get_slow_queries(0.5),
            'patterns' => $this->analyze_query_patterns(),
            'generated_at' => current_time('mysql')
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
