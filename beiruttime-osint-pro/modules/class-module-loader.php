<?php
/**
 * Module Loader
 * 
 * Loads and initializes all OSINT modules.
 * 
 * @package BeirutTime_OSINT_Pro
 * @subpackage Modules
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSINT_Module_Loader {
    
    /**
     * @var array Registered modules
     */
    private static $modules = array();
    
    /**
     * @var OSINT_Modular_Core Core instance
     */
    private static $core = null;
    
    /**
     * Initialize module loader
     * 
     * @param OSINT_Modular_Core $core
     * @return void
     */
    public static function init($core) {
        self::$core = $core;
        
        // Load base classes
        require_once dirname(__DIR__) . '/includes/modules/class-module-interface.php';
        require_once dirname(__DIR__) . '/includes/modules/class-base-module.php';
        
        // Load individual modules
        self::load_module('dashboard');
        self::load_module('map');
        self::load_module('chart');
        self::load_module('analysis');
        
        // Initialize active modules
        self::initialize_modules();
        
        do_action('osint_modules_loaded', self::$modules);
    }
    
    /**
     * Load a specific module
     * 
     * @param string $module_id Module ID
     * @return bool
     */
    private static function load_module($module_id) {
        $module_path = dirname(__FILE__) . '/' . $module_id . '/class-' . $module_id . '-module.php';
        
        if (!file_exists($module_path)) {
            error_log('[OSINT] Module not found: ' . $module_id);
            return false;
        }
        
        require_once $module_path;
        
        $class_name = 'OSINT_' . ucfirst($module_id) . '_Module';
        
        if (!class_exists($class_name)) {
            error_log('[OSINT] Module class not found: ' . $class_name);
            return false;
        }
        
        self::$modules[$module_id] = new $class_name(self::$core);
        
        return true;
    }
    
    /**
     * Initialize all loaded modules
     * 
     * @return void
     */
    private static function initialize_modules() {
        foreach (self::$modules as $module_id => $module) {
            if ($module->is_active()) {
                $module->init();
                do_action('osint_module_initialized', $module_id, $module);
            }
        }
    }
    
    /**
     * Get a specific module
     * 
     * @param string $module_id Module ID
     * @return OSINT_Base_Module|null
     */
    public static function get_module($module_id) {
        return self::$modules[$module_id] ?? null;
    }
    
    /**
     * Get all loaded modules
     * 
     * @return array
     */
    public static function get_modules() {
        return self::$modules;
    }
    
    /**
     * Check if a module is loaded
     * 
     * @param string $module_id Module ID
     * @return bool
     */
    public static function is_module_loaded($module_id) {
        return isset(self::$modules[$module_id]);
    }
    
    /**
     * Get active modules count
     * 
     * @return int
     */
    public static function get_active_count() {
        $count = 0;
        foreach (self::$modules as $module) {
            if ($module->is_active()) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Deactivate a module
     * 
     * @param string $module_id Module ID
     * @return bool
     */
    public static function deactivate_module($module_id) {
        if (!isset(self::$modules[$module_id])) {
            return false;
        }
        
        self::$modules[$module_id]->deactivate();
        unset(self::$modules[$module_id]);
        
        return true;
    }
    
    /**
     * Reload a module
     * 
     * @param string $module_id Module ID
     * @return bool
     */
    public static function reload_module($module_id) {
        self::deactivate_module($module_id);
        
        // Clear class cache
        $class_name = 'OSINT_' . ucfirst($module_id) . '_Module';
        if (class_exists($class_name)) {
            // Cannot undefine class in PHP, but we can prevent re-instantiation
        }
        
        return self::load_module($module_id);
    }
}

// Auto-load on WordPress init
add_action('plugins_loaded', function() {
    if (class_exists('OSINT_Modular_Core')) {
        $core = OSINT_Modular_Core::instance();
        OSINT_Module_Loader::init($core);
    }
}, 20);
