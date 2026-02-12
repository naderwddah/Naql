<?php
/**
 * متحكم المصادقة
 * يتعامل مع تسجيل الدخول والخروج
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/Response.php';

class AuthController {
    
    /**
     * تسجيل الدخول
     */
    public static function login(PDO $pdo) {
        // قراءة البيانات من الطلب
        $input = json_decode(file_get_contents('php://input'), true);
        
        // التحقق من وجود البيانات المطلوبة
        if (!isset($input['username']) || !isset($input['password'])) {
            Response::error("اسم المستخدم وكلمه المرور مطلوب", 400);
        }

        // المصادقة
        $userModel = new User($pdo);
        $user = $userModel->authenticate($input['username'], $input['password']);
        
        // توليد توكن
        $token = AuthHelper::generateToken($pdo, $user['user_id']);
        
        // تسجيل عملية الدخول
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, ip_address) 
            VALUES (:user_id, 'login', 'user', :ip)
        ");
        $stmt->execute(['user_id' => $user['user_id'], 'ip' => $ip]);

        // إرجاع الاستجابة
        Response::success([
            'token' => $token,
            'user' => [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'role_id' => $user['role_id']
            ]
        ]);
    }

    /**
     * تسجيل الخروج
     */
    public static function logout(PDO $pdo, $token) {
        AuthHelper::logout($pdo, $token);
        Response::success(['message' => 'تم تسجيل الدخول بنجاح']);
    }
}