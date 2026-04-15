# اختبارات الوحدة لنظام Beiruttime OSINT

## نظرة عامة

تم إنشاء مجموعة اختبارات وحدة للتحقق من صحة الوظائف المتعلقة بـ:
- `page=strategic-osint-db` - صفحة قاعدة البيانات
- `page=strategic-osint-reindex` - صفحة إعادة التحليل الشامل

## هيكل الاختبارات

```
tests/
├── bootstrap.php              # ملف إعداد بيئة الاختبار
├── BaseTestCase.php           # فئة الاختبار الأساسية
└── unit/
    ├── StrategicOsintDbTest.php      # اختبارات صفحة قاعدة البيانات
    └── StrategicOsintReindexTest.php # اختبارات صفحة إعادة التحليل
```

## الجداول المستهدفة

| الجدول | الوصف | عدد السجلات المتوقع |
|--------|-------|---------------------|
| wp_0929ce48ae_so_news_events | الأحداث الاستخباراتية | 9,450 |
| wp_0929ce48ae_so_sent_alerts | التنبيهات المرسلة | 2,738 |
| wp_0929ce48ae_so_actor_memory | ذاكرة الفاعلين | 60,922 |
| wp_0929ce48ae_so_predictions | التنبؤات | 0 |
| wp_0929ce48ae_so_dict_actors | قاموس الفاعلين | 40 |
| wp_0929ce48ae_so_dict_weapons | قاموس الأسلحة | 40 |
| wp_0929ce48ae_so_manual_learning | قواعد التعلم | 24 |

## تشغيل الاختبارات

### تشغيل جميع الاختبارات
```bash
cd /workspace
composer test
# أو
./vendor/bin/phpunit
```

### تشغيل اختبارات محددة
```bash
# اختبارات قاعدة البيانات
./vendor/bin/phpunit --filter StrategicOsintDbTest

# اختبارات إعادة التحليل
./vendor/bin/phpunit --filter StrategicOsintReindexTest

# اختبار محدد
./vendor/bin/phpunit --filter testReanalyzeBatchSizeValidation
```

### تشغيل مع تغطية الكود
```bash
composer test:coverage
```

## الدوال المختبرة

### صفحة قاعدة البيانات (strategic-osint-db)
- `so_reanalyze_all_news_events_full()` - إعادة التحليل الكامل
- `ajax_reanalyze_batch()` - معالجة دفعة AJAX
- `ajax_duplicate_cleanup_batch()` - تنظيف المكرر
- `ajax_db_stats()` - إحصائيات قاعدة البيانات

### صفحة إعادة التحليل (strategic-osint-reindex)
- `so_reanalyze_all_news_events()` - إعادة تحليل الأحداث
- `ajax_reanalyze_reset()` - تصفير المؤشر
- `ajax_duplicate_cleanup_reset()` - تصفير تنظيف المكرر

## حالات الاختبار

### 1. التحقق من الهيكل
- التأكد من وجود الدوال المطلوبة
- التحقق من هيكل البيانات المعادة

### 2. التحقق من الصحة
- التحقق من أحجام الدفعات (10-2000)
- التحقق من حساب النسب المئوية
- التحقق من معالجة الجداول الفارغة

### 3. التحقق من المنطق
- منطق المعالجة المستمرة
- حفظ واستعادة المؤشر
- معالجة الأخطاء

## متطلبات التشغيل

- PHP 8.0+
- PHPUnit 9.0+
- ووردبريس (للاختبارات المتكاملة)

## ملاحظات هامة

1. **الاختبارات المعزولة**: بعض الاختبارات تعمل دون الحاجة لبيئة ووردبريس كاملة
2. **المحاكاة**: يتم استخدام محاكاة لـ wpdb للاختبارات المعزولة
3. **الاختبارات المتكاملة**: تتطلب بيئة ووردبريس كاملة للعمل

## إضافة اختبارات جديدة

لإضافة اختبار جديد:

1. أنشئ ملفاً جديداً في `tests/unit/` ينتهي بـ `Test.php`
2. وسّع الفئة `BaseTestCase`
3. أضف دوال الاختبار التي تبدأ بـ `test`

مثال:
```php
<?php
namespace Beiruttime\OSINT\Tests;

class MyNewTest extends BaseTestCase
{
    public function testSomething()
    {
        $this->assertTrue(true);
    }
}
```

## استكشاف الأخطاء

### خطأ: Class not found
```bash
composer dump-autoload
```

### خطأ: Bootstrap file not found
تأكد من وجود ملف `tests/bootstrap.php`

### خطأ: Functions not defined
بعض الدوال تتطلب تحميل الإضافة بالكامل. استخدم المحاكاة في هذه الحالة.

## الترخيص

GPL-2.0-or-later
