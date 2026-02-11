<?php
/**
 * كلاس المستخدم
 * مسؤول عن كل العمليات المتعلقة بالمستخدمين
 */

require_once __DIR__ . '/../helpers/Response.php';

class User {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * إنشاء مستخدم جديد
     * @param string $username اسم المستخدم
     * @param string $password كلمة المرور
     * @param int $role_id معرف الصلاحية (افتراضي 4 = viewer)
     * @return string معرف المستخدم الجديد
     */
    public function create($username, $password, $fullName, $role_id = 4) {
        // التحقق من صحة اسم المستخدم
        if (strlen($username) < 3) {
            Response::error("Username must be at least 3 characters", 400);
        }
        
        // التحقق من صحة كلمة المرور
        if (strlen($password) < 6) {
            Response::error("Password must be at least 6 characters", 400);
        }
        
        // تشفير كلمة المرور
        // $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $password_hash =$password;

        try {
            // إدخال المستخدم في قاعدة البيانات
            $stmt = $this->pdo->prepare("
                INSERT INTO users (fullName, username, password_hash, role_id) 
                VALUES (:fullName, :username, :password_hash, :role_id)
            ");
            $stmt->execute([
                'fullName' => $fullName,
                'username' => $username,
                'password_hash' => $password_hash,
                'role_id' => $role_id
            ]);
            
               // الحصول على معرف المستخدم الجديد
            $user_id = $this->pdo->lastInsertId();
            return $user_id;
            
        } catch (PDOException $e) {
            // خطأ مكرر (اسم المستخدم موجود)
            if ($e->errorInfo[1] == 1062) {
                Response::error("Username already exists", 409);
            }
            throw $e;
        }
    }

    /**
     * التحقق من صحة بيانات الدخول
     * @param string $username اسم المستخدم
     * @param string $password كلمة المرور
     * @return array بيانات المستخدم
     */
    public function authenticate($username, $password) {
        // البحث عن المستخدم
        $stmt = $this->pdo->prepare("
            SELECT user_id, username, password_hash, role_id, is_active 
            FROM users 
            WHERE username = :username
        ");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        // المستخدم غير موجود
        if (!$user) {
            Response::error("Invalid credentials", 401);
        }

        // المستخدم غير نشط
        if (!$user['is_active']) {
            Response::error("Account is disabled", 403);
        }

        // التحقق من كلمة المرور
        if (!password_verify($password, $user['password_hash'])) {
            Response::error("Invalid credentials", 401);
        }

        return $user;
    }

    /**
     * تحديث كلمة المرور
     * @param string $user_id معرف المستخدم
     * @param string $newPassword كلمة المرور الجديدة
     * @return bool نجاح العملية
     */
    public function updatePassword($user_id, $newPassword) {
        // $password_hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $password_hash = $newPassword;
        
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET password_hash = :password_hash 
            WHERE user_id = :user_id
        ");
        
        return $stmt->execute([
            'password_hash' => $password_hash,
            'user_id' => $user_id
        ]);
    }

    /**
     * حذف مستخدم
     * @param string $user_id معرف المستخدم
     * @return bool نجاح العملية
     */
    public function delete($user_id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = :user_id");
        return $stmt->execute(['user_id' => $user_id]);
    }

    /**
     * جلب كل المستخدمين
     * @return array قائمة المستخدمين
     */
    public function getAll() {
        $stmt = $this->pdo->query("
            SELECT fullName,password_hash as password, user_id, username, role_id, is_active, created_at 
            FROM users 
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll();
    }
}