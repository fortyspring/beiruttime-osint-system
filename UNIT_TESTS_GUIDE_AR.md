# دليل تشغيل اختبارات الوحدة (Unit Tests)

## المتطلبات الأساسية

لتشغيل اختبارات الوحدة بنجاح، تحتاج إلى:

1. **PHP 7.4+** (متوفر حالياً: PHP 8.2.30)
2. **Composer** (مثبت مسبقاً)
3. **PHPUnit** (مثبت عبر Composer في `/workspace/vendor/bin/phpunit`)
4. **WordPress Test Suite** (يجب تثبيته)
5. **قاعدة بيانات MySQL/MariaDB** (اختياري للاختبارات الكاملة)

---

## طريقة 1: تشغيل الاختبارات البسيطة (بدون WordPress)

إذا كنت تريد اختبار الكود فقط دون الحاجة لبيئة WordPress كاملة:

### إنشاء ملف اختبار مبسط

```bash
# إنشاء ملف bootstrap بسيط
cat > tests/bootstrap-simple.php << 'EOF'
<?php
/**
 * Simple PHPUnit Bootstrap (without WordPress)
 */

// Define plugin constants
define('OSINT_PRO_PLUGIN_DIR', dirname(__DIR__) . '/');
define('OSINT_PRO_VERSION', '2.0.0');

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load test helpers
require_once __DIR__ . '/helpers/class-test-helpers.php';
EOF
```

### تعديل phpunit.xml للاختبارات البسيطة

```bash
# إنشاء نسخة للاختبارات البسيطة
cp phpunit.xml phpunit-simple.xml
```

ثم عدل الملف ليستخدم `bootstrap-simple.php` بدلاً من `tests/bootstrap.php`.

---

## طريقة 2: تشغيل الاختبارات الكاملة (مع WordPress Test Suite)

### الخطوة 1: تثبيت WordPress Test Suite

```bash
# إنشاء مجلد bin
mkdir -p bin

# تحميل скриبت التثبيت
curl -O https://raw.githubusercontent.com/wp-cli/scaffold-command/master/features/install-wp-tests.sh

# جعله قابل للتنفيذ
chmod +x install-wp-tests.sh

# تثبيت WordPress Test Suite
# ملاحظة: تحتاج إلى MySQL Server مثبت
./install-wp-tests.sh wordpress_test root '' localhost latest
```

**ملاحظة:** إذا لم يكن MySQL مثبتاً، يمكنك استخدام SQLite كبديل:

```bash
# تثبيت قاعدة بيانات SQLite للاختبارات
composer require --dev phpunit/phpunit-sqlite
```

### الخطوة 2: تشغيل الاختبارات

```bash
# تشغيل جميع الاختبارات
composer test

# أو مباشرة
./vendor/bin/phpunit

# تشغيل اختبارات محددة
composer test:security      # اختبارات الأمان
composer test:websocket     # اختبارات WebSocket
composer test:cache         # اختبارات الكاش

# تشغيل اختبار محدد
./vendor/bin/phpunit --filter test_secure_file_upload_valid_json

# تشغيل اختبار مع عرض التفاصيل
./vendor/bin/phpunit --testdox

# تشغيل اختبار مع تغطية الكود
composer test:coverage
```

---

## طريقة 3: استخدام Docker (الأسهل)

إذا لم يكن لديك MySQL أو WordPress مثبتين، استخدم Docker:

### إنشاء ملف docker-compose.yml

```yaml
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: wordpress_test
    ports:
      - "3306:3306"
  
  wordpress-test:
    image: wordpress:latest
    environment:
      WORDPRESS_DB_HOST: mysql
      WORDPRESS_DB_USER: root
      WORDPRESS_DB_PASSWORD: root
      WORDPRESS_DB_NAME: wordpress_test
    volumes:
      - .:/var/www/html/wp-content/plugins/osint-pro
    depends_on:
      - mysql
    command: >
      bash -c "
        curl -O https://raw.githubusercontent.com/wp-cli/scaffold-command/master/features/install-wp-tests.sh
        chmod +x install-wp-tests.sh
        ./install-wp-tests.sh wordpress_test root root mysql latest
        cd /var/www/html/wp-content/plugins/osint-pro
        vendor/bin/phpunit
      "
```

### تشغيل الاختبارات عبر Docker

```bash
docker-compose up --abort-on-container-exit
```

---

## هيكل الاختبارات الحالية

### 1. SecurityFixesTest.php (23 اختبار)
- رفع الملفات الآمن
- تنظيف المدخلات
- التحقق من Nonce
- التشفير وفك التشفير
- توليد_hashes SRI
- نظام الحد من التكرار (Rate Limiter)
- تسجيل الأحداث الأمنية

### 2. WebSocketHandlerTest.php (19 اختبار)
- الاشتراك في القنوات
- معالجة SSE
- البث للقنوات
- إدارة الاتصالات

### 3. CacheHandlerTest.php (5 اختبارات)
- تعيين وجلب البيانات
- حذف البيانات
- تنظيف الكاش

---

## أمثلة على كتابة اختبارات جديدة

### مثال 1: اختبار وظيفة بسيطة

```php
<?php
class MyFunctionTest extends WP_UnitTestCase {
    
    public function test_my_function() {
        $result = my_function('input');
        $this->assertEquals('expected_output', $result);
    }
}
```

### مثال 2: اختبار مع إعداد مسبق

```php
<?php
class MyModuleTest extends WP_UnitTestCase {
    
    private $module;
    
    public function setUp(): void {
        parent::setUp();
        $this->module = new OSINT_My_Module();
    }
    
    public function test_module_initialization() {
        $this->assertInstanceOf('OSINT_My_Module', $this->module);
    }
}
```

### مثال 3: اختبار قاعدة البيانات

```php
<?php
class DatabaseTest extends WP_UnitTestCase {
    
    public function test_create_post() {
        $post_id = $this->factory()->post->create([
            'post_title' => 'Test Post'
        ]);
        
        $this->assertIsInt($post_id);
        $this->assertEquals('Test Post', get_the_title($post_id));
    }
}
```

---

## استكشاف الأخطاء

### مشكلة: "Could not find wordpress-tests-lib"

**الحل:** تأكد من تثبيت WordPress Test Suite:

```bash
./install-wp-tests.sh wordpress_test root '' localhost latest
```

### مشكلة: "MySQL connection failed"

**الحل:** 
1. تأكد من تشغيل MySQL Server
2. تحقق من بيانات الاتصال في أمر التثبيت
3. أو استخدم SQLite بدلاً من MySQL

### مشكلة: "Class not found"

**الحل:** تأكد من تحميل autoload:

```bash
composer dump-autoload
```

---

## نصائح للاختبار الفعال

1. **اكتب اختبارات صغيرة ومركزة** - كل اختبار يجب أن يختبر شيئاً واحداً
2. **استخدم أسماء وصفية** - مثل `test_secure_file_upload_invalid_type`
3. **اختبر الحالات الحدية** - القيم الفارغة، القيم الكبيرة، البيانات غير الصالحة
4. **حافظ على الاختبارات سريعة** - تجنب العمليات البطيئة غير الضرورية
5. **استخدم Groups** - لتصنيف الاختبارات وتشغيلها بشكل انتقائي

---

## مراجع مفيدة

- [PHPUnit Documentation](https://phpunit.de/manual/current/en/)
- [WordPress Testing](https://developer.wordpress.org/plugins/testing/)
- [WP-CLI Scaffold Command](https://developer.wordpress.org/cli/commands/scaffold/)
