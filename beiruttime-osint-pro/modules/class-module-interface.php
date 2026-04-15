<?php
/**
 * واجهة الوحدات المعيارية
 * 
 * @package Beiruttime\OSINT
 */

namespace Beiruttime\OSINT;

interface Module_Interface {
    /**
     * الحصول على معرف الوحدة
     */
    public function get_id();
    
    /**
     * الحصول على اسم الوحدة
     */
    public function get_name();
    
    /**
     * الحصول على وصف الوحدة
     */
    public function get_description();
    
    /**
     * تهيئة الوحدة
     */
    public function init();
    
    /**
     * تحميل الموارد (CSS/JS)
     */
    public function enqueue_assets();
    
    /**
     * تسجيل AJAX handlers
     */
    public function register_ajax_handlers();
    
    /**
     * الحصول على بيانات الوحدة
     */
    public function get_data($args = array());
    
    /**
     * معالجة طلب AJAX
     */
    public function handle_ajax($action);
    
    /**
     * التحقق من صلاحيات الوصول
     */
    public function check_permissions();
}
