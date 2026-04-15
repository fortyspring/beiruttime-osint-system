#!/bin/bash

# ============================================
# Beiruttime OSINT Pro - Build Script
# يُنتج نسخة جاهزة للاستخدام الفوري
# ============================================

set -e  # إيقاف السكريبت عند حدوث أي خطأ

# الألوان للطباعة
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# المتغيرات
PLUGIN_SLUG="beiruttime-osint-pro"
BUILD_DIR="build"
DIST_FILE="${PLUGIN_SLUG}.zip"
VERSION=$(grep -oP "Version: \K[^\s]+" beiruttime-osint-pro.php || echo "unknown")

echo -e "${BLUE}============================================${NC}"
echo -e "${BLUE}🚀 Beiruttime OSINT Pro - Build System${NC}"
echo -e "${BLUE}============================================${NC}"
echo ""
echo -e "${YELLOW}📦 إصدار النسخة: ${VERSION}${NC}"
echo ""

# الخطوة 1: تنظيف مجلد البناء
echo -e "${YELLOW}🧹 تنظيف مجلد البناء...${NC}"
rm -rf ${BUILD_DIR}
mkdir -p ${BUILD_DIR}/${PLUGIN_SLUG}

# الخطوة 2: نسخ الملفات الأساسية
echo -e "${GREEN}📋 نسخ الملفات الأساسية...${NC}"

# قائمة الملفات والمجلدات المطلوبة
FILES_TO_COPY=(
    "beiruttime-osint-pro.php"
    "osint-hybrid-warfare-update.php"
    "osint-threat-radar.php"
    "README.txt"
    "README.md"
    "CHANGELOG.md"
    "includes"
    "src"
    "assets"
    "languages"
)

for item in "${FILES_TO_COPY[@]}"; do
    if [ -e "$item" ]; then
        cp -r "$item" "${BUILD_DIR}/${PLUGIN_SLUG}/"
        echo -e "  ✅ ${item}"
    else
        echo -e "  ⚠️  ${item} (غير موجود)"
    fi
done

# الخطوة 3: تثبيت تبعيات الإنتاج فقط (بدون dev dependencies)
echo -e "${YELLOW}📦 تثبيت مكتبات Composer للإنتاج...${NC}"
cd ${BUILD_DIR}/${PLUGIN_SLUG}

# نسخ composer.json وتعديله للإنتاج فقط
cp ../../composer.json .
# إزالة require-dev من composer.json للإنتاج
php -r "
\$json = json_decode(file_get_contents('composer.json'), true);
unset(\$json['require-dev']);
unset(\$json['scripts']);
file_put_contents('composer.json', json_encode(\$json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
"

# تثبيت التبعيات بدون تطوير
composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction --no-progress --quiet

# حذف ملفات composer غير الضرورية للإصدار النهائي
rm -f composer.json composer.lock

cd ../..

echo -e "  ✅ تم تثبيت المكتبات المطلوبة"

# الخطوة 4: إنشاء ملف ZIP النهائي
echo -e "${YELLOW}📦 ضغط الإضافة...${NC}"
cd ${BUILD_DIR}
zip -rq ${DIST_FILE} ${PLUGIN_SLUG}
cd ..

# نقل الملف إلى الجذر
mv ${BUILD_DIR}/${DIST_FILE} ./

# الخطوة 5: عرض الإحصائيات
FILE_SIZE=$(du -h ${DIST_FILE} | cut -f1)
FILE_COUNT=$(unzip -l ${DIST_FILE} | tail -n 1 | awk '{print $2}')

echo ""
echo -e "${GREEN}✅ اكتمل البناء بنجاح!${NC}"
echo ""
echo -e "${BLUE}============================================${NC}"
echo -e "${BLUE}📊 إحصائيات النسخة:${NC}"
echo -e "${BLUE}============================================${NC}"
echo -e "  📦 اسم الملف: ${DIST_FILE}"
echo -e "  📏 الحجم: ${FILE_SIZE}"
echo -e "  📄 عدد الملفات: ${FILE_COUNT}"
echo -e "  🏷️  الإصدار: ${VERSION}"
echo -e "${BLUE}============================================${NC}"
echo ""
echo -e "${GREEN}✨ النسخة جاهزة للتثبيت المباشر على WordPress!${NC}"
echo -e "${YELLOW}📍 موقع الملف: $(pwd)/${DIST_FILE}${NC}"
echo ""
echo -e "${YELLOW}💡 ملاحظات:${NC}"
echo -e "   • جميع المكتبات مطلوبة ومدمجة في النسخة"
echo -e "   • لا يحتاج المستخدم لتشغيل أي أوامر إضافية"
echo -e "   • احذف مجلد build بعد التأكد من نجاح النسخة"
echo ""
