<?php
/**
 * فئة صفحات الإدارة
 */

namespace Beiruttime\OSINT\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class AdminPages {
    
    public function __construct() {
        // تهيئة الصفحات
    }
    
    public static function get_available_pages() {
        return array(
            'dashboard' => array(
                'title' => __('لوحة التحكم', 'beiruttime-osint'),
                'slug' => 'beiruttime-osint',
                'capability' => 'manage_options'
            ),
            'hybrid_warfare' => array(
                'title' => __('الحرب المركبة', 'beiruttime-osint'),
                'slug' => 'beiruttime-hybrid-warfare',
                'capability' => 'manage_options'
            ),
            'settings' => array(
                'title' => __('الإعدادات', 'beiruttime-osint'),
                'slug' => 'beiruttime-settings',
                'capability' => 'manage_options'
            )
        );
    }
}
