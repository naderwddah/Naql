<?php
/**
 * كلاس الجلسة
 * مسؤول عن العمليات المتعلقة بجلسات المستخدمين
 */

require_once __DIR__ . '/../helpers/Response.php';

class Session {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * إنشاء جلسة جديدة
     * @param string $user_id معرف المستخدم
     * @param int $expiry مدة الصلاحية بالثواني
     * @return string التوكن
     */
    public function create($user_id, $expiry = 86400) {
        // حذف الجلسات القديمة للمستخدم
        $this->deleteUserSessions($user_id);
        
        // توليد توكن جديد
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + $expiry);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO sessions (token, user_id, expires_at) 
            VALUES (:token, :user_id, :expires_at)
        ");
        $stmt->execute([
            'token' => $token,
            'user_id' => $user_id,
            'expires_at' => $expires_at
        ]);
        
        return $token;
    }

    /**
     * التحقق من صحة التوكن
     * @param string $token التوكن
     * @return array|false بيانات الجلسة أو false
     */
    public function verify($token) {
        $stmt = $this->pdo->prepare("
            SELECT s.*, u.username, u.role_id, u.is_active 
            FROM sessions s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.token = :token AND s.expires_at > NOW()
        ");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch();
    }

    /**
     * حذف جلسة
     * @param string $token التوكن
     * @return bool نجاح العملية
     */
    public function delete($token) {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE token = :token");
        return $stmt->execute(['token' => $token]);
    }

    /**
     * حذف كل جلسات المستخدم
     * @param string $user_id معرف المستخدم
     * @return bool نجاح العملية
     */
    public function deleteUserSessions($user_id) {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE user_id = :user_id");
        return $stmt->execute(['user_id' => $user_id]);
    }

    /**
     * حذف الجلسات المنتهية
     * @return int عدد الجلسات المحذوفة
     */
    public function cleanExpired() {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE expires_at < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * تمديد صلاحية الجلسة
     * @param string $token التوكن
     * @param int $expiry مدة التمديد بالثواني
     * @return bool نجاح العملية
     */
    public function extend($token, $expiry = 86400) {
        $expires_at = date('Y-m-d H:i:s', time() + $expiry);
        $stmt = $this->pdo->prepare("
            UPDATE sessions 
            SET expires_at = :expires_at 
            WHERE token = :token
        ");
        return $stmt->execute([
            'expires_at' => $expires_at,
            'token' => $token
        ]);
    }

    /**
     * جلب جلسات المستخدم
     * @param string $user_id معرف المستخدم
     * @return array قائمة الجلسات
     */
    public function getUserSessions($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT token, expires_at, created_at 
            FROM sessions 
            WHERE user_id = :user_id 
            ORDER BY expires_at DESC
        ");
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll();
    }
}