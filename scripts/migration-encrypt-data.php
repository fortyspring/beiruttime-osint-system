<?php
/**
 * سكريبت ترحيل لتشفير البيانات الحساسة المخزنة
 * 
 * هذا السكريبت يقوم بقراءة البيانات غير المشفرة من قاعدة البيانات
 * وتشفيرها باستخدام دوال التشفير الجديدة.
 * 
 * طريقة الاستخدام:
 * 1. قم بنسخ هذا الملف إلى مجلد wp-content/plugins/beiruttime-osint-pro/
 * 2. قم بتشغيله مرة واحدة عبر المتصفح أو WP-CLI
 * 3. احذف الملف بعد الانتهاء للأمان
 * 
 * @package Beiruttime_OSINT_Pro
 */

// منع الوصول المباشر
if (!defined('ABSPATH')) {
    // محاولة تحميل ووردبريس
    $wp_load_path = '';
    if (file_exists(dirname(__FILE__) . '/../../../../wp-load.php')) {
        $wp_load_path = dirname(__FILE__) . '/../../../../wp-load.php';
    } elseif (file_exists(dirname(__FILE__) . '/../../../wp-load.php')) {
        $wp_load_path = dirname(__FILE__) . '/../../../wp-load.php';
    }
    
    if ($wp_load_path) {
        require_once $wp_load_path;
    } else {
        die('هذا السكريبت يجب تشغيله ضمن بيئة ووردبريس');
    }
}

// التحقق من صلاحيات المسؤول
if (!current_user_can('manage_options')) {
    die('ليس لديك صلاحيات كافية لتشغيل هذا السكريبت');
}

// تضمين ملف الإصلاحات الأمنية
if (file_exists(__DIR__ . '/includes/security/class-security-fixes.php')) {
    require_once __DIR__ . '/includes/security/class-security-fixes.php';
}

/**
 * كلاس ترحيل التشفير
 */
class SOD_Encryption_Migrator {
    
    /**
     * قائمة الخيارات التي تحتوي على بيانات حساسة
     */
    private $sensitive_options = array(
        'beiruttime_api_keys',
        'beiruttime_social_credentials',
        'beiruttime_email_credentials',
        'beiruttime_sms_credentials',
        'beiruttime_database_credentials',
    );
    
    /**
     * جدول السجلات
     */
    private $log_table = '';
    
    /**
     * البناء
     */
    public function __construct() {
        global $wpdb;
        $this->log_table = $wpdb->prefix . 'sod_security_log';
    }
    
    /**
     * تشغيل عملية الترحيل
     */
    public function run_migration() {
        $results = array(
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => array(),
        );
        
        echo "<h2>بدء عملية ترحيل التشفير</h2>";
        echo "<p>جاري معالجة البيانات الحساسة...</p>";
        
        foreach ($this->sensitive_options as $option_name) {
            $result = $this->migrate_option($option_name);
            $results[$result['status']]++;
            $results['details'][] = $result;
            
            echo "<div style='padding: 10px; margin: 5px 0; border: 1px solid #ccc; background: " . 
                 ($result['status'] === 'success' ? '#d4edda' : ($result['status'] === 'skipped' ? '#fff3cd' : '#f8d7da')) . 
                 "'>";
            echo "<strong>{$option_name}:</strong> " . $result['message'];
            echo "</div>";
        }
        
        echo "<h3>ملخص العملية:</h3>";
        echo "<ul>";
        echo "<li>✅ نجح: {$results['success']}</li>";
        echo "<li>⏭️ تم تخطيه: {$results['skipped']}</li>";
        echo "<li>❌ فشل: {$results['failed']}</li>";
        echo "</ul>";
        
        // تسجيل النتيجة
        $this->log_migration_result($results);
        
        return $results;
    }
    
    /**
     * ترحيل خيار معين
     */
    private function migrate_option($option_name) {
        $option_value = get_option($option_name);
        
        if ($option_value === false) {
            return array(
                'status' => 'skipped',
                'message' => 'الخيار غير موجود',
            );
        }
        
        // التحقق مما إذا كانت البيانات مشفرة بالفعل
        if ($this->is_already_encrypted($option_value)) {
            return array(
                'status' => 'skipped',
                'message' => 'البيانات مشفرة بالفعل',
            );
        }
        
        // محاولة التشفير
        if (function_exists('sod_encrypt_sensitive_data')) {
            $encrypted_value = sod_encrypt_sensitive_data($option_value);
            
            if ($encrypted_value !== false) {
                update_option($option_name, $encrypted_value);
                
                // التحقق من نجاح التحديث
                $new_value = get_option($option_name);
                if ($this->is_already_encrypted($new_value)) {
                    return array(
                        'status' => 'success',
                        'message' => 'تم التشفير بنجاح',
                    );
                } else {
                    return array(
                        'status' => 'failed',
                        'message' => 'فشل التحقق من التشفير',
                    );
                }
            } else {
                return array(
                    'status' => 'failed',
                    'message' => 'فشل عملية التشفير',
                );
            }
        } else {
            return array(
                'status' => 'failed',
                'message' => 'دالة التشفير غير متوفرة',
            );
        }
    }
    
    /**
     * التحقق مما إذا كانت البيانات مشفرة بالفعل
     */
    private function is_already_encrypted($data) {
        if (is_string($data)) {
            // التحقق من أن البيانات تبدو مشفرة (تبدأ ببادئة محددة أو تنسيق معين)
            // هذا تحقق بسيط، يمكن تحسينه حسب خوارزمية التشفير
            return (strpos($data, 'sod_enc:') === 0 || strlen($data) > 100);
        }
        
        if (is_array($data)) {
            // التحقق من أن جميع القيم في المصفوفة مشفرة
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    if (!$this->is_already_encrypted($value)) {
                        return false;
                    }
                } elseif (is_string($value) && !$this->is_already_encrypted($value)) {
                    return false;
                }
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * تسجيل نتيجة الترحيل
     */
    private function log_migration_result($results) {
        global $wpdb;
        
        $log_entry = array(
            'event_type' => 'encryption_migration',
            'event_data' => json_encode($results),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql'),
        );
        
        $wpdb->insert($this->log_table, $log_entry);
    }
    
    /**
     * ترحيل الجداول المخصصة
     */
    public function migrate_custom_tables() {
        global $wpdb;
        
        echo "<h2>ترحيل الجداول المخصصة</h2>";
        
        $tables_to_migrate = array(
            $wpdb->prefix . 'sod_entities' => array('api_key', 'secret_key'),
            $wpdb->prefix . 'sod_sources' => array('api_key', 'password'),
            $wpdb->prefix . 'sod_campaigns' => array('credentials'),
        );
        
        $total_updated = 0;
        
        foreach ($tables_to_migrate as $table => $columns) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                echo "<div style='padding: 10px; margin: 5px 0; background: #fff3cd;'>";
                echo "الجدول $table غير موجود - تم التخطي";
                echo "</div>";
                continue;
            }
            
            foreach ($columns as $column) {
                // جلب الصفوف التي تحتوي على بيانات غير مشفرة
                $rows = $wpdb->get_results("SELECT ID, $column FROM $table WHERE $column IS NOT NULL AND $column != ''");
                
                foreach ($rows as $row) {
                    if (!empty($row->$column) && !$this->is_already_encrypted($row->$column)) {
                        if (function_exists('sod_encrypt_sensitive_data')) {
                            $encrypted = sod_encrypt_sensitive_data($row->$column);
                            if ($encrypted) {
                                $wpdb->update($table, array($column => $encrypted), array('ID' => $row->ID));
                                $total_updated++;
                            }
                        }
                    }
                }
            }
            
            echo "<div style='padding: 10px; margin: 5px 0; background: #d4edda;'>";
            echo "تم معالجة الجدول $table";
            echo "</div>";
        }
        
        echo "<p>إجمالي التحديثات: $total_updated</p>";
        
        return $total_updated;
    }
}

// تشغيل السكريبت إذا تم طلبه
if (isset($_GET['run_migration']) && $_GET['run_migration'] === '1') {
    $migrator = new SOD_Encryption_Migrator();
    $migrator->run_migration();
    $migrator->migrate_custom_tables();
    
    echo "<hr>";
    echo "<h3>✅ اكتملت عملية الترحيل!</h3>";
    echo "<p><strong>مهم:</strong> يرجى حذف هذا الملف الآن للأمان.</p>";
    echo "<p>يمكنك العودة إلى <a href='" . admin_url('admin.php?page=beiruttime-osint-pro') . "'>لوحة التحكم</a></p>";
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>ترحيل التشفير - Beiruttime OSINT Pro</title>
        <style>
            body { font-family: Arial, sans-serif; direction: rtl; text-align: right; padding: 20px; }
            .container { max-width: 800px; margin: 0 auto; }
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
            .warning { 
                background: #fff3cd; 
                border: 1px solid #ffc107; 
                padding: 15px; 
                margin: 20px 0; 
                border-radius: 3px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔐 ترحيل تشفير البيانات الحساسة</h1>
            
            <div class="warning">
                <strong>⚠️ تحذير:</strong>
                <ul>
                    <li>هذا السكريبت يجب تشغيله <strong>مرة واحدة فقط</strong></li>
                    <li>يجب <strong>حذف الملف</strong> بعد الانتهاء للأمان</li>
                    <li>يوصى بأخذ <strong>نسخة احتياطية</strong> من قاعدة البيانات قبل التشغيل</li>
                </ul>
            </div>
            
            <p>سيقوم هذا السكريبت بتشفير جميع البيانات الحساسة المخزنة في قاعدة البيانات باستخدام خوارزميات التشفير الجديدة.</p>
            
            <h3>البيانات التي سيتم تشفيرها:</h3>
            <ul>
                <li>مفاتيح API</li>
                <li>بيانات اعتماد وسائل التواصل الاجتماعي</li>
                <li>بيانات اعتماد البريد الإلكتروني</li>
                <li>بيانات اعتماد خدمات SMS</li>
                <li>بيانات اعتماد قواعد البيانات</li>
            </ul>
            
            <a href="?run_migration=1" class="btn" onclick="return confirm('هل أنت متأكد من رغبتك في تشغيل عملية الترحيل؟ تأكد من أخذ نسخة احتياطية أولاً!')">
                🚀 بدء عملية الترحيل
            </a>
            
            <hr>
            <p><a href="<?php echo admin_url('admin.php?page=beiruttime-osint-pro'); ?>">← العودة إلى لوحة التحكم</a></p>
        </div>
    </body>
    </html>
    <?php
}
