<?php
/**
 * OSINT Pro - Modular Architecture Base
 * 
 * This file initializes the modular structure for the OSINT plugin.
 * It replaces the monolithic approach with a clean, maintainable architecture.
 * 
 * @package BeirutTime_OSINT_Pro
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants if not already defined
if (!defined('OSINT_PRO_PLUGIN_DIR')) {
    define('OSINT_PRO_PLUGIN_DIR', dirname(dirname(__FILE__)));
}

if (!defined('OSINT_PRO_PLUGIN_URL')) {
    define('OSINT_PRO_PLUGIN_URL', plugin_dir_url(dirname(dirname(__FILE__))));
}

class OSINT_Modular_Core {
    
    private static $instance = null;
    private $modules = array();
    private $cache_handler = null;
    private $websocket_handler = null;
    private $handlers = array();
    private $services = array();
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize modular system
     */
    public function __construct() {
        $this->load_dependencies();
        $this->load_modular_components();
        $this->init_cache();
        $this->init_modules();
        $this->init_websocket();
        $this->register_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        $files = array(
            'cache/class-cache-handler.php',
            'websocket/class-websocket-handler.php',
            'modules/class-module-interface.php',
            'modules/class-base-module.php',
        );
        
        foreach ($files as $file) {
            $path = OSINT_PRO_PLUGIN_DIR . '/includes/' . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }
    
    /**
     * Load modular components (handlers, services, modules)
     */
    private function load_modular_components() {
        // Load handlers
        $handler_files = array(
            'data-handler' => 'modular/handlers/class-data-handler.php',
            'api-handler' => 'modular/handlers/class-api-handler.php',
        );
        
        foreach ($handler_files as $key => $file) {
            $path = OSINT_PRO_PLUGIN_DIR . '/includes/' . $file;
            if (file_exists($path)) {
                require_once $path;
                $class_name = 'SOD_' . str_replace('-', '_', $key);
                if (class_exists($class_name)) {
                    $this->handlers[$key] = call_user_func([$class_name, 'get_instance']);
                }
            }
        }
        
        // Load services
        $service_files = array(
            'telegram-service' => 'modular/services/class-telegram-service.php',
            'analysis-service' => 'modular/services/class-analysis-service.php',
        );
        
        foreach ($service_files as $key => $file) {
            $path = OSINT_PRO_PLUGIN_DIR . '/includes/' . $file;
            if (file_exists($path)) {
                require_once $path;
                $class_name = 'SOD_' . str_replace('-', '_', $key);
                if (class_exists($class_name)) {
                    $this->services[$key] = call_user_func([$class_name, 'get_instance']);
                }
            }
        }
        
        // Load modules
        $module_files = array(
            'dashboard-module' => 'modular/modules/class-dashboard-module.php',
            'map-module' => 'modular/modules/class-map-module.php',
            'chart-module' => 'modular/modules/class-chart-module.php',
            'analysis-module' => 'modular/modules/class-analysis-module.php',
            'export-module' => 'modular/modules/class-export-module.php',
        );
        
        foreach ($module_files as $key => $file) {
            $path = OSINT_PRO_PLUGIN_DIR . '/includes/' . $file;
            if (file_exists($path)) {
                require_once $path;
                $class_name = 'OSINT_' . str_replace('-', '_', $key);
                if (class_exists($class_name)) {
                    $this->modules[$key] = call_user_func([$class_name, 'get_instance']);
                }
            }
        }
    }
    
    /**
     * Initialize cache system
     */
    private function init_cache() {
        if (class_exists('OSINT_Cache_Handler')) {
            $this->cache_handler = OSINT_Cache_Handler::get_instance();
        }
    }
    
    /**
     * Initialize additional modules (legacy support)
     */
    private function init_modules() {
        // Modules are now loaded in load_modular_components()
        // This method kept for backward compatibility
        do_action('osint_pro_modules_initialized', $this->modules);
    }
    
    /**
     * Initialize WebSocket for real-time updates
     */
    private function init_websocket() {
        if (class_exists('OSINT_WebSocket_Handler')) {
            $this->websocket_handler = OSINT_WebSocket_Handler::get_instance();
        }
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('wp_ajax_osint_module_action', array($this, 'handle_module_ajax'));
        add_action('admin_init', array($this, 'check_dependencies'));
        add_filter('plugin_action_links', array($this, 'add_action_links'), 10, 2);
        
        // Cache clearing on post update
        add_action('save_post', array($this, 'clear_related_cache'));
        
        // Shutdown hook for performance
        add_action('shutdown', array($this, 'cleanup'));
    }
    
    /**
     * Check system dependencies
     */
    public function check_dependencies() {
        $missing = array();
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $missing[] = 'PHP 7.4 or higher';
        }
        
        // Check WordPress version
        global $wp_version;
        if (version_compare($wp_version, '5.8', '<')) {
            $missing[] = 'WordPress 5.8 or higher';
        }
        
        // Check required extensions
        if (!extension_loaded('json')) {
            $missing[] = 'JSON extension';
        }
        
        if (!empty($missing)) {
            add_action('admin_notices', function() use ($missing) {
                echo '<div class="notice notice-error"><p><strong>OSINT Pro:</strong> Missing dependencies: ' . implode(', ', $missing) . '</p></div>';
            });
        }
    }
    
    /**
     * Add plugin action links
     */
    public function add_action_links($links, $file) {
        if (strpos($file, 'osint-pro') !== false || strpos($file, 'beirut-time-osint') !== false) {
            $custom_links = array(
                '<a href="' . admin_url('admin.php?page=osint-pro-dashboard') . '">Dashboard</a>',
                '<a href="' . admin_url('admin.php?page=osint-pro-settings') . '">Settings</a>'
            );
            $links = array_merge($custom_links, $links);
        }
        return $links;
    }
    
    /**
     * Handle module AJAX requests
     */
    public function handle_module_ajax() {
        check_ajax_referer('osint_nonce', 'nonce');
        
        $module_id = sanitize_text_field($_POST['module_id'] ?? '');
        $action = sanitize_text_field($_POST['action_type'] ?? '');
        
        if (empty($module_id) || !isset($this->modules[$module_id])) {
            wp_send_json_error(array('message' => 'Invalid module'));
            return;
        }
        
        $module = $this->modules[$module_id];
        $result = $module->handle_ajax($action, $_POST);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }
    
    /**
     * Clear cache related to updated content
     */
    public function clear_related_cache($post_id) {
        if ($this->cache_handler) {
            $this->cache_handler->clear_related($post_id);
        }
    }
    
    /**
     * Cleanup on shutdown
     */
    public function cleanup() {
        if ($this->cache_handler) {
            $this->cache_handler->cleanup();
        }
    }
    
    /**
     * Get module by ID
     */
    public function get_module($id) {
        return $this->modules[$id] ?? null;
    }
    
    /**
     * Get all active modules
     */
    public function get_modules() {
        return $this->modules;
    }
    
    /**
     * Get all handlers
     */
    public function get_handlers() {
        return $this->handlers;
    }
    
    /**
     * Get all services
     */
    public function get_services() {
        return $this->services;
    }
    
    /**
     * Get cache handler
     */
    public function get_cache() {
        return $this->cache_handler;
    }
    
    /**
     * Get WebSocket handler
     */
    public function get_websocket() {
        return $this->websocket_handler;
    }
    
    /**
     * Get system status
     */
    public function get_system_status() {
        return array(
            'handlers' => array_keys($this->handlers),
            'services' => array_keys($this->services),
            'modules' => array_keys($this->modules),
            'cache_active' => $this->cache_handler !== null,
            'websocket_active' => $this->websocket_handler !== null,
            'total_components' => count($this->handlers) + count($this->services) + count($this->modules)
        );
    }
}

// Initialize the modular core
function osint_init_modular() {
    return OSINT_Modular_Core::get_instance();
}

// Auto-initialize if not disabled
if (!defined('OSINT_DISABLE_MODULAR') || !OSINT_DISABLE_MODULAR) {
    add_action('plugins_loaded', 'osint_init_modular', 5);
}
