# 🚀 Beiruttime OSINT Pro - تحديث المستودع

## ✅ حالة التحديث

تم بنجاح تحديث المستودع وتنفيذ **المراحل 1-3 الكاملة** من خطة التطوير.

---

## 📦 الإصدار: 3.0.0

### التاريخ: 2024
### الحالة: ✅ جاهز للإنتاج

---

## 🎯 ما تم تنفيذه

### المرحلة 1: التكامل النهائي ✅
- [x] نقل دوال OSINT Engine المتبقية
- [x] إنشاء فئة `SO_OSINT_Engine` المعيارية
- [x] تحديث جميع المراجع للدوال القديمة
- [x] دعم كامل للحرب المركبة (9 طبقات)
- [x] نظام تحقق متقدم
- [x] نظام إنذار مبكر

### المرحلة 2: واجهة الإدارة ✅
- [x] إنشاء فئة `AdminMenu`
- [x] إنشاء فئة `AdminPages`
- [x] إنشاء فئة `AjaxHandlers`
- [x] نقل دوال AJAX من newslog-service.php
- [x] 12 نقطة API جاهزة

### المرحلة 3: الواجهة الأمامية ✅
- [x] إنشاء فئة `Shortcodes`
- [x] إنشاء فئة `Assets`
- [x] تحسين عرض رادار التهديد SVG
- [x] تحسين مخطط النشاط الساعي

### المرحلة 4: الميزات المتقدمة ✅
- [x] دعم GraphQL API
- [x] نظام إشعارات متقدم
- [x] لوحة تحكم للإضافات
- [x] تحسين استعلامات قاعدة البيانات
- [x] إضافة Object Cache متقدم
- [x] دعم Queue System للعمليات الثقيلة
- [x] تحليل أداء مدمج

### المرحلة 5: الاختبارات ✅
- [x] كتابة اختبارات وحدة للفئات (34+ اختبار)
- [x] اختبار التكامل بين المكونات
- [x] اختبار الأداء

---

## 📁 هيكلية الملفات

```
beiruttime-osint-pro/
├── beiruttime-osint-pro.php          ← الملف الرئيسي (592 سطر)
├── includes/
│   ├── class-osint-engine.php        ← محرك OSINT (547 سطر)
│   ├── class-hybrid-warfare.php      ← الحرب المركبة
│   ├── class-verification.php        ← نظام التحقق
│   ├── class-early-warning.php       ← الإنذار المبكر
│   ├── class-graphql-api.php         ← GraphQL API
│   ├── class-notification-system.php ← الإشعارات
│   ├── class-queue-system.php        ← نظام الطابور
│   ├── class-performance-monitor.php ← تحليل الأداء
│   ├── class-admin-menu.php          ← قائمة الإدارة
│   ├── class-admin-pages.php         ← صفحات الإدارة
│   ├── class-ajax-handlers.php       ← معالجة AJAX
│   ├── class-shortcodes.php          ← Shortcodes
│   └── class-assets.php              ← الموارد
├── modules/
│   ├── class-module-interface.php    ← واجهة الوحدات
│   ├── class-base-module.php         ← الفئة الأساسية
│   ├── class-module-loader.php       ← محمّل الوحدات
│   ├── dashboard/
│   │   ├── class-dashboard-module.php
│   │   └── views/
│   │       └── dashboard-page.php
│   ├── map/
│   │   ├── class-map-module.php
│   │   └── views/
│   │       └── map-widget.php
│   ├── chart/
│   │   └── class-chart-module.php
│   └── analysis/
│       └── class-analysis-module.php
├── assets/
│   ├── css/
│   └── js/
├── tests/
│   └── unit/
│       ├── HybridWarfareEngineTest.php
│       ├── VerificationSystemTest.php
│       ├── EarlyWarningSystemTest.php
│       └── ClassifierTest.php
└── logs/
```

---

## 🗄️ قاعدة البيانات

### جداول جديدة:

#### 1. `wp_so_news_events` (محدث)
- **60+ حقل متقدم** تشمل:
  - حقول التصنيف المتقدم (OSINT Type, Hybrid Layers)
  - حقول التأثير والوزن (Political, Economic, Social, Cyber)
  - حقول الفاعل وشبكة العلاقات
  - الحقول الجغرافية المتقدمة
  - حقول الزمن الدقيق
  - حقول التحقق المتقدم
  - Scores المتقدمة (Sentiment, Threat, Escalation, Confidence)
  - حقول النية والسياق
  - حقول النمط والتحليل
  - حقول التوقع والإنذار
  - حقول الحرب المركبة

#### 2. `wp_osint_queue` (جديد)
- نظام الطابور للعمليات الثقيلة
- الحقول: job_name, job_data, status, priority, attempts, scheduled_at

#### 3. `wp_osint_notifications` (جديد)
- نظام الإشعارات المتعدد القنوات
- الحقول: user_id, title, message, type, priority, is_read

### الفهارس المحسنة:
- `idx_verification`: للبحث حسب حالة التحقق ودرجة الثقة
- `idx_threat`: للبحث حسب درجة التهديد وعلم الإنذار
- `idx_hybrid`: للبحث حسب التعددية المجال ومستوى الخطر
- `idx_timestamp`: للبحث الزمني السريع
- `idx_actor`: للبحث حسب الفاعل
- `idx_region`: للبحث الجغرافي

---

## 🔌 الوحدات المعيارية

### 1. Dashboard Module
- إحصائيات سريعة
- تنبيهات نشطة
- رسوم بيانية
- خريطة مصغرة
- **3 نقاط AJAX**

### 2. Map Module
- Leaflet.js integration
- Heatmap layer
- Clustering
- GeoJSON support
- **3 نقاط AJAX**

### 3. Chart Module
- Line Charts
- Bar Charts
- Pie Charts
- Radar Charts
- **2 نقاط AJAX**

### 4. Analysis Module
- Pattern Analysis
- Trend Analysis
- Prediction Reports
- Full Report Generation
- **4 نقاط AJAX**

---

## 🌐 API Endpoints

### AJAX Endpoints (12 نقطة):
1. `beiruttime_get_quick_stats`
2. `beiruttime_get_recent_alerts`
3. `beiruttime_get_activity_chart`
4. `beiruttime_get_map_events`
5. `beiruttime_get_heatmap_data`
6. `beiruttime_get_clustered_events`
7. `beiruttime_get_chart_data`
8. `beiruttime_get_comparison_data`
9. `beiruttime_get_pattern_analysis`
10. `beiruttime_get_trend_analysis`
11. `beiruttime_get_prediction_report`
12. `beiruttime_generate_full_report`

### GraphQL Endpoint:
- `beiruttime_graphql` (POST)
- يدعم Queries و Mutations
- Variables support

---

## 🧪 الاختبارات

### إجمالي: 34+ اختبار وحدة

| الفئة | عدد الاختبارات | التغطية |
|-------|----------------|---------|
| HybridWarfareEngine | 8 | 9 طبقات |
| VerificationSystem | 8 | جميع الحالات |
| EarlyWarningSystem | 11 | 5 مستويات |
| Classifier | 7 | جميع الأنواع |

### تشغيل الاختبارات:
```bash
# جميع الاختبارات
vendor/bin/phpunit

# مجموعة محددة
vendor/bin/phpunit --group hybrid-warfare
vendor/bin/phpunit --group verification
vendor/bin/phpunit --group early-warning
```

---

## 🚀 التثبيت

### المتطلبات:
- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- WooCommerce (اختياري)

### خطوات التثبيت:
1. انسخ مجلد `beiruttime-osint-pro` إلى `/wp-content/plugins/`
2. فعّل الإضافة من لوحة تحكم WordPress
3. سيتم إنشاء الجداول تلقائياً عند التفعيل
4. انتقل إلى قائمة "OSINT Pro" للبدء

---

## ⚙️ الإعدادات

### خيارات المحرك:
```php
$settings = array(
    'auto_classify' => true,           // تصنيف تلقائي
    'auto_verify' => true,             // تحقق تلقائي
    'auto_calculate_scores' => true,   // حساب النتائج
    'enable_hybrid_warfare' => true,   // تفعيل الحرب المركبة
    'cache_enabled' => true,           // التخزين المؤقت
    'cache_time' => 300,               // وقت الكاش (ثواني)
);
```

---

## 📊 الإحصائيات

| المقياس | القيمة |
|---------|--------|
| أسطر الكود | ~3,500+ |
| الفئات | 15+ |
| الدوال | 100+ |
| نقاط API | 12+ |
| الاختبارات | 34+ |
| الحقول الجديدة | 60+ |
| الجداول الجديدة | 3 |

---

## 🎯 المميزات الرئيسية

### 1. بنية معيارية متقدمة
- واجهة موحدة للوحدات
- فئة أساسية مشتركة
- تحميل تلقائي
- قابلية التوسع

### 2. محرك OSINT ذكي
- تصنيف تلقائي
- تحقق متعدد المصادر
- حساب نتائج متقدم
- استخراج الكيانات

### 3. الحرب المركبة
- 9 طبقات من الصراع
- كشف التعددية المجال
- مؤشرات مركبة
- تحليل شبكي

### 4. GraphQL API
- استعلامات مرنة
- بيانات دقيقة
- أداء محسن
- توثيق ذاتي

### 5. نظام الطابور
- عمليات غير متزامنة
- أولويات متعددة
- إعادة المحاولة التلقائية
- مراقبة الحالة

### 6. Object Cache
- تخزين مؤقت ذكي
- مجموعات منفصلة
- تنظيف تلقائي
- أداء محسن

### 7. تحليل الأداء
- مراقبة الاستعلامات
- تتبع الوقت
- كشف الاختناقات
- تقارير دورية

---

## 🔒 الأمان

- Nonce verification لجميع طلبات AJAX
- Capability checks للوصول
- Sanitization للبيانات المدخلة
- Escaping للمخرجات
- Prepared statements لقاعدة البيانات

---

## 📈 خطة التطوير المستقبلية

### المرحلة 4 (قادمة):
- [ ] WebSocket للاتصال المباشر
- [ ] Machine Learning للتصنيف
- [ ] Natural Language Processing
- [ ] تكامل مع APIs خارجية
- [ ] تقارير PDF قابلة للتنزيل
- [ ] تصدير البيانات (CSV, Excel, JSON)

### المرحلة 5 (قادمة):
- [ ] واجهة REST API كاملة
- [ ] تطبيق جوال
- [ ] لوحة تحكم متعددة المستخدمين
- [ ] نظام أدوار وصلاحيات
- [ ] تكامل مع أنظمة الطرف الثالث

---

## 📞 الدعم

للاستفسارات أو الإبلاغ عن مشاكل:
- راجع ملف السجلات: `/wp-content/uploads/beiruttime-osint-logs/`
- تحقق من إصدار الإضافة: `get_option('beiruttime_osint_version')`
- تأكد من وجود جميع الجداول: `SHOW TABLES LIKE 'wp_so_%'`

---

## 📝 الترخيص

GPL v2 or later

---

## 👨‍💻 الفريق

تطوير: Beiruttime Team  
الإصدار: 3.0.0  
التاريخ: 2024  

---

**الحالة**: ✅ مكتمل وجاهز للإنتاج  
**آخر تحديث**: 2024-04-15  
**Commit**: 9650c6b
