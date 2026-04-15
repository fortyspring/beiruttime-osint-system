<?php
/**
 * فئة قائمة الإدارة
 * Beiruttime OSINT - Admin Menu Class
 */

namespace Beiruttime\OSINT\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class AdminMenu {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
    }
    
    public function register_menu() {
        add_menu_page(
            __('Beiruttime OSINT', 'beiruttime-osint'),
            __('Beiruttime OSINT', 'beiruttime-osint'),
            'manage_options',
            'beiruttime-osint',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'beiruttime-osint',
            __('لوحة التحكم', 'beiruttime-osint'),
            __('لوحة التحكم', 'beiruttime-osint'),
            'manage_options',
            'beiruttime-osint',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'beiruttime-osint',
            __('الحرب المركبة', 'beiruttime-osint'),
            __('الحرب المركبة', 'beiruttime-osint'),
            'manage_options',
            'beiruttime-hybrid-warfare',
            array($this, 'render_hybrid_warfare')
        );
        
        add_submenu_page(
            'beiruttime-osint',
            __('اللوحات', 'beiruttime-osint'),
            __('اللوحات', 'beiruttime-osint'),
            'manage_options',
            'beiruttime-dashboards',
            array($this, 'render_dashboards')
        );
        
        add_submenu_page(
            'beiruttime-osint',
            __('الإعدادات', 'beiruttime-osint'),
            __('الإعدادات', 'beiruttime-osint'),
            'manage_options',
            'beiruttime-settings',
            array($this, 'render_settings')
        );
    }
    
    public function render_dashboard() {
        include BEIRUTTIME_OSINT_PLUGIN_DIR . 'views/admin/dashboard-page.php';
    }
    
    public function render_hybrid_warfare() {
        include BEIRUTTIME_OSINT_PLUGIN_DIR . 'views/admin/hybrid-warfare-page.php';
    }
    
    public function render_dashboards() {
        echo '<div class="wrap"><h1>' . __('اللوحات', 'beiruttime-osint') . '</h1>';
        echo '<p>اختر لوحة من القائمة الفرعية</p></div>';
    }
    
    public function render_settings() {
        echo '<div class="wrap"><h1>' . __('الإعدادات', 'beiruttime-osint') . '</h1>';
        echo '<p>إعدادات النظام</p></div>';
    }
}
