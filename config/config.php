<?php
/**
 * ملف إعدادات النظام
 * يحتوي على ثوابت الاتصال بقاعدة البيانات والإعدادات العامة
 */

// ============================================
// 1. إعدادات قاعدة البيانات
// ============================================
define('DB_HOST', 'localhost');        // host
define('DB_NAME', 'driver_card');     // اسم قاعدة البيانات
define('DB_USER', 'root');          // اسم المستخدم
define('DB_PASS', '');          // كلمة المرور

// ============================================
// 2. إعدادات التوكن
// ============================================
define('TOKEN_EXPIRY', 86400);         // 24 ساعة بالثواني

// ============================================
// 3. الاتصال بقاعدة البيانات
// ============================================
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // استثناءات للأخطاء
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // مصفوفة ترابطية
            PDO::ATTR_EMULATE_PREPARES => false              // حماية من SQL injection
        ]
    );
} catch (PDOException $e) {
    // تسجيل الخطأ في ملف السجلات
    error_log($e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    
    // إرجاع خطأ 500 للمستخدم
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ============================================
// 4. دوال مساعدة عامة
// ============================================

/**
 * توليد UUID v4
 * @return string UUID format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
 */
if (!function_exists('generate_uuid')) {
    function generate_uuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

/**
 * تنظيف المدخلات النصية
 * @param string $input النص المراد تنظيفه
 * @return string النص بعد التنظيف
 */
if (!function_exists('sanitize_input')) {
    function sanitize_input($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}