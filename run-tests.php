<?php
/**
 * Simple PHPUnit Test Runner Script
 * 
 * Run this script to execute all unit tests:
 * php run-tests.php
 * 
 * Or with specific test file:
 * php run-tests.php tests/Unit/CacheHandlerTest.php
 */

// Define plugin constants
define('OSINT_PRO_PLUGIN_DIR', __DIR__ . '/');
define('OSINT_PRO_VERSION', '2.0.0');

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Mock WordPress functions
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return md5($action . microtime());
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return true;
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

if (!class_exists('WP_Error')) {
    class WP_Error {
        private $code = '';
        private $message = '';
        public function __construct($code = '', $message = '') {
            $this->code = $code;
            $this->message = $message;
        }
        public function get_error_code() { return $this->code; }
        public function get_error_message() { return $this->message; }
    }
}

echo "===========================================\n";
echo "OSINT Pro - Unit Test Runner\n";
echo "===========================================\n\n";

$test_dir = __DIR__ . '/tests/Unit/';
$test_files = glob($test_dir . '*Test.php');

if (empty($test_files)) {
    echo "No test files found in {$test_dir}\n";
    exit(1);
}

echo "Found " . count($test_files) . " test file(s):\n";
foreach ($test_files as $file) {
    echo "  - " . basename($file) . "\n";
}
echo "\n";

// Run PHPUnit
$phpunit_path = __DIR__ . '/vendor/bin/phpunit';
$config_file = __DIR__ . '/phpunit-simple.xml';

$specific_test = isset($argv[1]) ? $argv[1] : null;

if ($specific_test) {
    echo "Running specific test: {$specific_test}\n\n";
    passthru("php {$phpunit_path} -c {$config_file} {$specific_test} --testdox", $exit_code);
} else {
    echo "Running all tests...\n\n";
    passthru("php {$phpunit_path} -c {$config_file} --testdox", $exit_code);
}

echo "\n===========================================\n";
echo "Test run completed with exit code: {$exit_code}\n";
echo "===========================================\n";

exit($exit_code);
