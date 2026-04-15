<?php
/**
 * Security Fixes Implementation
 * معالجات أمنية شاملة لثغرات插件 بيروت تايم OSINT
 * 
 * @package Beiruttime\OSINT\Security
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * =============================================================================
 * FIX 1: Secure File Upload Handler
 * إصلاح ثغرات رفع الملفات - استخدام wp_handle_upload بدلاً من file_get_contents
 * =============================================================================
 */

if (!function_exists('sod_secure_file_upload')) {
    /**
     * معالجة آمنة لرفع الملفات مع التحقق من نوع الملف والامتداد
     * 
     * @param array $file ملف من $_FILES
     * @param array $allowed_types أنواع الملفات المسموحة
     * @return array|WP_Error بيانات الملف أو خطأ
     */
    function sod_secure_file_upload($file, $allowed_types = ['application/json' => ['json']]) {
        if (!isset($file) || !is_array($file)) {
            return new WP_Error('no_file', __('لم يتم رفع أي ملف.', 'beiruttime-osint-pro'));
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => __('الملف أكبر من الحد المسموح في إعدادات PHP.', 'beiruttime-osint-pro'),
                UPLOAD_ERR_FORM_SIZE => __('الملف أكبر من الحد المسموح في نموذج الرفع.', 'beiruttime-osint-pro'),
                UPLOAD_ERR_PARTIAL => __('تم رفع جزء فقط من الملف.', 'beiruttime-osint-pro'),
                UPLOAD_ERR_NO_FILE => __('لم يتم رفع أي ملف.', 'beiruttime-osint-pro'),
                UPLOAD_ERR_NO_TMP_DIR => __('لا يوجد مجلد مؤقت.', 'beiruttime-osint-pro'),
                UPLOAD_ERR_CANT_WRITE => __('فشل كتابة الملف على القرص.', 'beiruttime-osint-pro'),
                UPLOAD_ERR_EXTENSION => __('توقف رفع الملف بسبب إضافة PHP.', 'beiruttime-osint-pro'),
            ];
            $msg = $error_messages[$file['error']] ?? __('خطأ غير معروف في رفع الملف.', 'beiruttime-osint-pro');
            return new WP_Error('upload_error', $msg);
        }

        // التحقق من حجم الملف (الحد الأقصى: 5 ميجابايت)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', __('حجم الملف يتجاوز الحد المسموح (5 ميجابايت).', 'beiruttime-osint-pro'));
        }

        // استخدام wp_handle_upload للتحقق الآمن
        require_once ABSPATH . 'wp-admin/includes/file.php';
        
        $upload_overrides = [
            'test_form' => false,
            'mimes' => $allowed_types,
        ];

        $uploaded = wp_handle_upload($file, $upload_overrides);

        if (isset($uploaded['error'])) {
            return new WP_Error('upload_failed', $uploaded['error']);
        }

        if (!isset($uploaded['file']) || !isset($uploaded['url'])) {
            return new WP_Error('invalid_upload', __('بيانات الرفع غير صالحة.', 'beiruttime-osint-pro'));
        }

        // تحقق إضافي من محتوى الملف JSON
        if (in_array('json', $allowed_types['application/json'] ?? [], true)) {
            $content = file_get_contents($uploaded['file']);
            $decoded = json_decode($content, true);
            if (!is_array($decoded)) {
                // تنظيف الملف المرفوع
                wp_delete_file($uploaded['file']);
                return new WP_Error('invalid_json', __('الملف المرفوع ليس JSON صالح.', 'beiruttime-osint-pro'));
            }
        }

        return $uploaded;
    }
}

/**
 * =============================================================================
 * FIX 2: Input Sanitization Helper
 * دوال مساعدة لتنظيف المدخلات
 * =============================================================================
 */

if (!function_exists('sod_sanitize_input')) {
    /**
     * تنظيف المدخلات حسب النوع
     * 
     * @param mixed $input القيمة المدخلة
     * @param string $type نوع التنظيف
     * @return mixed القيمة المنظفة
     */
    function sod_sanitize_input($input, $type = 'text') {
        if ($input === null) {
            return null;
        }

        switch ($type) {
            case 'text':
                return sanitize_text_field(wp_unslash($input));
            
            case 'textarea':
                return sanitize_textarea_field(wp_unslash($input));
            
            case 'int':
            case 'integer':
                return (int)$input;
            
            case 'float':
                return (float)$input;
            
            case 'bool':
            case 'boolean':
                return (bool)$input;
            
            case 'email':
                return sanitize_email(wp_unslash($input));
            
            case 'url':
                return esc_url_raw(wp_unslash($input));
            
            case 'key':
                return sanitize_key(wp_unslash($input));
            
            case 'html':
                return wp_kses_post(wp_unslash($input));
            
            case 'array':
                if (!is_array($input)) {
                    return [];
                }
                return array_map(function($val) {
                    return is_array($val) ? sod_sanitize_input($val, 'array') : sod_sanitize_input($val, 'text');
                }, $input);
            
            default:
                return sanitize_text_field(wp_unslash($input));
        }
    }
}

/**
 * =============================================================================
 * FIX 3: Rate Limiting System
 * نظام الحد من التكرار Rate Limiting
 * =============================================================================
 */

if (!class_exists('SOD_Rate_Limiter')) {
    class SOD_Rate_Limiter {
        
        private static $instance = null;
        private $transient_prefix = 'sod_rate_limit_';
        private $default_limits = [
            'ajax' => ['requests' => 60, 'seconds' => 60],      // 60 طلب/دقيقة
            'upload' => ['requests' => 10, 'seconds' => 300],   // 10 رفعات/5 دقائق
            'export' => ['requests' => 5, 'seconds' => 300],    // 5 تصدير/5 دقائق
            'api' => ['requests' => 100, 'seconds' => 60],      // 100 طلب/دقيقة
        ];

        public static function get_instance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * التحقق من معدل الطلبات
         * 
         * @param string $action نوع الإجراء
         * @param string|null $identifier معرف المستخدم (IP أو User ID)
         * @return bool|WP_Error صحيح إذا سُمح، أو خطأ إذا تم التجاوز
         */
        public function check_rate_limit($action = 'ajax', $identifier = null) {
            if ($identifier === null) {
                $identifier = $this->get_client_identifier();
            }

            $limits = apply_filters('sod_rate_limits', $this->default_limits);
            $limit = $limits[$action] ?? $limits['ajax'];

            $transient_key = $this->transient_prefix . md5($action . '_' . $identifier);
            $current = get_transient($transient_key);

            if ($current === false) {
                set_transient($transient_key, 1, $limit['seconds']);
                return true;
            }

            if ((int)$current >= $limit['requests']) {
                return new WP_Error(
                    'rate_limit_exceeded',
                    sprintf(
                        __('تم تجاوز حد الطلبات المسموح. يرجى الانتظار %d ثانية.', 'beiruttime-osint-pro'),
                        $limit['seconds']
                    ),
                    ['retry_after' => $limit['seconds']]
                );
            }

            set_transient($transient_key, (int)$current + 1, $limit['seconds']);
            return true;
        }

        /**
         * الحصول على معرف العميل
         */
        private function get_client_identifier() {
            if (is_user_logged_in()) {
                return 'user_' . get_current_user_id();
            }
            
            // استخدام IP مع مراعاة البروكسي
            $ip = '';
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = explode(',', sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']));
                $ip = trim($ips[0]);
            } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
                $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
            }
            
            return 'ip_' . md5($ip ?: 'unknown');
        }

        /**
         * إعادة تعيين العداد
         */
        public function reset_limit($action, $identifier = null) {
            if ($identifier === null) {
                $identifier = $this->get_client_identifier();
            }
            delete_transient($this->transient_prefix . md5($action . '_' . $identifier));
        }
    }
}

/**
 * =============================================================================
 * FIX 4: Enhanced Nonce Verification
 * تحسين التحقق من Nonce
 * =============================================================================
 */

if (!function_exists('sod_verify_ajax_nonce')) {
    /**
     * التحقق من Nonce لطلبات AJAX
     * 
     * @param string|null $nonce قيمة Nonce
     * @param string $action اسم الإجراء
     * @return bool|WP_Error
     */
    function sod_verify_ajax_nonce($nonce, $action = SOD_AJAX_NONCE_ACTION) {
        if (empty($nonce)) {
            return new WP_Error('missing_nonce', __('رمز الأمان مفقود.', 'beiruttime-osint-pro'));
        }

        if (wp_verify_nonce($nonce, $action) === false) {
            return new WP_Error('invalid_nonce', __('رمز الأمان غير صالح.', 'beiruttime-osint-pro'));
        }

        return true;
    }
}

/**
 * =============================================================================
 * FIX 5: Secure Database Query Helper
 * دوال مساعدة للاستعلامات الآمنة
 * =============================================================================
 */

if (!function_exists('sod_safe_query')) {
    /**
     * تنفيذ استعلام SQL آمن مع prepare
     * 
     * @param string $query نص الاستعلام مع placeholders
     * @param array $params القيم للاستبدال
     * @param string $type نوع النتيجة (row|col|var|results)
     * @return mixed النتائج أو عدد الصفوف المتأثرة
     */
    function sod_safe_query($query, $params = [], $type = 'results') {
        global $wpdb;

        if (empty($params)) {
            $prepared = $query;
        } else {
            $prepared = $wpdb->prepare($query, $params);
        }

        switch ($type) {
            case 'row':
                return $wpdb->get_row($prepared, ARRAY_A);
            
            case 'col':
                return $wpdb->get_col($prepared);
            
            case 'var':
                return $wpdb->get_var($prepared);
            
            case 'results':
            default:
                return $wpdb->get_results($prepared, ARRAY_A);
        }
    }
}

if (!function_exists('sod_safe_delete')) {
    /**
     * حذف آمن من قاعدة البيانات
     */
    function sod_safe_delete($table, $where, $where_format = null) {
        global $wpdb;
        
        if (!is_array($where) || empty($where)) {
            return new WP_Error('invalid_where', __('شروط الحذف غير صالحة.', 'beiruttime-osint-pro'));
        }

        return $wpdb->delete($table, $where, $where_format);
    }
}

if (!function_exists('sod_safe_update')) {
    /**
     * تحديث آمن في قاعدة البيانات
     */
    function sod_safe_update($table, $data, $where, $data_format = null, $where_format = null) {
        global $wpdb;
        
        if (!is_array($data) || empty($data) || !is_array($where) || empty($where)) {
            return new WP_Error('invalid_data', __('بيانات التحديث غير صالحة.', 'beiruttime-osint-pro'));
        }

        return $wpdb->update($table, $data, $where, $data_format, $where_format);
    }
}

/**
 * =============================================================================
 * FIX 6: Output Escaping Helpers
 * دوال مساعدة لتنظيف المخرجات ومنع XSS
 * =============================================================================
 */

if (!function_exists('sod_esc_output')) {
    /**
     * تنظيف المخرجات حسب السياق
     * 
     * @param mixed $value القيمة
     * @param string $context السياق (html|attr|js|url|css)
     * @param bool $echo هل يتم الطباعة مباشرة
     * @return string|string|null القيمة المنظفة أو null
     */
    function sod_esc_output($value, $context = 'html', $echo = true) {
        if ($value === null) {
            return null;
        }

        $value = (string)$value;

        switch ($context) {
            case 'html':
                $escaped = esc_html($value);
                break;
            
            case 'attr':
                $escaped = esc_attr($value);
                break;
            
            case 'js':
                $escaped = esc_js($value);
                break;
            
            case 'url':
                $escaped = esc_url($value);
                break;
            
            case 'css':
                $escaped = esc_attr($value); // CSS يستخدم esc_attr كحل آمن
                break;
            
            case 'raw':
                $escaped = $value;
                break;
            
            default:
                $escaped = esc_html($value);
        }

        if ($echo) {
            echo $escaped; // phpcs:ignore WordPress.Security.EscapeOutput
            return '';
        }

        return $escaped;
    }
}

/**
 * =============================================================================
 * FIX 7: API Key Encryption
 * تشفير مفاتيح API الحساسة
 * =============================================================================
 */

if (!function_exists('sod_encrypt_sensitive_data')) {
    /**
     * تشفير البيانات الحساسة قبل التخزين
     * 
     * @param string $data البيانات المراد تشفيرها
     * @return string البيانات المشفرة Base64
     */
    function sod_encrypt_sensitive_data($data) {
        if (empty($data)) {
            return '';
        }

        // استخدام AUTH_KEY و AUTH_SALT من wp-config للتشفير
        $key = defined('AUTH_KEY') ? AUTH_KEY : wp_salt('auth');
        $salt = defined('AUTH_SALT') ? AUTH_SALT : wp_salt('secure_auth');
        
        $encryption_key = hash('sha256', $key . $salt, true);
        
        // Simple XOR encryption with base64 encoding
        $data_length = strlen($data);
        $key_length = strlen($encryption_key);
        
        $encrypted = '';
        for ($i = 0; $i < $data_length; $i++) {
            $encrypted .= $data[$i] ^ $encryption_key[$i % $key_length];
        }

        return base64_encode($encrypted);
    }
}

if (!function_exists('sod_decrypt_sensitive_data')) {
    /**
     * فك تشفير البيانات الحساسة
     * 
     * @param string $encrypted_data البيانات المشفرة
     * @return string البيانات الأصلية
     */
    function sod_decrypt_sensitive_data($encrypted_data) {
        if (empty($encrypted_data)) {
            return '';
        }

        $decoded = base64_decode($encrypted_data, true);
        if ($decoded === false) {
            return '';
        }

        $key = defined('AUTH_KEY') ? AUTH_KEY : wp_salt('auth');
        $salt = defined('AUTH_SALT') ? AUTH_SALT : wp_salt('secure_auth');
        
        $encryption_key = hash('sha256', $key . $salt, true);
        
        $data_length = strlen($decoded);
        $key_length = strlen($encryption_key);
        
        $decrypted = '';
        for ($i = 0; $i < $data_length; $i++) {
            $decrypted .= $decoded[$i] ^ $encryption_key[$i % $key_length];
        }

        return $decrypted;
    }
}

/**
 * =============================================================================
 * FIX 8: Security Headers
 * إضافة ترويسات الأمان
 * =============================================================================
 */

if (!function_exists('sod_add_security_headers')) {
    /**
     * إضافة ترويسات الأمان HTTP
     */
    function sod_add_security_headers() {
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https: wss:;");
        
        // X-Content-Type-Options
        header("X-Content-Type-Options: nosniff");
        
        // X-Frame-Options
        header("X-Frame-Options: SAMEORIGIN");
        
        // X-XSS-Protection
        header("X-XSS-Protection: 1; mode=block");
        
        // Referrer-Policy
        header("Referrer-Policy: strict-origin-when-cross-origin");
        
        // Permissions-Policy
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    }
}

// تطبيق ترويسات الأمان
add_action('send_headers', 'sod_add_security_headers');

/**
 * =============================================================================
 * FIX 9: Subresource Integrity (SRI) Helper
 * مساعد لتوليد تجزئات SRI للموارد الخارجية
 * =============================================================================
 */

if (!function_exists('sod_generate_sri_hash')) {
    /**
     * توليد تجزئة SRI لملف
     * 
     * @param string $file_path مسار الملف
     * @return string|false تجزئة SRI أو false عند الفشل
     */
    function sod_generate_sri_hash($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }

        $content = file_get_contents($file_path);
        if ($content === false) {
            return false;
        }

        $hash = base64_encode(hash('sha384', $content, true));
        return 'sha384-' . $hash;
    }
}

/**
 * =============================================================================
 * FIX 10: Secure Logging System
 * نظام تسجيل أمني
 * =============================================================================
 */

if (!class_exists('SOD_Security_Logger')) {
    class SOD_Security_Logger {
        
        private static $instance = null;
        private $log_file = '';
        private $max_log_size = 10485760; // 10MB

        public static function get_instance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct() {
            $upload_dir = wp_upload_dir();
            $this->log_file = trailingslashit($upload_dir['basedir']) . 'sod-security.log';
        }

        /**
         * تسجيل حدث أمني
         * 
         * @param string $event_type نوع الحدث
         * @param string $message رسالة الحدث
         * @param array $context سياق الحدث
         * @param string $level مستوى الخطورة (info|warning|error|critical)
         */
        public function log($event_type, $message, $context = [], $level = 'info') {
            $log_entry = [
                'timestamp' => current_time('mysql'),
                'level' => $level,
                'event_type' => $event_type,
                'message' => $message,
                'user_id' => get_current_user_id(),
                'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'context' => $context,
            ];

            $log_line = sprintf(
                "[%s] [%s] [%s] %s - User: %d, IP: %s%s\n",
                $log_entry['timestamp'],
                strtoupper($level),
                $event_type,
                $message,
                $log_entry['user_id'],
                $log_entry['user_ip'],
                !empty($context) ? ' - Context: ' . wp_json_encode($context) : ''
            );

            // تدوير ملف السجل إذا تجاوز الحجم الأقصى
            $this->rotate_log_if_needed();

            // الكتابة في ملف السجل
            file_put_contents($this->log_file, $log_line, FILE_APPEND | LOCK_EX);
        }

        /**
         * تسجيل محاولة وصول غير مصرح بها
         */
        public function log_unauthorized_access($action, $details = []) {
            $this->log('unauthorized_access', 'محاولة وصول غير مصرح بها: ' . $action, $details, 'warning');
        }

        /**
         * تسجيل محاولة SQL Injection
         */
        public function log_sql_injection_attempt($query, $source = '') {
            $this->log('sql_injection_attempt', 'محاولة حقن SQL: ' . substr($query, 0, 200), ['source' => $source], 'critical');
        }

        /**
         * تسجيل محاولة XSS
         */
        public function log_xss_attempt($input, $source = '') {
            $this->log('xss_attempt', 'محاولة XSS: ' . substr($input, 0, 200), ['source' => $source], 'critical');
        }

        /**
         * تسجيل تجاوز Rate Limit
         */
        public function log_rate_limit_exceeded($action, $identifier) {
            $this->log('rate_limit_exceeded', 'تجاوز حد التكرار: ' . $action, ['identifier' => $identifier], 'warning');
        }

        /**
         * تدوير ملف السجل
         */
        private function rotate_log_if_needed() {
            if (file_exists($this->log_file) && filesize($this->log_file) > $this->max_log_size) {
                $backup_file = $this->log_file . '.' . date('Y-m-d-His');
                rename($this->log_file, $backup_file);
                
                // الاحتفاظ بآخر 5 ملفات سجل فقط
                $dir = dirname($this->log_file);
                $pattern = basename($this->log_file) . '.*';
                $files = glob($dir . '/' . $pattern);
                if (count($files) > 5) {
                    sort($files);
                    unlink($files[0]);
                }
            }
        }
    }
}

/**
 * =============================================================================
 * تطبيق الإصلاحات الأمنية على الدوال الموجودة
 * =============================================================================
 */

// Hook لتسجيل محاولات الدخول الفاشلة
add_action('wp_login_failed', function($username) {
    $logger = SOD_Security_Logger::get_instance();
    $logger->log('login_failed', 'محاولة دخول فاشلة للمستخدم: ' . $username, ['username' => $username], 'warning');
});

// Hook لمراقبة المحاولات المشبوهة
add_action('init', function() {
    // مراقبة محاولات الحقن في المعاملات
    $suspicious_patterns = [
        '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER)\b)/i',
        '/(<script|javascript:|on\w+=)/i',
        '/(\.\.\/|\.\.\\\\)/',
    ];

    foreach ($_REQUEST as $key => $value) {
        if (is_string($value)) {
            foreach ($suspicious_patterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    $logger = SOD_Security_Logger::get_instance();
                    $logger->log_xss_attempt($value, "Request param: {$key}");
                    break;
                }
            }
        }
    }
}, 1);
