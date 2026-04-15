# 🚀 دليل البناء والنشر - Beiruttime OSINT Pro

## 📦 النسخة الجاهزة للاستخدام الفوري

تم إعداد نظام بناء آلي يُنتج نسخة كاملة من الإضافة تحتوي على **جميع المكتبات المطلوبة**، مما يسمح للمستخدم بتثبيتها مباشرة على WordPress دون الحاجة لأي أوامر إضافية.

---

## ✨ المميزات

- ✅ **تثبيت فوري**: رفع الملف ZIP وتفعيل الإضافة فقط
- ✅ **لا حاجة لـ Composer**: جميع المكتبات مدمجة في النسخة
- ✅ **لا حاجة لأوامر إضافية**: كل شيء جاهز للعمل
- ✅ **حجم محسّن**: 440 كيلوبايت فقط (بدلاً من 10.4 ميغابايت)
- ✅ **كود نظيف**: بدون ملفات التطوير والاختبارات

---

## 🛠️ للمطورين: كيفية بناء النسخة

### المتطلبات المسبقة
```bash
# تثبيت الأدوات المطلوبة
apt-get install -y zip unzip
composer install  # مرة واحدة فقط للتطوير
```

### خطوات البناء

#### الطريقة 1: استخدام السكريبت الآلي (موصى به)
```bash
# تشغيل سكريبت البناء
./build.sh
```

#### الطريقة 2: يدوياً
```bash
# 1. تنظيف مجلد البناء
rm -rf build
mkdir -p build/beiruttime-osint-pro

# 2. نسخ الملفات الأساسية
cp -r beiruttime-osint-pro.php README.txt README.md CHANGELOG.md includes src assets build/beiruttime-osint-pro/

# 3. تثبيت مكتبات الإنتاج فقط
cd build/beiruttime-osint-pro
composer install --no-dev --optimize-autoloader --classmap-authoritative
rm -f composer.json composer.lock
cd ../..

# 4. ضغط النسخة النهائية
cd build
zip -rq beiruttime-osint-pro.zip beiruttime-osint-pro
cd ..
mv build/beiruttime-osint-pro.zip ./
```

### الناتج النهائي
```
✅ beiruttime-osint-pro.zip (440K)
   ├── beiruttime-osint-pro.php (الملف الرئيسي)
   ├── includes/ (ملفات النظام)
   ├── src/ (الكود الأساسي)
   ├── assets/ (الملفات الثابتة)
   └── vendor/ (المكتبات المطلوبة فقط)
```

---

## 📥 للمستخدمين النهائيين: التثبيت

### الطريقة 1: من لوحة تحكم WordPress
1. اذهب إلى **إضافات → أضف جديد → رفع إضافة**
2. اختر ملف `beiruttime-osint-pro.zip`
3. اضغط **التثبيت الآن**
4. اضغط **تفعيل**

### الطريقة 2: يدوياً عبر FTP/cPanel
1. فك ضغط الملف `beiruttime-osint-pro.zip`
2. ارفع المجلد `beiruttime-osint-pro` إلى `/wp-content/plugins/`
3. اذهب إلى لوحة تحكم WordPress → إضافات
4. فعّل الإضافة

---

## 🔄 سير عمل النشر للإصدارات الجديدة

### عند إصدار نسخة جديدة:

```bash
# 1. تحديث رقم الإصدار في beiruttime-osint-pro.php
# Version: V.Beta 112

# 2. تنفيذ الاختبارات (اختياري ولكن موصى به)
composer test

# 3. بناء النسخة الجديدة
./build.sh

# 4. التحقق من الملف الناتج
unzip -l beiruttime-osint-pro.zip | head -20

# 5. رفع النسخة إلى GitHub Releases أو توزيعها
git add beiruttime-osint-pro.zip
git commit -m "release: إصدار النسخة V.Beta 112"
git push origin main
```

---

## 📊 مقارنة الحجم

| النوع | الحجم | الملاحظات |
|-------|-------|-----------|
| المستودع الكامل (مع vendor) | 70 MB | يحتوي على جميع تبعيات التطوير |
| المستودع على GitHub | ~1 MB | بدون vendor/node_modules |
| **النسخة الجاهزة (ZIP)** | **440 KB** | تحتوي على مكتبات الإنتاج فقط ✅ |

---

## ⚙️ ما الذي يتم استبعاده من النسخة النهائية؟

### ملفات التطوير (لا تُضمّن)
- ❌ `tests/` - اختبارات الوحدة
- ❌ `docs/` - الوثائق
- ❌ `.github/` - ملفات CI/CD
- ❌ `bin/` - سكريبتات التطوير
- ❌ `phpunit.xml` - تكوين الاختبارات
- ❌ `composer.json` و `composer.lock` (في النسخة النهائية)

### تبعيات التطوير (لا تُثبّت)
- ❌ PHPUnit
- ❌ PHPStan
- ❌ PHPCS
- ❌ Infection
- ❌ Yoast Polyfills

### ما يتم تضمينه
- ✅ جميع مكتبات الإنتاج المطلوبة للتشغيل
- ✅ الكود الأساسي (includes, src)
- ✅ الملفات الثابتة (assets)
- ✅ ملفات التعريف (README, CHANGELOG)

---

## 🎯 ضمان الجودة

### قبل كل إصدار:
```bash
# تشغيل جميع الاختبارات
composer test

# تحليل الكود
composer analyze

# فحص معايير WordPress
composer lint

# بناء النسخة
./build.sh

# اختبار التثبيت على موقع تجريبي
```

---

## 📝 ملاحظات مهمة

1. **مجلد build**: يُحذف تلقائياً عند كل بناء جديد
2. **vendor/**: يُعاد إنشاؤه في كل مرة لضمان حداثة المكتبات
3. **اللغات**: إذا أضفت ملفات ترجمة، تأكد من وجود مجلد `languages/`
4. **الإصدار**: يتم استخراج رقم الإصدار تلقائياً من تعليق الملف الرئيسي

---

## 🔧 حل المشاكل

### المشكلة: الخطأ "zip: command not found"
```bash
apt-get install -y zip unzip
```

### المشكلة: خطأ في تثبيت Composer
```bash
composer install --no-interaction
./build.sh
```

### المشكلة: حجم الملف كبير جداً
- تأكد من أن `--no-dev` مُفعّل في سكريبت البناء
- تحقق من عدم وجود ملفات كبيرة في `assets/`

---

## 📞 الدعم

للأسئلة أو المشاكل، يرجى فتح issue على GitHub أو التواصل عبر Telegram: @osint_lb

---

**آخر تحديث**: أبريل 2026  
**الإصدار الحالي**: V.Beta 111
