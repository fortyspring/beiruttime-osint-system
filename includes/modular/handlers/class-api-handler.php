<?php
/**
 * API Handler - Modular System
 * Handles API requests, authentication, and responses for OSINT modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class SOD_API_Handler {
    
    private static $instance = null;
    private $api_endpoints = [];
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->register_endpoints();
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    /**
     * Register API endpoints
     */
    private function register_endpoints() {
        $this->api_endpoints = [
            'osint/search' => [
                'methods' => 'POST',
                'callback' => [$this, 'handle_search'],
                'permission' => 'manage_options'
            ],
            'osint/analyze' => [
                'methods' => 'POST',
                'callback' => [$this, 'handle_analyze'],
                'permission' => 'manage_options'
            ],
            'osint/export' => [
                'methods' => 'GET',
                'callback' => [$this, 'handle_export'],
                'permission' => 'manage_options'
            ],
            'osint/status' => [
                'methods' => 'GET',
                'callback' => [$this, 'handle_status'],
                'permission' => 'read'
            ]
        ];
    }
    
    /**
     * Register REST routes
     */
    public function register_routes() {
        foreach ($this->api_endpoints as $endpoint => $config) {
            register_rest_route('osint-pro/v1', '/' . $endpoint, [
                'methods' => $config['methods'],
                'callback' => $config['callback'],
                'permission_callback' => function() use ($config) {
                    return current_user_can($config['permission']);
                },
                'args' => $this->get_endpoint_args($endpoint)
            ]);
        }
    }
    
    /**
     * Get endpoint arguments
     */
    private function get_endpoint_args($endpoint) {
        $args = [];
        
        switch ($endpoint) {
            case 'osint/search':
                $args = [
                    'query' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'sources' => [
                        'required' => false,
                        'type' => 'array',
                        'default' => ['all']
                    ],
                    'limit' => [
                        'required' => false,
                        'type' => 'integer',
                        'default' => 50
                    ]
                ];
                break;
                
            case 'osint/analyze':
                $args = [
                    'data' => [
                        'required' => true,
                        'type' => 'array'
                    ],
                    'analysis_type' => [
                        'required' => true,
                        'type' => 'string',
                        'enum' => ['sentiment', 'entities', 'relationships', 'threat']
                    ]
                ];
                break;
                
            case 'osint/export':
                $args = [
                    'format' => [
                        'required' => false,
                        'type' => 'string',
                        'enum' => ['json', 'csv', 'xml'],
                        'default' => 'json'
                    ],
                    'data_id' => [
                        'required' => true,
                        'type' => 'integer'
                    ]
                ];
                break;
        }
        
        return $args;
    }
    
    /**
     * Handle search requests
     */
    public function handle_search($request) {
        // Rate limiting check
        if (!SOD_Rate_Limiter::is_allowed('api_search', 30)) {
            return new WP_Error('rate_limit_exceeded', 'Too many requests', ['status' => 429]);
        }
        
        $params = $request->get_params();
        $query = sanitize_text_field($params['query']);
        $sources = isset($params['sources']) ? $params['sources'] : ['all'];
        $limit = intval($params['limit']);
        
        // Log the request
        SOD_Security_Logger::log('api_search', [
            'query' => $query,
            'sources' => $sources,
            'limit' => $limit,
            'user' => get_current_user_id()
        ]);
        
        // Execute search using existing engine
        if (class_exists('SO_OSINT_Engine')) {
            $engine = SO_OSINT_Engine::get_instance();
            $results = $engine->search_osint($query, $sources, $limit);
            
            return new WP_REST_Response([
                'success' => true,
                'data' => $results,
                'count' => count($results),
                'timestamp' => current_time('mysql')
            ], 200);
        }
        
        return new WP_Error('engine_not_found', 'OSINT Engine not available', ['status' => 500]);
    }
    
    /**
     * Handle analysis requests
     */
    public function handle_analyze($request) {
        if (!SOD_Rate_Limiter::is_allowed('api_analyze', 20)) {
            return new WP_Error('rate_limit_exceeded', 'Too many requests', ['status' => 429]);
        }
        
        $params = $request->get_params();
        $data = $params['data'];
        $analysis_type = sanitize_text_field($params['analysis_type']);
        
        SOD_Security_Logger::log('api_analyze', [
            'type' => $analysis_type,
            'data_size' => count($data),
            'user' => get_current_user_id()
        ]);
        
        // Use existing analysis engine
        if (class_exists('SO_Analysis_Engine')) {
            $engine = SO_Analysis_Engine::get_instance();
            
            switch ($analysis_type) {
                case 'sentiment':
                    $result = $engine->analyze_sentiment($data);
                    break;
                case 'entities':
                    $result = $engine->extract_entities($data);
                    break;
                case 'relationships':
                    $result = $engine->map_relationships($data);
                    break;
                case 'threat':
                    $result = $engine->assess_threat_level($data);
                    break;
                default:
                    return new WP_Error('invalid_type', 'Invalid analysis type', ['status' => 400]);
            }
            
            return new WP_REST_Response([
                'success' => true,
                'data' => $result,
                'timestamp' => current_time('mysql')
            ], 200);
        }
        
        return new WP_Error('engine_not_found', 'Analysis Engine not available', ['status' => 500]);
    }
    
    /**
     * Handle export requests
     */
    public function handle_export($request) {
        if (!SOD_Rate_Limiter::is_allowed('api_export', 10)) {
            return new WP_Error('rate_limit_exceeded', 'Too many requests', ['status' => 429]);
        }
        
        $params = $request->get_params();
        $format = sanitize_text_field($params['format']);
        $data_id = intval($params['data_id']);
        
        // Get data handler
        $data_handler = SOD_Data_Handler::get_instance();
        
        // Retrieve data (implement based on your data structure)
        global $wpdb;
        $table = $wpdb->prefix . 'osint_pro_data';
        $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $data_id), ARRAY_A);
        
        if (!$data) {
            return new WP_Error('not_found', 'Data not found', ['status' => 404]);
        }
        
        $exported = $data_handler->export_data([$data], $format);
        
        SOD_Security_Logger::log('api_export', [
            'format' => $format,
            'data_id' => $data_id,
            'user' => get_current_user_id()
        ]);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $exported,
            'format' => $format,
            'timestamp' => current_time('mysql')
        ], 200);
    }
    
    /**
     * Handle status requests
     */
    public function handle_status($request) {
        $status = [
            'system' => 'operational',
            'version' => OSINT_PRO_VERSION ?? '1.0.0',
            'modules' => [
                'osint_engine' => class_exists('SO_OSINT_Engine') ? 'active' : 'inactive',
                'analysis_engine' => class_exists('SO_Analysis_Engine') ? 'active' : 'inactive',
                'alert_dispatcher' => class_exists('SO_Alert_Dispatcher') ? 'active' : 'inactive',
                'cron_manager' => class_exists('SO_Cron_Manager') ? 'active' : 'inactive'
            ],
            'rate_limits' => [
                'search' => SOD_Rate_Limiter::get_remaining('api_search', 30),
                'analyze' => SOD_Rate_Limiter::get_remaining('api_analyze', 20),
                'export' => SOD_Rate_Limiter::get_remaining('api_export', 10)
            ],
            'timestamp' => current_time('mysql')
        ];
        
        return new WP_REST_Response($status, 200);
    }
    
    /**
     * Verify API nonce
     */
    public static function verify_nonce($nonce) {
        return wp_verify_nonce($nonce, 'osint_pro_api_nonce');
    }
    
    /**
     * Generate API response wrapper
     */
    public static function response($success, $data = null, $message = '', $code = 200) {
        return new WP_REST_Response([
            'success' => $success,
            'data' => $data,
            'message' => $message,
            'timestamp' => current_time('mysql')
        ], $code);
    }
}
