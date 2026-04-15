<?php
/**
 * فئة GraphQL API
 */

namespace Beiruttime\OSINT\API;

if (!defined('ABSPATH')) {
    exit;
}

class GraphQL_API {
    
    public function __construct() {
        // تهيئة GraphQL عند توفر المكتبة
        add_action('init', array($this, 'register_types'));
    }
    
    public function register_types() {
        if (!function_exists('register_graphql_type')) {
            return;
        }
        
        register_graphql_type('HybridWarfareEvent', [
            'fields' => [
                'id' => ['type' => 'ID'],
                'title' => ['type' => 'String'],
                'threatScore' => ['type' => 'Int'],
                'hybridLayers' => ['type' => 'String'],
                'timestamp' => ['type' => 'String']
            ]
        ]);
        
        register_graphql_field('RootQuery', 'hybridEvents', [
            'type' => ['list_of' => 'HybridWarfareEvent'],
            'resolve' => function() {
                global $wpdb;
                $table = $wpdb->prefix . 'so_news_events';
                return $wpdb->get_results("SELECT * FROM {$table} ORDER BY event_timestamp DESC LIMIT 20");
            }
        ]);
    }
}
