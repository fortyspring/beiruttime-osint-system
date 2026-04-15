<?php
/**
 * فئة الاختبار الأساسية
 * 
 * @package Beiruttime_OSINT_Pro
 */

namespace Beiruttime\OSINT\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * فئة الاختبار الأساسية مع وظائف مشتركة
 */
abstract class BaseTestCase extends BaseTestCase
{
    /**
     * إعداد ما قبل كل اختبار
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // إعداد البيئة المشتركة
        $this->setupCommonMocks();
    }

    /**
     * إعداد المحاكاة المشتركة
     */
    protected function setupCommonMocks()
    {
        // يمكن إضافة محاكاة مشتركة هنا
    }

    /**
     * إنشاء محاكاة لـ wpdb
     * 
     * @param string $prefix بادئة الجداول
     * @return \stdClass محاكاة wpdb
     */
    protected function createWpdbMock($prefix = 'wp_0929ce48ae_')
    {
        $mock = new \stdClass();
        $mock->prefix = $prefix;
        
        return $mock;
    }

    /**
     * التحقق من وجود دالة
     * 
     * @param string $functionName اسم الدالة
     * @return bool
     */
    protected function functionExists($functionName)
    {
        return function_exists($functionName);
    }

    /**
     * التحقق من وجود طريقة في كلاس
     * 
     * @param string $className اسم الكلاس
     * @param string $methodName اسم الطريقة
     * @return bool
     */
    protected function methodExists($className, $methodName)
    {
        return method_exists($className, $methodName);
    }
}
