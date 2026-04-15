<?php
/**
 * Data Handler - Modular System
 * Handles data processing, validation, and storage for OSINT modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class SOD_Data_Handler {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Validate and sanitize input data
     */
    public function validate_data($data, $type = 'text') {
        switch ($type) {
            case 'email':
                return sanitize_email($data);
            case 'url':
                return esc_url_raw($data);
            case 'int':
                return intval($data);
            case 'text':
            default:
                return sanitize_text_field($data);
        }
    }
    
    /**
     * Process batch data with rate limiting
     */
    public function process_batch($items, $callback) {
        global $wpdb;
        
        $results = [];
        $batch_size = 100;
        $total = count($items);
        
        for ($i = 0; $i < $total; $i += $batch_size) {
            $batch = array_slice($items, $i, $batch_size);
            
            foreach ($batch as $item) {
                if (SOD_Rate_Limiter::is_allowed('data_process', 100)) {
                    $results[] = call_user_func($callback, $item);
                } else {
                    SOD_Security_Logger::log('rate_limit_exceeded', [
                        'operation' => 'data_process',
                        'item' => $item
                    ]);
                    break;
                }
            }
            
            // Small delay to prevent overload
            usleep(10000); // 10ms
        }
        
        return $results;
    }
    
    /**
     * Store processed data securely
     */
    public function store_data($table, $data) {
        global $wpdb;
        
        $sanitized_data = [];
        foreach ($data as $key => $value) {
            $sanitized_data[$key] = $this->validate_data($value);
        }
        
        $inserted = $wpdb->insert($table, $sanitized_data);
        
        if ($inserted === false) {
            SOD_Security_Logger::log('db_insert_error', [
                'table' => $table,
                'error' => $wpdb->last_error
            ]);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Retrieve data with caching
     */
    public function get_data($table, $where = [], $cache_key = '') {
        global $wpdb;
        
        if (!empty($cache_key)) {
            $cached = wp_cache_get($cache_key, 'osint_pro');
            if ($cached !== false) {
                return $cached;
            }
        }
        
        $sql = "SELECT * FROM {$table}";
        $conditions = [];
        $values = [];
        
        foreach ($where as $key => $value) {
            $conditions[] = "{$key} = %s";
            $values[] = $value;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $prepared = $wpdb->prepare($sql, $values);
        $results = $wpdb->get_results($prepared, ARRAY_A);
        
        if (!empty($cache_key) && !empty($results)) {
            wp_cache_set($cache_key, $results, 'osint_pro', 300); // 5 minutes
        }
        
        return $results;
    }
    
    /**
     * Export data in various formats
     */
    public function export_data($data, $format = 'json') {
        switch ($format) {
            case 'csv':
                return $this->export_csv($data);
            case 'xml':
                return $this->export_xml($data);
            case 'json':
            default:
                return json_encode($data, JSON_PRETTY_PRINT);
        }
    }
    
    private function export_csv($data) {
        if (empty($data)) return '';
        
        $headers = array_keys(reset($data));
        $output = implode(',', $headers) . "\n";
        
        foreach ($data as $row) {
            $output .= implode(',', array_map(function($val) {
                return '"' . str_replace('"', '""', $val) . '"';
            }, $row)) . "\n";
        }
        
        return $output;
    }
    
    private function export_xml($data) {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<root>\n";
        
        foreach ($data as $item) {
            $xml .= "  <item>\n";
            foreach ($item as $key => $value) {
                $xml .= "    <{$key}>" . htmlspecialchars($value) . "</{$key}>\n";
            }
            $xml .= "  </item>\n";
        }
        
        $xml .= "</root>";
        return $xml;
    }
}
