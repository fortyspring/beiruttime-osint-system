<?php
/**
 * PHPUnit Test for Security Fixes
 * 
 * @package BeirutTime_OSINT_Pro
 * @group security
 */

class SecurityFixesTest extends WP_UnitTestCase {
    
    /**
     * Set up before each test
     */
    public function setUp(): void {
        parent::setUp();
        require_once OSINT_PRO_PLUGIN_DIR . 'includes/security/class-security-fixes.php';
    }
    
    /**
     * Test secure file upload with valid JSON file
     */
    public function test_secure_file_upload_valid_json() {
        // Create a temporary JSON file
        $tmp_file = tmpfile();
        $tmp_path = stream_get_meta_data($tmp_file)['uri'];
        $test_data = array('test' => 'data', 'number' => 123);
        fwrite($tmp_file, json_encode($test_data));
        fflush($tmp_file);
        
        // Mock file upload array
        $mock_file = array(
            'name' => 'test.json',
            'type' => 'application/json',
            'tmp_name' => $tmp_path,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmp_path),
        );
        
        $result = sod_secure_file_upload($mock_file, ['application/json' => ['json']]);
        
        // Clean up
        fclose($tmp_file);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('file', $result);
        $this->assertArrayHasKey('url', $result);
        
        // Clean up uploaded file
        if (isset($result['file']) && file_exists($result['file'])) {
            wp_delete_file($result['file']);
        }
    }
    
    /**
     * Test secure file upload with invalid file type
     */
    public function test_secure_file_upload_invalid_type() {
        // Create a temporary PHP file (should be rejected)
        $tmp_file = tmpfile();
        $tmp_path = stream_get_meta_data($tmp_file)['uri'];
        fwrite($tmp_file, '<?php echo "malicious"; ?>');
        fflush($tmp_file);
        
        $mock_file = array(
            'name' => 'malicious.php',
            'type' => 'application/x-php',
            'tmp_name' => $tmp_path,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmp_path),
        );
        
        $result = sod_secure_file_upload($mock_file, ['application/json' => ['json']]);
        
        fclose($tmp_file);
        
        $this->assertWPError($result);
        $this->assertEquals('upload_failed', $result->get_error_code());
    }
    
    /**
     * Test secure file upload with invalid JSON content
     */
    public function test_secure_file_upload_invalid_json_content() {
        // Create a temporary file with invalid JSON
        $tmp_file = tmpfile();
        $tmp_path = stream_get_meta_data($tmp_file)['uri'];
        fwrite($tmp_file, 'This is not valid JSON');
        fflush($tmp_file);
        
        $mock_file = array(
            'name' => 'invalid.json',
            'type' => 'application/json',
            'tmp_name' => $tmp_path,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmp_path),
        );
        
        $result = sod_secure_file_upload($mock_file, ['application/json' => ['json']]);
        
        fclose($tmp_file);
        
        $this->assertWPError($result);
        $this->assertEquals('invalid_json', $result->get_error_code());
    }
    
    /**
     * Test input sanitization - text
     */
    public function test_sanitize_input_text() {
        $input = '<script>alert("XSS")</script>Hello World';
        $sanitized = sod_sanitize_input($input, 'text');
        
        $this->assertEquals('Hello World', $sanitized);
        $this->assertStringNotContainsString('<script>', $sanitized);
    }
    
    /**
     * Test input sanitization - integer
     */
    public function test_sanitize_input_integer() {
        $input = '123abc';
        $sanitized = sod_sanitize_input($input, 'int');
        
        $this->assertEquals(123, $sanitized);
        $this->assertIsInt($sanitized);
    }
    
    /**
     * Test input sanitization - email
     */
    public function test_sanitize_input_email() {
        $input = 'test@example.com';
        $sanitized = sod_sanitize_input($input, 'email');
        
        $this->assertEquals('test@example.com', $sanitized);
    }
    
    /**
     * Test input sanitization - URL
     */
    public function test_sanitize_input_url() {
        $input = 'https://example.com/path?query=value';
        $sanitized = sod_sanitize_input($input, 'url');
        
        $this->assertEquals('https://example.com/path?query=value', $sanitized);
    }
    
    /**
     * Test input sanitization - array
     */
    public function test_sanitize_input_array() {
        $input = array(
            'name' => '<script>bad</script>John',
            'age' => '25',
            'email' => 'john@example.com',
        );
        
        $sanitized = sod_sanitize_input($input, 'array');
        
        $this->assertIsArray($sanitized);
        $this->assertEquals('John', $sanitized['name']);
        $this->assertEquals('john@example.com', $sanitized['email']);
    }
    
    /**
     * Test nonce verification - valid nonce
     */
    public function test_verify_ajax_nonce_valid() {
        $nonce = wp_create_nonce(SOD_AJAX_NONCE_ACTION);
        $result = sod_verify_ajax_nonce($nonce, SOD_AJAX_NONCE_ACTION);
        
        $this->assertTrue($result);
    }
    
    /**
     * Test nonce verification - invalid nonce
     */
    public function test_verify_ajax_nonce_invalid() {
        $result = sod_verify_ajax_nonce('invalid_nonce', SOD_AJAX_NONCE_ACTION);
        
        $this->assertWPError($result);
        $this->assertEquals('invalid_nonce', $result->get_error_code());
    }
    
    /**
     * Test nonce verification - missing nonce
     */
    public function test_verify_ajax_nonce_missing() {
        $result = sod_verify_ajax_nonce(null, SOD_AJAX_NONCE_ACTION);
        
        $this->assertWPError($result);
        $this->assertEquals('missing_nonce', $result->get_error_code());
    }
    
    /**
     * Test output escaping - HTML context
     */
    public function test_esc_output_html() {
        $input = '<script>alert("XSS")</script>';
        $escaped = sod_esc_output($input, 'html', false);
        
        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertStringContainsString('&lt;script&gt;', $escaped);
    }
    
    /**
     * Test output escaping - JS context
     */
    public function test_esc_output_js() {
        $input = '"; alert("XSS"); //';
        $escaped = sod_esc_output($input, 'js', false);
        
        $this->assertStringNotContainsString('alert("XSS")', $escaped);
    }
    
    /**
     * Test output escaping - URL context
     */
    public function test_esc_output_url() {
        $input = 'javascript:alert("XSS")';
        $escaped = sod_esc_output($input, 'url', false);
        
        $this->assertStringNotContainsString('javascript:', $escaped);
    }
    
    /**
     * Test encryption and decryption
     */
    public function test_encrypt_decrypt_sensitive_data() {
        $original = 'sensitive_api_key_12345';
        $encrypted = sod_encrypt_sensitive_data($original);
        $decrypted = sod_decrypt_sensitive_data($encrypted);
        
        $this->assertEquals($original, $decrypted);
        $this->assertNotEquals($original, $encrypted);
    }
    
    /**
     * Test encryption produces different output each time
     */
    public function test_encryption_is_consistent() {
        $data = 'test_data';
        $encrypted1 = sod_encrypt_sensitive_data($data);
        $encrypted2 = sod_encrypt_sensitive_data($data);
        
        // Should be consistent with same key/salt
        $this->assertEquals($encrypted1, $encrypted2);
    }
    
    /**
     * Test SRI hash generation
     */
    public function test_generate_sri_hash() {
        // Create a temporary file
        $tmp_file = tmpfile();
        $tmp_path = stream_get_meta_data($tmp_file)['uri'];
        $content = 'console.log("test");';
        fwrite($tmp_file, $content);
        fflush($tmp_file);
        
        $hash = sod_generate_sri_hash($tmp_path);
        
        fclose($tmp_file);
        
        $this->assertIsString($hash);
        $this->assertStringStartsWith('sha384-', $hash);
        $this->assertEquals(64, strlen($hash) - 7); // sha384- prefix is 7 chars
    }
    
    /**
     * Test SRI hash generation with non-existent file
     */
    public function test_generate_sri_hash_nonexistent_file() {
        $hash = sod_generate_sri_hash('/non/existent/file.js');
        
        $this->assertFalse($hash);
    }
    
    /**
     * Test rate limiter - check rate limit allows first request
     */
    public function test_rate_limiter_allows_first_request() {
        $limiter = SOD_Rate_Limiter::get_instance();
        $identifier = 'test_user_' . time();
        
        $result = $limiter->check_rate_limit('ajax', $identifier);
        
        $this->assertTrue($result);
    }
    
    /**
     * Test rate limiter - blocks after exceeding limit
     */
    public function test_rate_limiter_blocks_after_limit() {
        $limiter = SOD_Rate_Limiter::get_instance();
        $identifier = 'test_rate_limit_' . time();
        
        // Make requests up to the limit (60 for ajax)
        for ($i = 0; $i < 60; $i++) {
            $limiter->check_rate_limit('ajax', $identifier);
        }
        
        // Next request should be blocked
        $result = $limiter->check_rate_limit('ajax', $identifier);
        
        $this->assertWPError($result);
        $this->assertEquals('rate_limit_exceeded', $result->get_error_code());
        
        // Clean up
        $limiter->reset_limit('ajax', $identifier);
    }
    
    /**
     * Test security logger
     */
    public function test_security_logger_logs_event() {
        $logger = SOD_Security_Logger::get_instance();
        
        // Log a test event
        $logger->log('test_event', 'Test message', array('key' => 'value'), 'info');
        
        // Check log file exists
        $upload_dir = wp_upload_dir();
        $log_file = trailingslashit($upload_dir['basedir']) . 'sod-security.log';
        
        $this->assertFileExists($log_file);
        
        // Clean up log file
        if (file_exists($log_file)) {
            unlink($log_file);
        }
    }
    
    /**
     * Test SQL injection attempt logging
     */
    public function test_sql_injection_attempt_logging() {
        $logger = SOD_Security_Logger::get_instance();
        
        $logger->log_sql_injection_attempt('SELECT * FROM users WHERE 1=1', 'test');
        
        $upload_dir = wp_upload_dir();
        $log_file = trailingslashit($upload_dir['basedir']) . 'sod-security.log';
        
        $this->assertFileExists($log_file);
        
        $log_content = file_get_contents($log_file);
        $this->assertStringContainsString('sql_injection_attempt', $log_content);
        $this->assertStringContainsString('CRITICAL', $log_content);
        
        // Clean up
        if (file_exists($log_file)) {
            unlink($log_file);
        }
    }
}
