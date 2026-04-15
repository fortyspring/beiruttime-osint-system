# المرحلة 2: تحويل الوحدات الحالية إلى بنية معيارية

## ✅ اكتمل التحويل

تم بنجاح نقل جميع الوحدات (Modules) الحالية إلى البنية المعيارية الجديدة.

---

## 📦 الوحدات المحولة

### 1. **Dashboard Module** - لوحة التحكم الرئيسية
- **الموقع**: `/modules/dashboard/class-dashboard-module.php`
- **الوظائف**:
  - عرض إحصائيات OSINT السريعة
  - إدارة التنبيهات الأخيرة
  - رسوم بيانية للاتجاهات
  - خريطة جغرافية تفاعلية
  - تحليل الحرب المركبة
  - تخصيص التخطيط (Layout)
- **AJAX Endpoints**:
  - `osint_dashboard_get_data`
  - `osint_dashboard_refresh`
  - `osint_dashboard_save_layout`

### 2. **Map Module** - الخريطة الجغرافية
- **الموقع**: `/modules/map/class-map-module.php`
- **الوظائف**:
  - عرض الأحداث على الخريطة (Leaflet.js)
  - طبقة الحرارة (Heatmap)
  - تجميع العناقيد (Clustering)
  - فلترة حسب مستوى التهديد
  - فلترة جغرافية
- **AJAX Endpoints**:
  - `osint_map_get_events`
  - `osint_map_get_heatmap`
  - `osint_map_get_cluster`

### 3. **Chart Module** - الرسوم البيانية
- **الموقع**: `/modules/chart/class-chart-module.php`
- **الوظائف**:
  - اتجاهات الأحداث (Line Chart)
  - توزيع التهديدات (Doughnut Chart)
  - تحليل الجهات الفاعلة (Bar Chart)
  - طبقات الحرب المركبة (Radar Chart)
  - التوزيع الجغرافي (Pie Chart)
  - تصدير الرسوم (PNG/PDF)
- **AJAX Endpoints**:
  - `osint_chart_get_data`
  - `osint_chart_export`

### 4. **Analysis Module** - التحليل الاستخباراتي
- **الموقع**: `/modules/analysis/class-analysis-module.php`
- **الوظائف**:
  - اكتشاف الأنماط (Pattern Detection)
  - تحليل الاتجاهات (Trend Analysis)
  - التوقعات الاستخباراتية (Predictions)
  - توليد التقارير (Report Generation)
  - توصيات التصعيد
- **AJAX Endpoints**:
  - `osint_analysis_get_patterns`
  - `osint_analysis_get_trends`
  - `osint_analysis_get_predictions`
  - `osint_analysis_generate_report`

---

## 🏗️ البنية المعيارية

```
beiruttime-osint-pro/modules/
├── class-module-loader.php       # محمّل الوحدات الرئيسي
├── dashboard/
│   ├── class-dashboard-module.php
│   └── views/
│       ├── dashboard-page.php
│       ├── settings-page.php
│       └── widgets/
│           ├── quick-stats.php
│           └── recent-alerts.php
├── map/
│   ├── class-map-module.php
│   └── views/
│       ├── map-widget.php
│       └── map-page.php
├── chart/
│   ├── class-chart-module.php
│   └── views/
│       └── charts-page.php
└── analysis/
    ├── class-analysis-module.php
    └── views/
        └── analysis-page.php
```

---

## 🔧 الوراثة والقواعد الأساسية

### الواجهة الموحدة (`OSINT_Module_Interface`)
```php
interface OSINT_Module_Interface {
    public function get_id();
    public function get_name();
    public function get_version();
    public function is_active();
    public function init();
    public function handle_ajax($action, $data);
    public function get_config();
    public function render();
    public function deactivate();
}
```

### الفئة الأساسية (`OSINT_Base_Module`)
توفر وظائف مشتركة لجميع الوحدات:
- إدارة الإعدادات (`load_config`, `save_config`)
- التخزين المؤقت (`get_cached`, `clear_cache`)
- التسجيل (`log`)
- التحقق من طلبات AJAX (`validate_ajax_request`)
- معالجة AJAX افتراضية (`handle_ajax`)

---

## 🚀 التفعيل

### التفعيل التلقائي
يتم تحميل الوحدات تلقائياً عند تشغيل WordPress عبر:
```php
add_action('plugins_loaded', function() {
    if (class_exists('OSINT_Modular_Core')) {
        $core = OSINT_Modular_Core::instance();
        OSINT_Module_Loader::init($core);
    }
}, 20);
```

### الحصول على وحدة معينة
```php
$dashboard = OSINT_Module_Loader::get_module('dashboard');
$data = $dashboard->get_dashboard_data();
```

### الحصول على جميع الوحدات
```php
$modules = OSINT_Module_Loader::get_modules();
foreach ($modules as $id => $module) {
    if ($module->is_active()) {
        echo $module->get_name();
    }
}
```

---

## 🎯 Hooks و Filters المتاحة

### Actions
```php
// بعد تحميل جميع الوحدات
do_action('osint_modules_loaded', $modules);

// بعد تفعيل وحدة معينة
do_action('osint_module_initialized', $module_id, $module);

// عند إلغاء تفعيل وحدة
do_action('osint_module_deactivated', $module_id);
```

### Filters
```php
// تعديل إعدادات الوحدة
$config = apply_filters('osint_module_config', $config, $module_id);

// تعديل بيانات لوحة التحكم
$data = apply_filters('osint_dashboard_data', $data);
```

---

## 📊 الإحصائيات

| الوحدة | عدد الدوال | نقاط AJAX | طرق العرض |
|--------|-----------|-----------|-----------|
| Dashboard | 18 | 3 | 4 |
| Map | 12 | 3 | 2 |
| Chart | 14 | 2 | 1 |
| Analysis | 16 | 4 | 1 |
| **الإجمالي** | **60** | **12** | **8** |

---

## 🔐 الأمان

### Nonce Verification
جميع نقاط AJAX تستخدم WordPress Nonce للتحقق:
```php
check_ajax_referer('osint_dashboard_nonce', 'nonce');
check_ajax_referer('osint_map_nonce', 'nonce');
check_ajax_referer('osint_chart_nonce', 'nonce');
check_ajax_referer('osint_analysis_nonce', 'nonce');
```

### Sanitization
```php
sanitize_text_field($_POST['field']);
intval($_POST['number']);
json_decode(stripslashes($_POST['json']));
```

### Capability Checks
```php
'manage_options' // للوصول إلى صفحات الإدارة
```

---

## 🧪 اختبارات الوحدة (قادمة)

سيتم إنشاء ملفات اختبار لكل وحدة:
```
tests/unit/
├── DashboardModuleTest.php
├── MapModuleTest.php
├── ChartModuleTest.php
└── AnalysisModuleTest.php
```

---

## 📝 أمثلة الاستخدام

### مثال 1: الحصول على إحصائيات سريعة
```php
$dashboard = OSINT_Module_Loader::get_module('dashboard');
$stats = $dashboard->get_quick_stats();

echo "إجمالي الأحداث: " . $stats['total_events'];
echo "أحداث اليوم: " . $stats['today_events'];
echo "تنبيهات نشطة: " . $stats['active_alerts'];
```

### مثال 2: عرض خريطة الأحداث
```php
$map = OSINT_Module_Loader::get_module('map');
$events = $map->get_map_events(array(
    'threat_level' => 'high',
    'country' => 'سوريا',
));

foreach ($events as $event) {
    echo $event['title'] . ' - ' . $event['lat'] . ', ' . $event['lng'];
}
```

### مثال 3: تحليل الاتجاهات
```php
$analysis = OSINT_Module_Loader::get_module('analysis');
$trends = $analysis->analyze_trends(array('days' => 30));

if ($trends['event_trend']['direction'] === 'increasing') {
    echo "زيادة بنسبة " . $trends['event_trend']['percentage'] . "%";
}
```

### مثال 4: توليد تقرير
```php
$analysis = OSINT_Module_Loader::get_module('analysis');
$report = $analysis->generate_report(array(
    'days' => 30,
    'include_predictions' => true,
));

echo $report['summary'];
```

---

## ⚙️ التكوين

### تكوين كل وحدة
```php
// Dashboard
array(
    'enabled' => true,
    'cache_ttl' => 300,
    'refresh_interval' => 60,
    'show_widgets' => array('quick_stats', 'alerts', 'trends', 'map'),
)

// Map
array(
    'enabled' => true,
    'cache_ttl' => 300,
    'default_center' => array(33.8938, 35.5018),
    'default_zoom' => 7,
    'clustering_enabled' => true,
)

// Chart
array(
    'enabled' => true,
    'cache_ttl' => 600,
    'animation_enabled' => true,
    'responsive' => true,
)

// Analysis
array(
    'enabled' => true,
    'cache_ttl' => 1800,
    'auto_analysis' => true,
    'enable_predictions' => true,
)
```

---

## 🔄 التحديثات المستقبلية

### المرحلة 3 (قادمة):
- [ ] WebSocket Module للبث المباشر
- [ ] Notification Module للإشعارات
- [ ] Export Module لتصدير البيانات
- [ ] API Module لـ REST API
- [ ] User Preferences Module لتفضيلات المستخدم

### تحسينات مقترحة:
- [ ] إضافة اختبارات PHPUnit
- [ ] تحسين الأداء مع مجموعات البيانات الكبيرة
- [ ] دعم التصدير PDF للتقارير
- [ ] تكامل مع مصادر بيانات خارجية
- [ ] دعم متعدد اللغات كامل

---

## 📞 الدعم

للحصول على مساعدة أو الإبلاغ عن مشاكل:
1. راجع السجلات: `/wp-content/uploads/beiruttime-osint-logs/`
2. تحقق من وحدة التحميل: `OSINT_Module_Loader::get_modules()`
3. تأكد من تفعيل الوحدات: `$module->is_active()`

---

**الحالة**: ✅ مكتمل  
**الإصدار**: 2.0.0  
**التاريخ**: 2024
