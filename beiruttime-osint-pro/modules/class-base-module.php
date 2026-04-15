<?php
/**
 * الفئة الأساسية للوحدات
 * 
 * @package Beiruttime\OSINT
 */

namespace Beiruttime\OSINT;

abstract class Base_Module implements Module_Interface {
    /**
     * معرف الوحدة
     */
    protected $id;
    
    /**
     * اسم الوحدة
     */
    protected $name;
    
    /**
     * وصف الوحدة
     */
    protected $description;
    
    /**
     * حالة التهيئة
     */
    protected $initialized = false;
    
    /**
     * إعدادات الوحدة
     */
    protected $settings = array();
    
    /**
     * كائن قاعدة البيانات
     */
    protected $wpdb;
    
    /**
     * مجلد الوحدة
     */
    protected $module_dir;
    
    /**
     * رابط الوحدة
     */
    protected $module_url;
    
    /**
     * سجل الأحداث
     */
    protected $logger;
    
    /**
     * التخزين المؤقت
     */
    protected $cache_enabled = true;
    protected $cache_time = 300; // 5 دقائق
    
    /**
     * الإنشاء
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->module_dir = BEIRUTTIME_OSINT_MODULES_DIR . $this->get_id() . '/';
        $this->module_url = BEIRUTTIME_OSINT_PLUGIN_URL . 'modules/' . $this->get_id() . '/';
        $this->init_settings();
    }
    
    /**
     * تهيئة الإعدادات الافتراضية
     */
    protected function init_settings() {
        $this->settings = apply_filters(
            "beiruttime_osint_{$this->id}_settings",
            $this->get_default_settings()
        );
    }
    
    /**
     * الإعدادات الافتراضية
     */
    protected function get_default_settings() {
        return array();
    }
    
    /**
     * الحصول على معرف الوحدة
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * الحصول على اسم الوحدة
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * الحصول على وصف الوحدة
     */
    public function get_description() {
        return $this->description;
    }
    
    /**
     * تهيئة الوحدة
     */
    public function init() {
        if ($this->initialized) {
            return;
        }
        
        $this->register_hooks();
        $this->register_ajax_handlers();
        $this->initialized = true;
        
        do_action("beiruttime_osint_module_initialized_{$this->id}", $this);
    }
    
    /**
     * تسجيل الهوكات
     */
    protected function register_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * تحميل الموارد
     */
    public function enqueue_assets() {
        // يمكن تجاوزه في الفئات الفرعية
    }
    
    /**
     * تسجيل AJAX handlers
     */
    public function register_ajax_handlers() {
        // يمكن تجاوزه في الفئات الفرعية
    }
    
    /**
     * الحصول على بيانات الوحدة
     */
    public function get_data($args = array()) {
        return array();
    }
    
    /**
     * معالجة طلب AJAX
     */
    public function handle_ajax($action) {
        check_ajax_referer('beiruttime_osint_nonce', 'nonce');
        
        if (!$this->check_permissions()) {
            wp_send_json_error(array('message' => __('غير مصرح لك', 'beiruttime-osint-pro')));
        }
        
        $method = 'ajax_' . $action;
        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            wp_send_json_error(array('message' => __('إجراء غير معروف', 'beiruttime-osint-pro')));
        }
    }
    
    /**
     * التحقق من الصلاحيات
     */
    public function check_permissions() {
        return current_user_can('manage_options');
    }
    
    /**
     * تسجيل حدث في السجل
     */
    protected function log($message, $level = 'info') {
        $log_file = BEIRUTTIME_OSINT_LOGS_DIR . 'modules.log';
        $timestamp = current_time('mysql');
        $log_entry = "[$timestamp] [$level] [{$this->id}] $message\n";
        
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
    
    /**
     * الحصول على بيانات من التخزين المؤقت
     */
    protected function get_cache($key, $group = 'beiruttime_osint') {
        if (!$this->cache_enabled) {
            return false;
        }
        
        return wp_cache_get($key, $group);
    }
    
    /**
     * حفظ بيانات في التخزين المؤقت
     */
    protected function set_cache($key, $data, $group = 'beiruttime_osint', $expire = null) {
        if (!$this->cache_enabled) {
            return false;
        }
        
        $expire = $expire ?: $this->cache_time;
        return wp_cache_set($key, $data, $group, $expire);
    }
    
    /**
     * حذف بيانات من التخزين المؤقت
     */
    protected function delete_cache($key, $group = 'beiruttime_osint') {
        return wp_cache_delete($key, $group);
    }
    
    /**
     * تنظيف التخزين المؤقت
     */
    protected function flush_cache() {
        wp_cache_flush_group('beiruttime_osint');
    }
}
