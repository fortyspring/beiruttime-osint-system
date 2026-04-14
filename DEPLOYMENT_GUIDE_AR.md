# 🚀 دليل النشر الآمن - Beiruttime OSINT Pro

## الإصدار: 2.0.0 (مع الإصلاحات الأمنية)
## تاريخ التحديث: 2024

---

## 📋 قائمة التحقق قبل النشر

### 1. التحضير المسبق
- [ ] أخذ نسخة احتياطية كاملة من قاعدة البيانات
- [ ] أخذ نسخة احتياطية من ملفات الموقع الحالية
- [ ] التأكد من وجود مساحة كافية على السيرفر
- [ ] التحقق من توافق إصدار PHP (7.4 أو أحدث)
- [ ] التحقق من توافق إصدار WordPress (5.8 أو أحدث)

### 2. الملفات المطلوبة للنشر
تأكد من وجود جميع الملفات التالية في حزمة النشر:

```
beiruttime-osint-pro/
├── beiruttime-osint-pro.php              (الملف الرئيسي - محدّث)
├── includes/
│   ├── security/
│   │   └── class-security-fixes.php     (جديد - ملف الأمان)
│   ├── cache/
│   │   └── class-cache-handler.php
│   ├── class-db-updater.php
│   ├── class-entity-relations-manager.php
│   ├── class-hybrid-warfare-integrator.php
│   ├── class-modular-core.php
│   ├── class-osint-migrator.php
│   ├── classifier-service.php
│   └── newslog-service.php
├── assets/
│   ├── css/
│   └── js/
├── scripts/
│   ├── migration-encrypt-data.php       (مؤقت - للحذف بعد الاستخدام)
│   └── security-unit-tests.php          (اختياري - للاختبار)
└── README.md
```

---

## 🔧 خطوات النشر التفصيلية

### الطريقة 1: النشر اليدوي (عبر FTP/cPanel)

#### الخطوة 1: رفع الملفات
1. اتصل بالسيرفر عبر FTP أو File Manager
2. انتقل إلى المسار: `/wp-content/plugins/`
3. احذف المجلد القديم `beiruttime-osint-pro` (بعد أخذ نسخة احتياطية)
4. ارفع المجلد الجديد بكامل محتوياته

#### الخطوة 2: تشغيل الترحيل
1. انسخ الملف `scripts/migration-encrypt-data.php` إلى المجلد الرئيسي للإضافة
2. افتح المتصفح واذهب إلى: `https://your-site.com/wp-content/plugins/beiruttime-osint-pro/migration-encrypt-data.php`
3. اضغط على "بدء عملية الترحيل"
4. انتظر حتى تكتمل العملية
5. **احذف الملف فوراً** بعد الانتهاء

#### الخطوة 3: اختبار النظام
1. سجل الدخول كمسؤول
2. اذهب إلى لوحة تحكم الإضافة
3. اختبر الوظائف التالية:
   - [ ] رفع ملفات JSON/CSV
   - [ ] حفظ الإعدادات
   - [ ] عرض التقارير
   - [ ] الاتصال بـ APIs

#### الخطوة 4: تفعيل ترويسات الأمان (اختياري - حسب نوع السيرفر)

**لخادم Apache:**
أضف التالي إلى ملف `.htaccess`:
```apache
<IfModule mod_headers.c>
    Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https:;"
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Permissions-Policy "geolocation=(), microphone=(), camera=()"
</IfModule>
```

**لخادم Nginx:**
أضف التالي إلى ملف التكوين:
```nginx
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https:;" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
```

---

### الطريقة 2: النشر عبر WP-CLI (للمستخدمين المتقدمين)

```bash
# الانتقال إلى مجلد ووردبريس
cd /path/to/wordpress

# أخذ نسخة احتياطية
wp db export backup-before-security-update.sql
wp plugin deactivate beiruttime-osint-pro

# حذف النسخة القديمة
rm -rf wp-content/plugins/beiruttime-osint-pro

# رفع النسخة الجديدة (عبر unzip)
unzip beiruttime-osint-pro.zip -d wp-content/plugins/

# تفعيل الإضافة
wp plugin activate beiruttime-osint-pro

# تشغيل الاختبارات الأمنية
wp eval-file wp-content/plugins/beiruttime-osint-pro/scripts/security-unit-tests.php
```

---

## 🔐 ما بعد النشر

### 1. التحقق من السجلات الأمنية
- راقب ملف `logs/security.log` يومياً للأسبوع الأول
- ابحث عن أي محاولات وصول غير مصرح بها
- تحقق من نجاح عمليات التشفير

### 2. اختبار Rate Limiting
- حاول تنفيذ 60 طلب AJAX في دقيقة واحدة
- تأكد من ظهور رسالة "Rate limit exceeded"
- تحقق من تسجيل المحاولات في السجلات

### 3. تحديث التوثيق
- حدّث ملف CHANGELOG.md بالإصلاحات المطبقة
- أبلغ المستخدمين بالتغييرات الأمنية
- قدم دليلاً للمستخدمين حول أي تغييرات في الواجهة

---

## ⚠️ استكشاف الأخطاء وإصلاحها

### مشكلة: فشل رفع الملفات
**الحل:**
1. تحقق من صلاحيات المجلد: `chmod 755 wp-content/uploads`
2. تأكد من أن `upload_max_filesize` في php.ini كافٍ
3. تحقق من `post_max_size` في php.ini

### مشكلة: خطأ في التشفير
**الحل:**
1. تأكد من تعريف AUTH_KEY و AUTH_SALT في wp-config.php
2. شغل السكريبت مرة أخرى بعد إصلاح المفاتيح
3. راجع ملف `debug.log` للتفاصيل

### مشكلة: Rate Limiting يمنع الوصول الشرعي
**الحل:**
1. عدّل الحدود في `class-security-fixes.php`:
   ```php
   $this->limits = array(
       'ajax' => 100,  // زيادة من 60 إلى 100
       'file_upload' => 20,  // زيادة من 10 إلى 20
   );
   ```
2. امحِ ذاكرة التخزين المؤقت لـ Rate Limiter

---

## 📞 الدعم الفني

في حالة وجود مشاكل:
1. راجع ملف `logs/security.log`
2. فعّل وضع التصحيح في ووردبريس
3. تحقق من `debug.log`
4. تواصل مع فريق الدعم مع إرفاق السجلات

---

## ✅ قائمة التحقق النهائية

قبل إعلان اكتمال النشر:

- [ ] تم رفع جميع الملفات بنجاح
- [ ] تم تشغيل سكريبت الترحيل وحذفه
- [ ] تعمل جميع الوظائف الأساسية
- [ ] تظهر ترويسات الأمان في الاستجابة HTTP
- [ ] يعمل Rate Limiting بشكل صحيح
- [ ] يتم تسجيل الأحداث الأمنية
- [ ] تم أخذ نسخة احتياطية ناجحة
- [ ] تم إبلاغ المستخدمين بالتحديث

---

**🎉 مبروك! تم النشر بنجاح مع تعزيز الحماية الأمنية.**
