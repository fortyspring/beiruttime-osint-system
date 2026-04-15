<?php
/**
 * Bootstrap لاختبارات الوحدة
 * 
 * @package Beiruttime_OSINT_Pro
 */

// تحديد مسار الجذر
define('TEST_ROOT_DIR', dirname(__DIR__));
define('PLUGIN_ROOT_DIR', dirname(TEST_ROOT_DIR));

// تحميل الملحق التلقائي لـ Composer
if (file_exists(PLUGIN_ROOT_DIR . '/vendor/autoload.php')) {
    require_once PLUGIN_ROOT_DIR . '/vendor/autoload.php';
}

// تحميل ملف الاختبار الرئيسي
if (file_exists(TEST_ROOT_DIR . '/BaseTestCase.php')) {
    require_once TEST_ROOT_DIR . '/BaseTestCase.php';
}

/**
 * دالة مساعدة لتحميل ملفات الإضافة للاختبار
 */
function loadPluginFilesForTesting()
{
    $pluginFile = PLUGIN_ROOT_DIR . '/beiruttime-osint-pro.php';
    
    if (!file_exists($pluginFile)) {
        throw new RuntimeException("ملف الإضافة الرئيسي غير موجود: {$pluginFile}");
    }
    
    // ملاحظة: لا نقوم بتحميل الملف الكامل لتجنب تعارضات ووردبريس
    // بدلاً من ذلك، نحمل الدوال المطلوبة فقط عند الحاجة
}

/**
 * إنشاء محاكاة لـ wpdb
 */
function createMockWpdb($prefix = 'wp_0929ce48ae_')
{
    $mock = new stdClass();
    $mock->prefix = $prefix;
    
    return $mock;
}

/**
 * إعداد بيئة الاختبار
 */
function setupTestEnvironment()
{
    // تعريف الثوابت الأساسية إذا لم تكن معرفة
    if (!defined('ABSPATH')) {
        define('ABSPATH', PLUGIN_ROOT_DIR . '/');
    }
    
    // تحميل الدوال المساعدة من الإضافة
    $functionsFile = PLUGIN_ROOT_DIR . '/beiruttime-osint-pro.php';
    if (file_exists($functionsFile)) {
        // استخراج الدوال المطلوبة فقط
        // ملاحظة: هذا تبسيط - في الواقع قد نحتاج لطريقة أكثر تعقيداً
    }
}

// تشغيل الإعداد عند البدء
setupTestEnvironment();
