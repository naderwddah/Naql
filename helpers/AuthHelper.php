<?php
/**
 * كلاس المساعدة في المصادقة
 * يدير التوكنات والجلسات والصلاحيات
 */

require_once __DIR__ . '/Response.php';

class AuthHelper {
    
    /**
     * التحقق من صحة التوكن
     * @param PDO $pdo اتصال قاعدة البيانات
     * @param string $token التوكن المرسل
     * @return array بيانات المستخدم
     */
    public static function checkToken(PDO $pdo, $token) {
        // التحقق من وجود توكن
        if (empty($token)) {
            Response::error("Unauthorized - No token provided", 401);
        }

        // البحث عن التوكن في قاعدة البيانات
        $stmt = $pdo->prepare("
            SELECT s.user_id, u.role_id, u.username, u.is_active 
            FROM sessions s 
            JOIN users u ON s.user_id = u.user_id
            WHERE s.token = :token AND s.expires_at > NOW()
        ");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        // التوكن غير صالح أو منتهي
        if (!$user) {
            Response::error("Unauthorized - Invalid or expired token", 401);
        }

        // المستخدم غير نشط
        if (!$user['is_active']) {
            Response::error("Account is disabled", 403);
        }

        return $user;
    }

    /**
     * توليد توكن جديد
     * @param PDO $pdo اتصال قاعدة البيانات
     * @param string $userId معرف المستخدم
     * @return string التوكن الجديد
     */
    public static function generateToken(PDO $pdo, $userId) {
        // حذف التوكنات المنتهية الصلاحية
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE expires_at < NOW()");
        $stmt->execute();

        // حذف التوكنات القديمة للمستخدم (جلسة واحدة فقط)
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);

        // إنشاء توكن عشوائي آمن
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + TOKEN_EXPIRY);

        // حفظ التوكن في قاعدة البيانات
        $stmt = $pdo->prepare("
            INSERT INTO sessions (token, user_id, expires_at) 
            VALUES (:token, :user_id, :expires_at)
        ");
        $stmt->execute([
            'token' => $token,
            'user_id' => $userId,
            'expires_at' => $expires
        ]);

        return $token;
    }

    /**
     * إنهاء الجلسة (تسجيل خروج)
     * @param PDO $pdo اتصال قاعدة البيانات
     * @param string $token التوكن المراد حذفه
     */
    public static function logout(PDO $pdo, $token) {
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE token = :token");
        $stmt->execute(['token' => $token]);
    }

    /**
     * التحقق من الصلاحية
     * @param int $userRoleId دور المستخدم
     * @param array|int $allowedRoles الأدوار المسموحة
     * @return bool هل لديه صلاحية؟
     */
    public static function checkPermission($userRoleId, $allowedRoles) {
        if (is_array($allowedRoles)) {
            return in_array($userRoleId, $allowedRoles);
        }
        return $userRoleId <= $allowedRoles;
    }
}