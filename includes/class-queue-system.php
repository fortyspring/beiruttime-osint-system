<?php
/**
 * فئة نظام الطابور (Queue System)
 */

namespace Beiruttime\OSINT\Queue;

if (!defined('ABSPATH')) {
    exit;
}

class Queue_System {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'osint_queue';
        
        add_action('wp_loaded', array($this, 'process_queue'));
    }
    
    public function add_job($action, $data = array(), $priority = 10) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'action' => $action,
                'data' => json_encode($data),
                'priority' => $priority,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'scheduled_at' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    public function process_queue() {
        global $wpdb;
        
        $jobs = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} 
             WHERE status = 'pending' 
             AND scheduled_at <= NOW() 
             ORDER BY priority ASC, created_at ASC 
             LIMIT 5"
        );
        
        foreach ($jobs as $job) {
            $this->execute_job($job);
        }
    }
    
    private function execute_job($job) {
        global $wpdb;
        
        // تحديث الحالة إلى processing
        $wpdb->update(
            $this->table_name,
            array('status' => 'processing'),
            array('id' => $job->id),
            array('%s'),
            array('%d')
        );
        
        try {
            $data = json_decode($job->data, true);
            
            switch ($job->action) {
                case 'reindex_event':
                    $this->reindex_event($data);
                    break;
                case 'analyze_hybrid_layers':
                    $this->analyze_hybrid_layers($data);
                    break;
                case 'send_notification':
                    $this->send_notification($data);
                    break;
                default:
                    do_action('osint_queue_action_' . $job->action, $data);
            }
            
            // نجاح
            $wpdb->update(
                $this->table_name,
                array(
                    'status' => 'completed',
                    'completed_at' => current_time('mysql')
                ),
                array('id' => $job->id),
                array('%s', '%s'),
                array('%d')
            );
            
        } catch (\Exception $e) {
            // فشل
            $wpdb->update(
                $this->table_name,
                array(
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'failed_at' => current_time('mysql')
                ),
                array('id' => $job->id),
                array('%s', '%s', '%s'),
                array('%d')
            );
        }
    }
    
    private function reindex_event($data) {
        // إعادة فهرسة حدث
    }
    
    private function analyze_hybrid_layers($data) {
        // تحليل طبقات الحرب المركبة
    }
    
    private function send_notification($data) {
        // إرسال إشعار
    }
    
    public function get_queue_stats() {
        global $wpdb;
        
        return $wpdb->get_row(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
             FROM {$this->table_name}"
        , ARRAY_A);
    }
}
