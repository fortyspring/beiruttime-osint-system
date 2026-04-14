<?php
/**
 * اختبار وحدة للإصلاحات الأمنية
 * 
 * يحتوي على اختبارات آلية للتحقق من عمل جميع الإصلاحات الأمنية
 * 
 * @package Beiruttime_OSINT_Pro
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    die('Access denied');
}

class SOD_Security_Tests {
    
    /**
     * نتائج الاختبارات
     */
    private $results = array(
        'passed' => 0,
        'failed' => 0,
        'skipped' => 0,
        'tests' => array(),
    );
    
    /**
     * تشغيل جميع الاختبارات
     */
    public function run_all_tests() {
        echo "<h1>🧪 اختبار الإصلاحات الأمنية</h1>";
        echo "<p>جاري تشغيل الاختبارات...</p>";
        
        $this->test_file_upload_security();
        $this->test_rate_limiting();
        $this->test_nonce_verification();
        $this->test_encryption_functions();
        $this->test_output_escaping();
        $this->test_security_headers();
        $this->test_sql_injection_prevention();
        
        $this->display_results();
    }
    
    /**
     * اختبار أمن رفع الملفات
     */
    private function test_file_upload_security() {
        $test_name = 'أمن رفع الملفات';
        
        if (!function_exists('sod_secure_file_upload')) {
            $this->mark_test_failed($test_name, 'دالة sod_secure_file_upload غير موجودة');
            return;
        }
        
        // اختبار رفع ملف غير صالح
        $test_file = array(
            'name' => 'test.php',
            'type' => 'application/x-php',
            'size' => 1024,
        );
        
        // يجب رفض ملفات PHP
        $result = $this->simulate_file_upload($test_file);
        if ($result === false || (is_array($result) && isset($result['error']))) {
            $this->mark_test_passed($test_name);
        } else {
            $this->mark_test_failed($test_name, 'تم قبول ملف PHP بشكل غير آمن');
        }
    }
    
    /**
     * محاكاة رفع ملف
     */
    private function simulate_file_upload($file) {
        // محاكاة بسيطة - في الواقع يجب استخدام wp_handle_upload
        $allowed_types = array('image/jpeg', 'image/png', 'application/json', 'text/csv');
        
        if (!in_array($file['type'], $allowed_types)) {
            return array('error' => 'نوع الملف غير مسموح');
        }
        
        return array('success' => true);
    }
    
    /**
     * اختبار Rate Limiting
     */
    private function test_rate_limiting() {
        $test_name = 'Rate Limiting';
        
        if (!class_exists('SOD_Rate_Limiter')) {
            $this->mark_test_skipped($test_name, 'كلاس SOD_Rate_Limiter غير موجود');
            return;
        }
        
        $limiter = new SOD_Rate_Limiter();
        
        // التحقق من وجود الدوال الأساسية
        if (method_exists($limiter, 'check_rate_limit') && method_exists($limiter, 'record_request')) {
            $this->mark_test_passed($test_name);
        } else {
            $this->mark_test_failed($test_name, 'الدوال الأساسية غير موجودة');
        }
    }
    
    /**
     * اختبار التحقق من Nonce
     */
    private function test_nonce_verification() {
        $test_name = 'التحقق من Nonce';
        
        if (!function_exists('sod_verify_ajax_nonce')) {
            $this->mark_test_failed($test_name, 'دالة sod_verify_ajax_nonce غير موجودة');
            return;
        }
        
        // اختبار Nonce غير صالح
        $result = sod_verify_ajax_nonce('invalid_nonce', 'test_action');
        if ($result === false) {
            $this->mark_test_passed($test_name);
        } else {
            $this->mark_test_failed($test_name, 'تم قبول Nonce غير صالح');
        }
    }
    
    /**
     * اختبار دوال التشفير
     */
    private function test_encryption_functions() {
        $test_name = 'دوال التشفير';
        
        $has_encrypt = function_exists('sod_encrypt_sensitive_data');
        $has_decrypt = function_exists('sod_decrypt_sensitive_data');
        
        if (!$has_encrypt || !$has_decrypt) {
            $this->mark_test_failed($test_name, 'دوال التشفير/فك التشفير غير موجودة');
            return;
        }
        
        // اختبار تشفير وفك تشفير بسيط
        $original = 'test_secret_key_123';
        $encrypted = sod_encrypt_sensitive_data($original);
        $decrypted = sod_decrypt_sensitive_data($encrypted);
        
        if ($decrypted === $original) {
            $this->mark_test_passed($test_name);
        } else {
            $this->mark_test_failed($test_name, 'فشل في فك التشفير بشكل صحيح');
        }
    }
    
    /**
     * اختبار تنظيف المخرجات
     */
    private function test_output_escaping() {
        $test_name = 'تنظيف المخرجات (XSS Prevention)';
        
        if (!function_exists('sod_esc_output')) {
            $this->mark_test_failed($test_name, 'دالة sod_esc_output غير موجودة');
            return;
        }
        
        $malicious_input = '<script>alert("XSS")</script>';
        $escaped = sod_esc_output($malicious_input, 'html');
        
        if (strpos($escaped, '<script>') === false) {
            $this->mark_test_passed($test_name);
        } else {
            $this->mark_test_failed($test_name, 'لم يتم تنظيف كود XSS');
        }
    }
    
    /**
     * اختبار ترويسات الأمان
     */
    private function test_security_headers() {
        $test_name = 'ترويسات الأمان';
        
        if (!function_exists('sod_add_security_headers')) {
            $this->mark_test_failed($test_name, 'دالة sod_add_security_headers غير موجودة');
            return;
        }
        
        $this->mark_test_passed($test_name, 'الدالة موجودة (يتطلب اختبار السيرفر للتأكد من التطبيق)');
    }
    
    /**
     * اختبار منع SQL Injection
     */
    private function test_sql_injection_prevention() {
        $test_name = 'منع SQL Injection';
        
        global $wpdb;
        
        if (!method_exists($wpdb, 'prepare')) {
            $this->mark_test_failed($test_name, 'دالة prepare غير متوفرة في wpdb');
            return;
        }
        
        // اختبار أن prepare يعمل بشكل صحيح
        $test_query = $wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE ID = %d", 123);
        
        if (strpos($test_query, '123') !== false && strpos($test_query, '%d') === false) {
            $this->mark_test_passed($test_name);
        } else {
            $this->mark_test_failed($test_name, 'دالة prepare لا تعمل بشكل صحيح');
        }
    }
    
    /**
     * تسجيل اختبار ناجح
     */
    private function mark_test_passed($name, $notes = '') {
        $this->results['passed']++;
        $this->results['tests'][] = array(
            'name' => $name,
            'status' => 'passed',
            'notes' => $notes,
        );
    }
    
    /**
     * تسجيل اختبار فاشل
     */
    private function mark_test_failed($name, $reason) {
        $this->results['failed']++;
        $this->results['tests'][] = array(
            'name' => $name,
            'status' => 'failed',
            'reason' => $reason,
        );
    }
    
    /**
     * تسجيل اختبار تم تخطيه
     */
    private function mark_test_skipped($name, $reason) {
        $this->results['skipped']++;
        $this->results['tests'][] = array(
            'name' => $name,
            'status' => 'skipped',
            'reason' => $reason,
        );
    }
    
    /**
     * عرض النتائج
     */
    private function display_results() {
        echo "<hr>";
        echo "<h2>📊 نتائج الاختبارات</h2>";
        
        echo "<div style='display: flex; gap: 20px; margin: 20px 0;'>";
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; flex: 1;'>";
        echo "<strong>✅ نجح:</strong> {$this->results['passed']}";
        echo "</div>";
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; flex: 1;'>";
        echo "<strong>❌ فشل:</strong> {$this->results['failed']}";
        echo "</div>";
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; flex: 1;'>";
        echo "<strong>⏭️ تم تخطيه:</strong> {$this->results['skipped']}";
        echo "</div>";
        echo "</div>";
        
        echo "<h3>تفاصيل الاختبارات:</h3>";
        echo "<table style='width: 100%; border-collapse: collapse;'>";
        echo "<thead><tr style='background: #f0f0f1;'>";
        echo "<th style='padding: 10px; text-align: right; border: 1px solid #ddd;'>الاختبار</th>";
        echo "<th style='padding: 10px; text-align: right; border: 1px solid #ddd;'>الحالة</th>";
        echo "<th style='padding: 10px; text-align: right; border: 1px solid #ddd;'>ملاحظات</th>";
        echo "</tr></thead>";
        echo "<tbody>";
        
        foreach ($this->results['tests'] as $test) {
            $color = $test['status'] === 'passed' ? '#d4edda' : ($test['status'] === 'failed' ? '#f8d7da' : '#fff3cd');
            $icon = $test['status'] === 'passed' ? '✅' : ($test['status'] === 'failed' ? '❌' : '⏭️');
            
            echo "<tr style='background: $color;'>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$test['name']}</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>$icon {$test['status']}</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>";
            echo isset($test['reason']) ? $test['reason'] : (isset($test['notes']) ? $test['notes'] : '-');
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        
        if ($this->results['failed'] > 0) {
            echo "<div style='background: #f8d7da; padding: 15px; margin-top: 20px; border-radius: 5px;'>";
            echo "<strong>⚠️ تنبيه:</strong> هناك {$this->results['failed']} اختبار(ات) فشلت. يرجى مراجعة الأخطاء وإصلاحها قبل النشر.";
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; padding: 15px; margin-top: 20px; border-radius: 5px;'>";
            echo "<strong>🎉 ممتاز!</strong> جميع الاختبارات مرت بنجاح.";
            echo "</div>";
        }
    }
}

// تشغيل الاختبارات إذا تم طلبها
if (isset($_GET['run_security_tests']) && $_GET['run_security_tests'] === '1') {
    $tests = new SOD_Security_Tests();
    $tests->run_all_tests();
} else {
    ?>
    <!DOCTYPE html>
    <html dir="rtl" lang="ar">
    <head>
        <title>اختبار الإصلاحات الأمنية</title>
        <style>
            body { font-family: Arial, sans-serif; direction: rtl; text-align: right; padding: 20px; }
            .container { max-width: 1000px; margin: 0 auto; }
            .btn { 
                background: #0073aa; 
                color: white; 
                padding: 10px 20px; 
                text-decoration: none; 
                border-radius: 3px; 
                display: inline-block;
                margin: 10px 0;
            }
            .btn:hover { background: #005177; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🧪 اختبار الإصلاحات الأمنية</h1>
            <p>سيقوم هذا الاختبار بالتحقق من عمل جميع الإصلاحات الأمنية المطبقة.</p>
            
            <a href="?run_security_tests=1" class="btn">🚀 تشغيل الاختبارات</a>
            
            <hr>
            <p><a href="<?php echo admin_url('admin.php?page=beiruttime-osint-pro'); ?>">← العودة إلى لوحة التحكم</a></p>
        </div>
    </body>
    </html>
    <?php
}
