<?php
require_once __DIR__ . '/Response.php';

class AuthHelper {

    /**
     * التحقق من صحة التوكن والكوكيز
     * @param PDO $pdo اتصال قاعدة البيانات
     * @param string|null $token التوكن (إذا لم يرسل، سيتم قراءته من الكوكي)
     * @param string|null $idSession معرف الجلسة (الكوكي الجديد)
     * @return array بيانات المستخدم
     */
    public static function checkToken(PDO $pdo, $token = null, $idSession = null) {
        // قراءة التوكن والكوكيز إذا لم تُرسل
        $token = $token ?? $_COOKIE['session_id'] ?? null;
        $idSession = $idSession ?? $_COOKIE['id_session'] ?? null;

        if (empty($token) || empty($idSession)) {
            Response::error("فشلت المصادقه: بيانات المصادقه مفقود", 401);
        }

        // البحث عن الجلسة في قاعدة البيانات
        $stmt = $pdo->prepare("
            SELECT s.user_id, u.role_id, u.username, u.is_active 
            FROM sessions s 
            JOIN users u ON s.user_id = u.user_id
            WHERE s.token = :token AND s.id_session = :id_session AND s.expires_at > NOW()
        ");
        $stmt->execute([
            'token' => $token,
            'id_session' => $idSession
        ]);
        $user = $stmt->fetch();

        // أي فشل → حذف الجلسة فورًا
        if (!$user) {
            self::logout($pdo, $token, $idSession);
            Response::error("تحتاج للمصادقه", 401);
        }

        // المستخدم غير نشط
        if (!$user['is_active']) {
            self::logout($pdo, $token, $idSession);
            Response::error("الحساب غير مفعل", 403);
        }

        return $user;
    }

    /**
     * توليد توكن جديد و id_session ووضعهما في كوكيز
     */
    public static function generateToken(PDO $pdo, $userId) {
        // حذف التوكنات المنتهية الصلاحية
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE expires_at < NOW()");
        $stmt->execute();

        // حذف الجلسات القديمة للمستخدم
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);

        // توليد توكن عشوائي آمن
        $token = bin2hex(random_bytes(32));

        // توليد معرف جلسة فريد
        $idSession = bin2hex(random_bytes(16)); // 32 حرف

        $expires = date('Y-m-d H:i:s', time() + TOKEN_EXPIRY);

        // حفظ الجلسة في قاعدة البيانات
        $stmt = $pdo->prepare("
            INSERT INTO sessions (token, id_session, user_id, expires_at) 
            VALUES (:token, :id_session, :user_id, :expires_at)
        ");
        $stmt->execute([
            'token' => $token,
            'id_session' => $idSession,
            'user_id' => $userId,
            'expires_at' => $expires
        ]);

        // كوكيز التوكن
        setcookie(
            "session_id",
            $token,
            [
                "expires" => time() + TOKEN_EXPIRY,
                "path" => "/",
                "secure" => true,
                "httponly" => true,
                "samesite" => "Strict"
            ]
        );

        // كوكيز معرف الجلسة
        setcookie(
            "id_session",
            $idSession,
            [
                "expires" => time() + TOKEN_EXPIRY,
                "path" => "/",
                "secure" => true,
                "httponly" => true,
                "samesite" => "Strict"
            ]
        );

        return $token;
    }

    /**
     * إنهاء الجلسة وحذف الكوكيز
     */
    public static function logout(PDO $pdo, $token = null, $idSession = null) {
        $token = $token ?? $_COOKIE['session_id'] ?? '';
        $idSession = $idSession ?? $_COOKIE['id_session'] ?? '';

        if (!empty($token) && !empty($idSession)) {
            $stmt = $pdo->prepare("DELETE FROM sessions WHERE token = :token AND id_session = :id_session");
            $stmt->execute([
                'token' => $token,
                'id_session' => $idSession
            ]);
        }

        // حذف الكوكيز
        setcookie("session_id", "", time() - 3600, "/");
        setcookie("id_session", "", time() - 3600, "/");
    }

    /**
     * التحقق من الصلاحية
     */
    public static function checkPermission($userRoleId, $allowedRoles) {
        if (is_array($allowedRoles)) {
            return in_array($userRoleId, $allowedRoles);
        }
        return $userRoleId <= $allowedRoles;
    }
}
