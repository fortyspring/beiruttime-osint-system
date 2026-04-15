# 🚀 دليل النشر الإنتاجي

## ✅ تم إكمال المهام

### 1. ربط مستودع Git البعيد
- **المستودع:** https://github.com/fortyspring/beiruttime-osint-system
- **الفرع:** main
- **الحالة:** متصل وجاهز

### 2. بناء النسخة الإنتاجية
- **الأداة:** `build-production.sh`
- **حجم النسخة:** 326KB (مضغوطة)
- **المحتوى:** ملفات نظيفة بدون أدوات التطوير

## 📦 كيفية بناء نسخة إنتاجية

### الطريقة التلقائية (موصى بها)
```bash
./build-production.sh
```

### مخرجات البناء
- `/tmp/beiruttime-osint-pro-production.tar.gz` - الملف المضغوط
- `/tmp/osint-prod-build/` - مجلد النسخة غير المضغوط
- `/tmp/osint-prod-build/RELEASE.md` - معلومات الإصدار

## 🔧 محتوى النسخة الإنتاجية

### الملفات المتضمنة:
- ✅ `src/` - ملفات المصدر الأساسية
- ✅ `includes/` - ملفات التضمين
- ✅ `assets/` - الأصول (CSS, JS, Images)
- ✅ `beiruttime-osint-pro.php` - الملف الرئيسي
- ✅ `vendor/autoload.php` - نظام التحميل التلقائي
- ✅ `composer.json` - ملف التبعيات

### الملفات المستبعدة:
- ❌ ملفات الاختبار (tests/)
- ❌ ملفات التطوير (dev dependencies)
- ❌ ملفات التوثيق الزائدة
- ❌ مجلدات build/scripts

## 📥 التثبيت على WordPress

1. **فك الضغط:**
   ```bash
   tar -xzf beiruttime-osint-pro-production.tar.gz
   ```

2. **النقل إلى WordPress:**
   ```bash
   cp -r osint-prod-build /path/to/wordpress/wp-content/plugins/beiruttime-osint-pro
   ```

3. **التفعيل:**
   - اذهب إلى لوحة تحكم WordPress
   - الإضافات → الإضافات المثبتة
   - فعّل "BeirutTime OSINT Pro"

## 🔄 سير العمل الموصى به

### للتطوير:
```bash
# العمل على الفرع المحلي
git checkout -b feature/new-feature
# تطوير الميزة
git commit -m "feat: إضافة ميزة جديدة"
git push origin feature/new-feature
# إنشاء Pull Request على GitHub
```

### للإنتاج:
```bash
# التأكد من أن كل شيء محدث
git pull origin main

# بناء النسخة الإنتاجية
./build-production.sh

# رفع النسخة إلى الخادم
scp /tmp/beiruttime-osint-pro-production.tar.gz user@server:/tmp/

# تثبيت على الخادم
ssh user@server
cd /tmp
tar -xzf beiruttime-osint-pro-production.tar.gz
cp -r osint-prod-build /var/www/html/wp-content/plugins/beiruttime-osint-pro
```

## 📊 الإحصائيات

| المعيار | القيمة |
|---------|--------|
| حجم المصدر | 2.8MB |
| حجم النسخة المضغوطة | 326KB |
| نسبة الضغط | 88% |
| عدد الملفات | 72 |
| وقت البناء | < 5 ثواني |

## 🛡️ الأمان

النسخة الإنتاجية تتضمن:
- ✅ التحقق من الصلاحيات
- ✅ تنظيف المدخلات
- ✅ حماية CSRF
- ✅ تشفير البيانات الحساسة
- ✅ سجلات التدقيق

## 📞 الدعم

للحصول على المساعدة:
- **GitHub Issues:** https://github.com/fortyspring/beiruttime-osint-system/issues
- **البريد الإلكتروني:** info@beiruttime.com

---

**آخر تحديث:** 2026-04-15  
**الإصدار:** 2026.04.15
