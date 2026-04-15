<?php
/**
 * فئة معالجة طلبات AJAX
 */

namespace Beiruttime\OSINT\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class AjaxHandlers {
    
    public function __construct() {
        add_action('wp_ajax_osint_get_hybrid_dashboard', array($this, 'get_hybrid_dashboard'));
        add_action('wp_ajax_osint_get_layer_distribution', array($this, 'get_layer_distribution'));
        add_action('wp_ajax_osint_get_high_threat_events', array($this, 'get_high_threat_events'));
        add_action('wp_ajax_osint_get_multi_domain_events', array($this, 'get_multi_domain_events'));
    }
    
    public function get_hybrid_dashboard() {
        check_ajax_referer('osint_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        // جلب بيانات لوحة الحرب المركبة
        $data = array(
            'total_events' => 150,
            'high_threat' => 25,
            'multi_domain' => 40,
            'active_alerts' => 8
        );
        
        wp_send_json_success($data);
    }
    
    public function get_layer_distribution() {
        check_ajax_referer('osint_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $layers = array(
            'military' => 35,
            'security' => 20,
            'cyber' => 15,
            'political' => 25,
            'economic' => 18,
            'social' => 12,
            'energy' => 10,
            'geographic' => 8,
            'strategic' => 7
        );
        
        wp_send_json_success($layers);
    }
    
    public function get_high_threat_events() {
        check_ajax_referer('osint_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        $events = $wpdb->get_results(
            "SELECT id, title, threat_score, event_timestamp 
             FROM {$table} 
             WHERE threat_score >= 60 
             ORDER BY threat_score DESC 
             LIMIT 10",
            ARRAY_A
        );
        
        wp_send_json_success($events ?: array());
    }
    
    public function get_multi_domain_events() {
        check_ajax_referer('osint_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        $events = $wpdb->get_results(
            "SELECT id, title, multi_domain_score, hybrid_layers 
             FROM {$table} 
             WHERE multi_domain_score >= 30 
             ORDER BY multi_domain_score DESC 
             LIMIT 10",
            ARRAY_A
        );
        
        wp_send_json_success($events ?: array());
    }
}
