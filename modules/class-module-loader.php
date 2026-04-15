<?php
/**
 * محمّل الوحدات
 */

namespace Beiruttime\OSINT\Modules;

if (!defined('ABSPATH')) {
    exit;
}

class Module_Loader {
    
    private static $modules = array();
    
    public static function register($name, $instance) {
        self::$modules[$name] = $instance;
    }
    
    public static function get_module($name) {
        return isset(self::$modules[$name]) ? self::$modules[$name] : null;
    }
    
    public static function get_all_modules() {
        return self::$modules;
    }
    
    public static function init_all() {
        foreach (self::$modules as $module) {
            if (method_exists($module, 'init')) {
                $module->init();
            }
        }
    }
}
