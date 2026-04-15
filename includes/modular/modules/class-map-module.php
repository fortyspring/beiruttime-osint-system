<?php
/**
 * OSINT Map Module - Modular System
 * Interactive mapping and geolocation visualization
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSINT_Map_Module {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', [$this, 'register_submenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_osint_get_map_data', [$this, 'ajax_get_map_data']);
    }
    
    public function register_submenu() {
        add_submenu_page(
            'osint-pro-dashboard',
            'Map View',
            'Map View',
            'manage_options',
            'osint-pro-map',
            [$this, 'render_map']
        );
    }
    
    public function enqueue_assets($hook) {
        if ($hook !== 'osint-pro-page_osint-pro-map') {
            return;
        }
        
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
        wp_enqueue_script('osint-map', OSINT_PRO_PLUGIN_URL . 'assets/js/map-module.js', ['jquery', 'leaflet'], OSINT_PRO_VERSION, true);
    }
    
    public function render_map() {
        ?>
        <div class="wrap osint-pro-map">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="osint-map-controls">
                <select id="osint-map-filter">
                    <option value="all">All Events</option>
                    <option value="critical">Critical Only</option>
                    <option value="high">High Priority</option>
                    <option value="recent">Last 24 Hours</option>
                </select>
                <button id="osint-map-refresh" class="button button-primary">Refresh Map</button>
            </div>
            
            <div id="osint-map-container"></div>
            
            <div class="osint-map-sidebar">
                <h3>Event Details</h3>
                <div id="osint-map-event-info">Select a marker to view details</div>
            </div>
        </div>
        <?php
    }
    
    public function ajax_get_map_data() {
        check_ajax_referer('osint_pro_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'osint_pro_events';
        
        $filter = sanitize_text_field($_POST['filter'] ?? 'all');
        $where_clauses = [];
        
        if ($filter === 'critical') {
            $where_clauses[] = "severity = 'critical'";
        } elseif ($filter === 'high') {
            $where_clauses[] = "severity IN ('critical', 'high')";
        } elseif ($filter === 'recent') {
            $where_clauses[] = "created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        }
        
        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        
        $events = $wpdb->get_results("SELECT * FROM {$table} {$where_sql} AND latitude IS NOT NULL AND longitude IS NOT NULL");
        
        $map_data = [];
        foreach ($events as $event) {
            $map_data[] = [
                'id' => $event->id,
                'latitude' => floatval($event->latitude),
                'longitude' => floatval($event->longitude),
                'title' => $event->title,
                'severity' => $event->severity,
                'description' => $event->description,
                'timestamp' => $event->created_at
            ];
        }
        
        wp_send_json_success($map_data);
    }
}
