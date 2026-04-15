<?php
/**
 * فئة نظام الطابور (Queue System) - محسّن للأداء
 */

namespace Beiruttime\OSINT\Queue;

if (!defined('ABSPATH')) {
    exit;
}

class Queue_System {
    
    private $table_name;
    private $processing = false;
    private $max_jobs_per_run = 10;
    private $batch_size = 5;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'osint_queue';
        
        // استخدام WP Cron مع فحص أفضل
        add_action('wp_loaded', array($this, 'process_queue'));
        add_action('osint_process_queue', array($this, 'process_queue_cron'));
        
        // جدولة المعالجة في الخلفية إذا لم تكن موجودة
        if (!wp_next_scheduled('osint_process_queue')) {
            wp_schedule_event(time(), 'every_minute', 'osint_process_queue');
        }
    }
    
    /**
     * إضافة وظيفة إلى الطابور مع دعم الأولويات والمعالجة المؤجلة
     * 
     * @param string $action نوع الإجراء
     * @param array $data بيانات الوظيفة
     * @param int $priority الأولوية (أقل رقم = أولوية أعلى)
     * @param int $delay تأخير بالثواني
     * @return int|false معرف الوظيفة أو false عند الفشل
     */
    public function add_job($action, $data = array(), $priority = 10, $delay = 0) {
        global $wpdb;
        
        $scheduled_time = current_time('mysql', true);
        if ($delay > 0) {
            $scheduled_time = date('Y-m-d H:i:s', strtotime("+{$delay} seconds", strtotime($scheduled_time)));
        }
        
        $wpdb->insert(
            $this->table_name,
            array(
                'action' => $action,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'priority' => intval($priority),
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'scheduled_at' => $scheduled_time,
                'attempts' => 0,
                'lock_token' => null
            ),
            array('%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * معالجة الطابور أثناء تحميل الصفحة (للطلبات الفردية)
     */
    public function process_queue() {
        if ($this->processing) {
            return;
        }
        
        // منع التكرار باستخدام transient
        if (get_transient('osint_queue_processing')) {
            return;
        }
        
        set_transient('osint_queue_processing', true, 30);
        $this->processing = true;
        
        $this->execute_batch($this->batch_size);
        
        $this->processing = false;
        delete_transient('osint_queue_processing');
    }
    
    /**
     * معالجة الطابور عبر WP Cron (للخلفية)
     */
    public function process_queue_cron() {
        if ($this->processing) {
            return;
        }
        
        $this->processing = true;
        $this->execute_batch($this->max_jobs_per_run);
        $this->processing = false;
    }
    
    /**
     * تنفيذ دفعة من الوظائف
     * 
     * @param int $limit عدد الوظائف القصوى للمعالجة
     */
    private function execute_batch($limit = 5) {
        global $wpdb;
        
        // الحصول على وظائف جاهزة للمعالجة مع قفل بسيط
        $jobs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                 WHERE status = 'pending' 
                 AND scheduled_at <= %s 
                 AND (lock_token IS NULL OR lock_token = '')
                 ORDER BY priority ASC, created_at ASC 
                 LIMIT %d",
                current_time('mysql'),
                $limit
            )
        );
        
        if (empty($jobs)) {
            return;
        }
        
        foreach ($jobs as $job) {
            // تعيين رمز قفل لمنع المعالجة المكررة
            $lock_token = uniqid('lock_', true);
            $wpdb->update(
                $this->table_name,
                array(
                    'status' => 'processing',
                    'lock_token' => $lock_token,
                    'attempts' => $job->attempts + 1
                ),
                array('id' => $job->id),
                array('%s', '%s', '%d'),
                array('%d')
            );
            
            $this->execute_job($job, $lock_token);
        }
        
        // تنظيف الوظائف القديمة المكتملة
        $this->cleanup_old_jobs();
    }
    
    /**
     * تنفيذ وظيفة واحدة
     * 
     * @param object $job بيانات الوظيفة
     * @param string $lock_token رمز القفل
     */
    private function execute_job($job, $lock_token) {
        global $wpdb;
        
        try {
            $data = json_decode($job->data, true);
            
            // التحقق من صحة البيانات
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid job data: ' . json_last_error_msg());
            }
            
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
                case 'bulk_process':
                    $this->bulk_process($data);
                    break;
                default:
                    do_action('osint_queue_action_' . $job->action, $data);
            }
            
            // نجاح
            $wpdb->update(
                $this->table_name,
                array(
                    'status' => 'completed',
                    'completed_at' => current_time('mysql'),
                    'lock_token' => null
                ),
                array('id' => $job->id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            
        } catch (\Exception $e) {
            $error_msg = $e->getMessage();
            $max_attempts = defined('OSINT_QUEUE_MAX_ATTEMPTS') ? OSINT_QUEUE_MAX_ATTEMPTS : 3;
            
            // إعادة المحاولة إذا كان هناك محاولات متبقية
            if ($job->attempts < $max_attempts) {
                $wpdb->update(
                    $this->table_name,
                    array(
                        'status' => 'pending',
                        'lock_token' => null,
                        'error_message' => $error_msg,
                        'scheduled_at' => date('Y-m-d H:i:s', strtotime('+5 minutes'))
                    ),
                    array('id' => $job->id),
                    array('%s', '%s', '%s'),
                    array('%d')
                );
            } else {
                // فشل نهائي
                $wpdb->update(
                    $this->table_name,
                    array(
                        'status' => 'failed',
                        'error_message' => $error_msg,
                        'failed_at' => current_time('mysql'),
                        'lock_token' => null
                    ),
                    array('id' => $job->id),
                    array('%s', '%s', '%s'),
                    array('%d')
                );
            }
        }
    }
    
    /**
     * إعادة فهرسة حدث
     */
    private function reindex_event($data) {
        if (empty($data['event_id'])) {
            return;
        }
        
        do_action('osint_reindex_event', $data['event_id']);
    }
    
    /**
     * تحليل طبقات الحرب المركبة
     */
    private function analyze_hybrid_layers($data) {
        if (empty($data['event_id'])) {
            return;
        }
        
        do_action('osint_analyze_hybrid_layers', $data['event_id']);
    }
    
    /**
     * إرسال إشعار
     */
    private function send_notification($data) {
        if (empty($data['user_id']) || empty($data['message'])) {
            return;
        }
        
        do_action('osint_send_notification', $data);
    }
    
    /**
     * معالجة جماعية للوظائف
     */
    private function bulk_process($data) {
        if (empty($data['action']) || empty($data['items'])) {
            return;
        }
        
        foreach ($data['items'] as $item) {
            $this->add_job($data['action'], $item, 15);
        }
    }
    
    /**
     * تنظيف الوظائف القديمة المكتملة
     */
    private function cleanup_old_jobs() {
        global $wpdb;
        
        $retention_days = defined('OSINT_QUEUE_RETENTION_DAYS') ? OSINT_QUEUE_RETENTION_DAYS : 7;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} 
                 WHERE status IN ('completed', 'failed') 
                 AND completed_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $retention_days
            )
        );
    }
    
    /**
     * الحصول على إحصائيات الطابور
     * 
     * @return array
     */
    public function get_queue_stats() {
        global $wpdb;
        
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                AVG(CASE WHEN status = 'completed' THEN TIMESTAMPDIFF(SECOND, created_at, completed_at) END) as avg_completion_time
             FROM {$this->table_name}",
            ARRAY_A
        );
        
        // إضافة معلومات عن الوظائف المتأخرة
        $stats['overdue'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} 
                 WHERE status = 'pending' 
                 AND scheduled_at < %s",
                current_time('mysql')
            )
        );
        
        return $stats ?: array(
            'total' => 0,
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
            'overdue' => 0,
            'avg_completion_time' => 0
        );
    }
    
    /**
     * إلغاء وظيفة معينة
     * 
     * @param int $job_id معرف الوظيفة
     * @return bool
     */
    public function cancel_job($job_id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            array('status' => 'cancelled'),
            array('id' => $job_id),
            array('%s'),
            array('%d')
        ) !== false;
    }
    
    /**
     * إعادة جدولة وظيفة فشلت
     * 
     * @param int $job_id معرف الوظيفة
     * @param int $delay تأخير بالثواني
     * @return bool
     */
    public function retry_job($job_id, $delay = 0) {
        global $wpdb;
        
        $scheduled_at = current_time('mysql');
        if ($delay > 0) {
            $scheduled_at = date('Y-m-d H:i:s', strtotime("+{$delay} seconds", strtotime($scheduled_at)));
        }
        
        return $wpdb->update(
            $this->table_name,
            array(
                'status' => 'pending',
                'lock_token' => null,
                'scheduled_at' => $scheduled_at
            ),
            array('id' => $job_id),
            array('%s', '%s', '%s'),
            array('%d')
        ) !== false;
    }
}
