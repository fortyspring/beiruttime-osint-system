<?php
/**
 * فئة مراقبة الأداء
 */

namespace Beiruttime\OSINT\Performance;

if (!defined('ABSPATH')) {
    exit;
}

class Performance_Monitor {
    
    private $start_time;
    private $queries = array();
    
    public function __construct() {
        add_action('shutdown', array($this, 'log_performance'));
    }
    
    public function start_timer() {
        $this->start_time = microtime(true);
    }
    
    public function stop_timer() {
        if (!$this->start_time) return 0;
        return microtime(true) - $this->start_time;
    }
    
    public function log_query($sql, $time) {
        $this->queries[] = array(
            'sql' => $sql,
            'time' => $time,
            'timestamp' => current_time('mysql')
        );
    }
    
    public function get_slow_queries($threshold = 1.0) {
        return array_filter($this->queries, function($q) use ($threshold) {
            return $q['time'] > $threshold;
        });
    }
    
    public function log_performance() {
        $total_time = $this->stop_timer();
        $slow_queries = $this->get_slow_queries();
        
        if ($total_time > 2.0 || count($slow_queries) > 5) {
            error_log(sprintf(
                '[OSINT Performance] Total: %.3fs, Slow Queries: %d',
                $total_time,
                count($slow_queries)
            ));
        }
    }
    
    public function get_stats() {
        global $wpdb;
        
        return array(
            'execution_time' => $this->stop_timer(),
            'total_queries' => count($this->queries),
            'slow_queries' => count($this->get_slow_queries()),
            'memory_usage' => memory_get_peak_usage(true),
            'cache_hits' => wp_cache_get('osint_cache_hits', 'osint') ?: 0
        );
    }
}
