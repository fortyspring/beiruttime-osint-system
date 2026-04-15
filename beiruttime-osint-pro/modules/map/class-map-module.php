<?php
/**
 * Map Module
 * 
 * Interactive geographic mapping module for OSINT events.
 * Provides geospatial visualization, heatmaps, and location-based filtering.
 * 
 * @package BeirutTime_OSINT_Pro
 * @subpackage Modules/Map
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__DIR__) . '/modules/class-base-module.php';

class OSINT_Map_Module extends OSINT_Base_Module {
    
    /**
     * {@inheritdoc}
     */
    public function get_id() {
        return 'map';
    }
    
    /**
     * {@inheritdoc}
     */
    public function get_name() {
        return __('الخريطة الجغرافية', 'beiruttime-osint-pro');
    }
    
    /**
     * {@inheritdoc}
     */
    public function get_version() {
        return '2.0.0';
    }
    
    /**
     * {@inheritdoc}
     */
    public function init() {
        if (!$this->is_active()) {
            return;
        }
        
        // Register AJAX handlers
        add_action('wp_ajax_osint_map_get_events', array($this, 'ajax_get_map_events'));
        add_action('wp_ajax_osint_map_get_heatmap', array($this, 'ajax_get_heatmap'));
        add_action('wp_ajax_osint_map_get_cluster', array($this, 'ajax_get_cluster'));
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Register map widget
        add_action('wp_dashboard_setup', array($this, 'register_map_widget'));
        
        $this->log('Map module initialized');
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'osint') === false && !did_action('wp_dashboard_setup')) {
            return;
        }
        
        // Leaflet CSS & JS
        wp_enqueue_style(
            'leaflet-css',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            array(),
            '1.9.4'
        );
        
        wp_enqueue_script(
            'leaflet-js',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            array(),
            '1.9.4',
            true
        );
        
        // Leaflet Heatmap
        wp_enqueue_script(
            'leaflet-heatmap',
            'https://leaflet.github.io/Leaflet.heat/dist/leaflet-heat.js',
            array('leaflet-js'),
            '0.2.0',
            true
        );
        
        // Custom map script
        wp_enqueue_script(
            'osint-map-script',
            BEIRUTTIME_OSINT_PRO_URL . 'assets/js/map.js',
            array('jquery', 'leaflet-js', 'leaflet-heatmap'),
            $this->version,
            true
        );
        
        wp_localize_script('osint-map-script', 'osintMap', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('osint_map_nonce'),
            'defaultCenter' => $this->config['default_center'] ?? array(33.8938, 35.5018), // Beirut
            'defaultZoom' => $this->config['default_zoom'] ?? 7,
            'i18n' => array(
                'loading' => __('جاري تحميل الخريطة...', 'beiruttime-osint-pro'),
                'noEvents' => __('لا توجد أحداث في هذه المنطقة', 'beiruttime-osint-pro'),
            )
        ));
    }
    
    /**
     * Register map widget
     */
    public function register_map_widget() {
        wp_add_dashboard_widget(
            'osint_interactive_map',
            __('الخريطة التفاعلية', 'beiruttime-osint-pro'),
            array($this, 'render_map_widget')
        );
    }
    
    /**
     * Render map widget
     */
    public function render_map_widget() {
        include dirname(__FILE__) . '/views/map-widget.php';
    }
    
    /**
     * Render full map page
     */
    public function render_map_page() {
        include dirname(__FILE__) . '/views/map-page.php';
    }
    
    /**
     * Get map events
     * 
     * @param array $filters Optional filters
     * @return array
     */
    public function get_map_events($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        return $this->get_cached('map_events_' . md5(json_encode($filters)), function() use ($wpdb, $table, $filters) {
            $where_clauses = array('geo_coordinates IS NOT NULL AND geo_coordinates != \'\'');
            $params = array();
            
            // Date filter
            if (!empty($filters['date_from'])) {
                $where_clauses[] = 'event_timestamp >= %d';
                $params[] = strtotime($filters['date_from']);
            }
            
            if (!empty($filters['date_to'])) {
                $where_clauses[] = 'event_timestamp <= %d';
                $params[] = strtotime($filters['date_to']);
            }
            
            // Threat level filter
            if (!empty($filters['threat_level'])) {
                switch ($filters['threat_level']) {
                    case 'low':
                        $where_clauses[] = 'threat_score < 30';
                        break;
                    case 'medium':
                        $where_clauses[] = 'threat_score BETWEEN 30 AND 60';
                        break;
                    case 'high':
                        $where_clauses[] = 'threat_score BETWEEN 61 AND 80';
                        break;
                    case 'critical':
                        $where_clauses[] = 'threat_score > 80';
                        break;
                }
            }
            
            // Event type filter
            if (!empty($filters['event_type'])) {
                $where_clauses[] = 'event_type = %s';
                $params[] = sanitize_text_field($filters['event_type']);
            }
            
            // Country filter
            if (!empty($filters['country'])) {
                $where_clauses[] = 'geo_country = %s';
                $params[] = sanitize_text_field($filters['country']);
            }
            
            $where_sql = implode(' AND ', $where_clauses);
            
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT id, title, event_type, threat_score, geo_coordinates, geo_country, geo_region, geo_city, event_timestamp
                 FROM $table
                 WHERE $where_sql
                 ORDER BY event_timestamp DESC
                 LIMIT 500",
                $params
            ), ARRAY_A);
            
            // Parse coordinates
            foreach ($results as &$event) {
                $coords = explode(',', $event['geo_coordinates']);
                $event['lat'] = floatval($coords[0]);
                $event['lng'] = floatval($coords[1]);
                unset($event['geo_coordinates']);
            }
            
            return $results ?: array();
        }, 300);
    }
    
    /**
     * Get heatmap data
     * 
     * @param array $filters
     * @return array
     */
    public function get_heatmap_data($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'so_news_events';
        
        return $this->get_cached('heatmap_data', function() use ($wpdb, $table, $filters) {
            $results = $wpdb->get_results(
                "SELECT geo_coordinates, COUNT(*) as intensity, AVG(threat_score) as avg_threat
                 FROM $table
                 WHERE geo_coordinates IS NOT NULL AND geo_coordinates != ''
                 GROUP BY geo_coordinates
                 HAVING COUNT(*) > 1
                 ORDER BY intensity DESC",
                ARRAY_A
            );
            
            $heatmap_points = array();
            foreach ($results as $row) {
                $coords = explode(',', $row['geo_coordinates']);
                $lat = floatval($coords[0]);
                $lng = floatval($coords[1]);
                $intensity = intval($row['intensity']);
                
                // Normalize intensity (0-1)
                $normalized_intensity = min($intensity / 10, 1.0);
                
                $heatmap_points[] = array($lat, $lng, $normalized_intensity);
            }
            
            return array(
                'points' => $heatmap_points,
                'max_intensity' => max(array_column($results, 'intensity')) ?: 1,
            );
        }, 600);
    }
    
    /**
     * Get clustered events
     * 
     * @param array $bounds Geographic bounds
     * @param int $zoom Zoom level
     * @return array
     */
    public function get_clustered_events($bounds, $zoom) {
        // Simple clustering based on grid
        $events = $this->get_map_events();
        
        if ($zoom > 10) {
            // No clustering at high zoom levels
            return array('type' => 'events', 'data' => $events);
        }
        
        // Create grid-based clusters
        $grid_size = pow(2, 10 - $zoom);
        $clusters = array();
        
        foreach ($events as $event) {
            $grid_x = floor($event['lat'] * $grid_size);
            $grid_y = floor($event['lng'] * $grid_size);
            $grid_key = $grid_x . '_' . $grid_y;
            
            if (!isset($clusters[$grid_key])) {
                $clusters[$grid_key] = array(
                    'type' => 'cluster',
                    'lat' => 0,
                    'lng' => 0,
                    'count' => 0,
                    'events' => array(),
                    'avg_threat' => 0,
                );
            }
            
            $clusters[$grid_key]['lat'] += $event['lat'];
            $clusters[$grid_key]['lng'] += $event['lng'];
            $clusters[$grid_key]['count']++;
            $clusters[$grid_key]['events'][] = $event;
            $clusters[$grid_key]['avg_threat'] += $event['threat_score'];
        }
        
        // Calculate averages
        $result = array();
        foreach ($clusters as $cluster) {
            $cluster['lat'] /= $cluster['count'];
            $cluster['lng'] /= $cluster['count'];
            $cluster['avg_threat'] /= $cluster['count'];
            $result[] = $cluster;
        }
        
        return array('type' => 'clusters', 'data' => $result);
    }
    
    /**
     * AJAX: Get map events
     */
    public function ajax_get_map_events() {
        check_ajax_referer('osint_map_nonce', 'nonce');
        
        $filters = isset($_POST['filters']) ? json_decode(stripslashes($_POST['filters']), true) : array();
        $events = $this->get_map_events($filters);
        
        wp_send_json_success(array(
            'events' => $events,
            'count' => count($events),
        ));
    }
    
    /**
     * AJAX: Get heatmap
     */
    public function ajax_get_heatmap() {
        check_ajax_referer('osint_map_nonce', 'nonce');
        
        $heatmap = $this->get_heatmap_data();
        
        wp_send_json_success($heatmap);
    }
    
    /**
     * AJAX: Get cluster
     */
    public function ajax_get_cluster() {
        check_ajax_referer('osint_map_nonce', 'nonce');
        
        $bounds = isset($_POST['bounds']) ? json_decode(stripslashes($_POST['bounds']), true) : array();
        $zoom = isset($_POST['zoom']) ? intval($_POST['zoom']) : 7;
        
        $clusters = $this->get_clustered_events($bounds, $zoom);
        
        wp_send_json_success($clusters);
    }
    
    /**
     * {@inheritdoc}
     */
    public function render() {
        ob_start();
        $this->render_map_page();
        return ob_get_clean();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function get_default_config() {
        return array(
            'enabled' => true,
            'cache_ttl' => 300,
            'default_center' => array(33.8938, 35.5018),
            'default_zoom' => 7,
            'min_zoom' => 4,
            'max_zoom' => 18,
            'show_heatmap' => true,
            'clustering_enabled' => true,
            'cluster_radius' => 50,
        );
    }
}
