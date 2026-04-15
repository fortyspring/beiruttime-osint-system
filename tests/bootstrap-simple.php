<?php
/**
 * Simple PHPUnit Bootstrap (without WordPress)
 * 
 * Use this for testing pure PHP classes without WordPress dependencies.
 */

// Define plugin constants
define('OSINT_PRO_PLUGIN_DIR', dirname(__DIR__) . '/');
define('OSINT_PRO_VERSION', '2.0.0');

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load test helpers
require_once __DIR__ . '/helpers/class-test-helpers.php';

// Mock WordPress functions if needed
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return md5($action . microtime());
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return true; // Simplified for testing
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        return array(
            'basedir' => sys_get_temp_dir() . '/uploads',
            'baseurl' => 'http://example.org/uploads',
        );
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit($string) {
        return rtrim($string, '/') . '/';
    }
}

if (!function_exists('wp_delete_file')) {
    function wp_delete_file($file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
}

if (!class_exists('WP_UnitTestCase')) {
    class WP_UnitTestCase extends PHPUnit\Framework\TestCase {
        // Simple mock for WordPress test case
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        private $code = '';
        private $message = '';
        
        public function __construct($code = '', $message = '') {
            $this->code = $code;
            $this->message = $message;
        }
        
        public function get_error_code() {
            return $this->code;
        }
        
        public function get_error_message() {
            return $this->message;
        }
    }
}
