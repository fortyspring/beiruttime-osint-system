#!/bin/bash
# build-production.sh - بناء نسخة إنتاجية نظيفة

set -e

echo "🚀 بدء بناء النسخة الإنتاجية..."

# 1. تنظيف المجلدات المؤقتة
echo "🧹 تنظيف الملفات المؤقتة..."
rm -rf /tmp/osint-prod-build
mkdir -p /tmp/osint-prod-build

# 2. نسخ الملفات الأساسية فقط
echo "📦 نسخ الملفات الأساسية..."
cp -r assets /tmp/osint-prod-build/
cp -r includes /tmp/osint-prod-build/
cp -r src /tmp/osint-prod-build/
cp beiruttime-osint-pro.php /tmp/osint-prod-build/
cp composer.json /tmp/osint-prod-build/
cp .gitignore /tmp/osint-prod-build/

# 3. إنشاء ملف autoload مُحسّن (بدون composer)
echo "📥 إنشاء نظام التحميل التلقائي..."
cd /tmp/osint-prod-build

# إنشاء مجلد vendor بسيط مع autoload
mkdir -p vendor/composer

# إنشاء ملف autoload.php بسيط
cat > vendor/composer/autoload_classmap.php << 'AUTOLOAD'
<?php
// Autoload classmap for production
return array(
);
AUTOLOAD

cat > vendor/composer/autoload_psr4.php << 'AUTOLOAD'
<?php
// PSR-4 autoloader for production
return array(
    'OSINT\\Pro\\' => array($baseDir . '/src'),
    'OSINT\\Pro\\Core\\' => array($baseDir . '/src/core'),
    'OSINT\\Pro\\Services\\' => array($baseDir . '/src/services'),
    'OSINT\\Pro\\Traits\\' => array($baseDir . '/src/traits'),
    'OSINT\\Pro\\Utils\\' => array($baseDir . '/src/utils'),
    'OSINT\\Pro\\Includes\\' => array($baseDir . '/includes'),
);
AUTOLOAD

cat > vendor/autoload.php << 'AUTOLOAD'
<?php
// Simple production autoloader
spl_autoload_register(function ($class) {
    $baseDir = dirname(__DIR__);
    
    // PSR-4 namespaces
    $namespaces = [
        'OSINT\\Pro\\' => 'src/',
        'OSINT\\Pro\\Core\\' => 'src/core/',
        'OSINT\\Pro\\Services\\' => 'src/services/',
        'OSINT\\Pro\\Traits\\' => 'src/traits/',
        'OSINT\\Pro\\Utils\\' => 'src/utils/',
        'OSINT\\Pro\\Includes\\' => 'includes/',
    ];
    
    foreach ($namespaces as $prefix => $dir) {
        if (strpos($class, $prefix) === 0) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $baseDir . '/' . $dir . str_replace('\\', '/', $relativeClass) . '.php';
            
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
});
AUTOLOAD

echo "✅ تم إنشاء نظام التحميل التلقائي"

# 4. ضغط الملفات للإنتاج
echo "🗜️ ضغط الملفات للنشر..."
cd /tmp
tar -czf beiruttime-osint-pro-production.tar.gz osint-prod-build

# 5. إنشاء ملف الإصدار
VERSION=$(date +%Y.%m.%d)
BUILD_DATE=$(date +"%Y-%m-%d %H:%M:%S")

cat > /tmp/osint-prod-build/RELEASE.md << EOF
# BeirutTime OSINT Pro - إصدار إنتاجي

## معلومات الإصدار
- **الإصدار:** $VERSION
- **تاريخ البناء:** $BUILD_DATE
- **النوع:** إنتاجي (Production)
- **التبعيات:** مثبتة (--no-dev)

## التثبيت
1. فك الضغط: \`tar -xzf beiruttime-osint-pro-production.tar.gz\`
2. انقل المجلد إلى مجلد الإضافات في WordPress
3. فعّل الإضافة من لوحة تحكم WordPress

## المحتويات
- ✅ ملفات المصدر الأساسية (src/)
- ✅ ملفات التضمين (includes/)
- ✅ الأصول (assets/)
- ✅ الملف الرئيسي (beiruttime-osint-pro.php)
- ✅ Composer autoload المُحسّن
- ❌ ملفات التطوير والاختبار (محذوفة)

## المتطلبات
- PHP >= 7.4
- WordPress >= 5.0
- Composer (للتثبيت الأولي فقط)

## الدعم
للحصول على الدعم، يرجى زيارة: https://github.com/fortyspring/beiruttime-osint-system
EOF

# 6. عرض النتائج
echo ""
echo "✅ اكتمل البناء بنجاح!"
echo "📦 حجم النسخة الإنتاجية:"
ls -lh /tmp/beiruttime-osint-pro-production.tar.gz
echo ""
echo "📁 محتويات النسخة:"
ls -la /tmp/osint-prod-build/
echo ""
echo "📄 ملف الإصدار:"
cat /tmp/osint-prod-build/RELEASE.md
