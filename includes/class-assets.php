<?php
/**
 * فئة إدارة الموارد (Assets)
 */

namespace Beiruttime\OSINT\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

class Assets {
    
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'beiruttime') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'osint-admin-css',
            BEIRUTTIME_OSINT_ASSETS_DIR . 'css/admin.css',
            array(),
            BEIRUTTIME_OSINT_VERSION
        );
        
        // JS
        wp_enqueue_script(
            'osint-admin-js',
            BEIRUTTIME_OSINT_ASSETS_DIR . 'js/admin.js',
            array('jquery', 'chartjs'),
            BEIRUTTIME_OSINT_VERSION,
            true
        );
        
        wp_localize_script('osint-admin-js', 'osintAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('osint_nonce'),
            'strings' => array(
                'loading' => __('جاري التحميل...', 'beiruttime-osint'),
                'error' => __('حدث خطأ', 'beiruttime-osint')
            )
        ));
    }
    
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'osint-frontend-css',
            BEIRUTTIME_OSINT_ASSETS_DIR . 'css/frontend.css',
            array(),
            BEIRUTTIME_OSINT_VERSION
        );
        
        wp_enqueue_script(
            'osint-frontend-js',
            BEIRUTTIME_OSINT_ASSETS_DIR . 'js/frontend.js',
            array('jquery'),
            BEIRUTTIME_OSINT_VERSION,
            true
        );
    }
}
