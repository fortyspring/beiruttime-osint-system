# دليل التشغيل السريع - Beiruttime OSINT Pro

## 🚀 خطوات ما بعد التحديث

تم تطبيق جميع الإصلاحات الأمنية بنجاح. اتبع الخطوات التالية لتفعيلها في بيئة الإنتاج:

### 1️⃣ التحقق من المتطلبات
تأكد من توفر:
- PHP 7.4 أو أعلى
- WordPress 5.8 أو أعلى
- صلاحيات كتابة لمجلد `wp-content/uploads/sod-logs/`

### 2️⃣ تشغيل سكريبت ترحيل البيانات (لتشفير المفاتيح القديمة)

**الخيار أ: باستخدام WP-CLI (موصى به)**
```bash
cd /path/to/wordpress
wp eval-file wp-content/plugins/beiruttime-osint-pro/scripts/migration-encrypt-data.php
```

**الخيار ب: عبر المتصفح**
1. انقل الملف `scripts/migration-encrypt-data.php` مؤقتاً إلى مجلد الجذر العام (public_html) بحذر.
2. افتح الرابط: `https://your-site.com/migration-encrypt-data.php`
3. **احذف الملف فوراً** بعد الانتهاء لأسباب أمنية.

**الخيار ج: تشغيل يدوي (للمطورين)**
قم بتضمين الملف في `functions.php` مؤقتاً ثم قم بإزالته:
```php
// أضف هذا السطر مؤقتاً في functions.php
require_once ABSPATH . 'wp-content/plugins/beiruttime-osint-pro/scripts/migration-encrypt-data.php';
// قم بتحديث الصفحة مرة واحدة ثم احذف السطر
```

### 3️⃣ تشغيل اختبارات الأمان

**باستخدام WP-CLI:**
```bash
wp eval-file wp-content/plugins/beiruttime-osint-pro/scripts/security-unit-tests.php
```

**عبر المتصفح (لأغراض التطوير فقط):**
يمكنك إنشاء صفحة اختبار مؤقتة تستدعي دوال الاختبار من `class-security-fixes.php`.

### 4️⃣ التحقق من السجلات
بعد التشغيل، تحقق من مجلد السجلات:
```bash
ls -la wp-content/uploads/sod-logs/
cat wp-content/uploads/sod-logs/security.log
```

### 5️⃣ تفعيل الترويسات الأمنية (اختياري - مستوى الخادم)

**لـ Apache (.htaccess):**
```apache
<IfModule mod_headers.c>
    Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'self';"
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

**لـ Nginx:**
```nginx
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'self';";
add_header X-Content-Type-Options "nosniff";
add_header X-Frame-Options "SAMEORIGIN";
add_header X-XSS-Protection "1; mode=block";
add_header Referrer-Policy "strict-origin-when-cross-origin";
```

### 6️⃣ قائمة التحقق النهائية

- [ ] تم تشغيل سكريبت التشفير بنجاح
- [ ] تم التحقق من عدم وجود أخطاء في `debug.log`
- [ ] تم اختبار رفع الملفات (JSON/CSV)
- [ ] تم اختبار نقاط النهاية AJAX والتأكد من عمل Rate Limiting
- [ ] تم التحقق من وجود ملفات السجل
- [ ] تم تطبيق ترويسات الأمان (على مستوى الخادم أو الإضافة)

---

## 🆘 استكشاف الأخطاء

**المشكلة:** فشل رفع الملفات
**الحل:** تحقق من صلاحيات المجلد `wp-content/uploads/` وتأكد من أنها `755` أو `775`.

**المشكلة:** لا تظهر سجلات الأمان
**الحل:** تأكد من أن المجلد `wp-content/uploads/sod-logs/` موجود وقابل للكتابة.

**المشكلة:** خطأ في التشفير
**الحل:** تأكد من وجود الثوابت `AUTH_KEY` و `AUTH_SALT` في ملف `wp-config.php`.

---

## 📞 الدعم

للحصول على مساعدة إضافية، راجع الملفات التالية:
- `SECURITY_SUMMARY_AR.md`: ملخص شامل لجميع التحسينات
- `DEPLOYMENT_GUIDE_AR.md`: دليل النشر التفصيلي
- `SECURITY_FIXES_IMPLEMENTATION_AR.md`: تفاصيل التطبيق التقني

**تاريخ التحديث:** 2024
**الإصدار:** 2.0.0 (آمن)
