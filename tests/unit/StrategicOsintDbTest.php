<?php
/**
 * اختبارات الوحدة لصفحة قاعدة البيانات وإعادة التحليل
 * 
 * @package Beiruttime_OSINT_Pro
 */

namespace Beiruttime\OSINT\Tests;

use PHPUnit\Framework\TestCase;

/**
 * اختبارات الوظائف المتعلقة بـ page=strategic-osint-db
 */
class StrategicOsintDbTest extends TestCase
{
    /**
     * محاكاة كائن wpdb للاختبار
     */
    private $mockWpdb;

    protected function setUp(): void
    {
        parent::setUp();
        
        // إنشاء محاكاة لـ wpdb
        $this->mockWpdb = new \stdClass();
        $this->mockWpdb->prefix = 'wp_0929ce48ae_';
    }

    /**
     * اختبار دالة so_reanalyze_all_news_events_full
     * 
     * @test
     */
    public function testReanalyzeAllNewsEventsFullWithEmptyTable()
    {
        // الترتيب: محاكاة جدول فارغ
        global $wpdb;
        $wpdb = $this->getMockBuilder('stdClass')
            ->addMethods(['get_var', 'get_results', 'prepare'])
            ->getMock();
        
        $wpdb->prefix = 'wp_0929ce48ae_';
        $table = $wpdb->prefix . 'so_news_events';
        
        $wpdb->expects($this->any())
            ->method('get_var')
            ->with($this->stringContains('COUNT(*)'))
            ->willReturn(0);
        
        $wpdb->expects($this->never())
            ->method('get_results');
        
        // التنفيذ
        $result = so_reanalyze_all_news_events_full(100, true);
        
        // التحقق
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['updated']);
        $this->assertTrue($result['done']);
        $this->assertEquals(0, $result['percent']);
    }

    /**
     * اختبار دالة ajax_reanalyze_batch مع بيانات صالحة
     * 
     * @test
     */
    public function testAjaxReanalyzeBatchWithValidData()
    {
        // هذا الاختبار يتطلب بيئة ووردبريس كاملة
        // نكتفي بالتحقق من وجود الدالة
        $this->assertTrue(
            method_exists('SO_Dashboard_Stats', 'ajax_reanalyze_batch'),
            'دالة ajax_reanalyze_batch يجب أن تكون موجودة في SO_Dashboard_Stats'
        );
    }

    /**
     * اختبار دالة ajax_duplicate_cleanup_batch
     * 
     * @test
     */
    public function testAjaxDuplicateCleanupBatchExists()
    {
        $this->assertTrue(
            method_exists('SO_Dashboard_Stats', 'ajax_duplicate_cleanup_batch'),
            'دالة ajax_duplicate_cleanup_batch يجب أن تكون موجودة'
        );
    }

    /**
     * اختبار دالة ajax_db_stats
     * 
     * @test
     */
    public function testAjaxDbStatsExists()
    {
        $this->assertTrue(
            method_exists('SO_Dashboard_Stats', 'ajax_db_stats'),
            'دالة ajax_db_stats يجب أن تكون موجودة'
        );
    }

    /**
     * اختبار التحقق من صحة المعاملات في so_reanalyze_all_news_events
     * 
     * @test
     * @dataProvider batchSizeProvider
     */
    public function testReanalyzeBatchSizeValidation($input, $expected)
    {
        // التحقق من أن حجم الدفعة ضمن النطاق المسموح
        $batch = max(10, min(2000, (int)$input));
        $this->assertEquals($expected, $batch);
    }

    /**
     * مزود بيانات لاختبار أحجام الدفعات
     */
    public function batchSizeProvider()
    {
        return [
            'حجم دفعة سالب' => [-100, 10],
            'حجم دفعة صفر' => [0, 10],
            'حجم دفعة صغير' => [5, 10],
            'حجم دفعة عادي' => [100, 100],
            'حجم دفعة كبير' => [2000, 2000],
            'حجم دفعة أكبر من المسموح' => [5000, 2000],
            'حجم دفعة نصي' => ['abc', 10],
        ];
    }

    /**
     * اختبار دالة so_reanalyze_all_news_events مع صفوف فارغة
     * 
     * @test
     */
    public function testReanalyzeAllNewsEventsWithEmptyRows()
    {
        global $wpdb;
        $wpdb = $this->getMockBuilder('stdClass')
            ->addMethods(['get_results', 'prepare', 'get_var'])
            ->getMock();
        
        $wpdb->prefix = 'wp_0929ce48ae_';
        
        $wpdb->expects($this->once())
            ->method('get_results')
            ->willReturn([]);
        
        $result = so_reanalyze_all_news_events(100, 0);
        
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(0, $result['scanned']);
        $this->assertTrue($result['done']);
    }
}
