<?php
/**
 * متحكم المستخدمين
 * يتعامل مع إدارة المستخدمين
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Response.php';

class UsersController {
    private $pdo;
    private $authUser;
    private $userModel;

    // صلاحيات إدارة المستخدمين
    const PERMISSIONS = [
        'view' => [1, 2],    // super_admin, admin
        'create' => [1, 2],  // super_admin, admin
        'update' => [1, 2],  // super_admin, admin
        'delete' => [1]      // super_admin فقط
    ];

    public function __construct(PDO $pdo, $authUser) {
        $this->pdo = $pdo;
        $this->authUser = $authUser;
        $this->userModel = new User($pdo);
    }

    /**
     * عرض كل المستخدمين
     */
    public function list() {
        if (!in_array($this->authUser['role_id'], self::PERMISSIONS['view'])) {
            Response::error("ممنوع", 403);
        }

        $users = $this->userModel->getAll();
        Response::success(['users' => $users]);
    }

    /**
     * إنشاء مستخدم جديد
     */
    public function create($data) {
        if (!in_array($this->authUser['role_id'], self::PERMISSIONS['create'])) {
            Response::error("ممنوع", 403);
        }

        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;
        $role_id = $data['role_id'] ?? 4;
        $fullName=$data['name'] ?? "";

        if (!$username || !$password) {
            Response::error("اسم المستخدم وكلمه المرور مطلوبتان", 400);
        }

        try {
            $user_id = $this->userModel->create($username, $password, $fullName, $role_id);
            $this->logAction('create_user', $user_id);
            Response::success(['user_id' => $user_id], 201);
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                Response::error("اسم المستخدم موجود سابقا", 409);
            }
            throw $e;
        }
    }
 /**
     * تحديث  مستخدم
     */

     public function update($data) {
        if (!in_array($this->authUser['role_id'], self::PERMISSIONS['update'])) {
            Response::error("ممنوع", 403);
        }

        $user_id = $data['user_id'] ?? null;

        if (!$user_id) {
            Response::error("معرف المستخدم مطلوب", 400);
        }

        $this->userModel->update($user_id, $data['fullName']??null, $data['username']??null, $data['role_id']??null, $data['is_active']??null,$data['password']??null);
        $this->logAction('update_user', $user_id);
        Response::success(['message' => 'تم تحديث المستخدم']);
    }
    /**
     * تحديث كلمة مرور مستخدم
     */
    public function updatePassword($data) {
        if (!in_array($this->authUser['role_id'], self::PERMISSIONS['update'])) {
            Response::error("ممنوع", 403);
        }

        $user_id = $data['user_id'] ?? null;
        $password = $data['password'] ?? null;

        if (!$user_id || !$password) {
            Response::error("اسم المستخدم وكلمه المرور مطلوب", 400);
        }

        $this->userModel->updatePassword($user_id, $password);
        $this->logAction('update_user', $user_id);
        Response::success(['message' => 'تم تحديث المستخدم']);
    }

    /**
     * حذف مستخدم
     */
    public function delete($data) {
        if (!in_array($this->authUser['role_id'], self::PERMISSIONS['delete'])) {
            Response::error("ممنوع", 403);
        }

        $user_id = $data['user_id'] ?? null;
        if (!$user_id) {
            Response::error("معرف المستخدم مطلوب", 400);
        }

        // منع حذف الحساب نفسه
        if ($user_id == $this->authUser['user_id']) {
            Response::error("لايمكنك حذف الحساب نفسة الذي تستخدمه", 403);
        }

        $this->userModel->delete($user_id);
        $this->logAction('delete_user', $user_id);
        Response::success(['message' => 'تم حذف الحساب']);
    }

    /**
     * تسجيل العملية
     */
    private function logAction($action, $target_id) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $this->pdo->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address) 
            VALUES (:user_id, :action, 'user', :entity_id, :ip)
        ");
        $stmt->execute([
            'user_id' => $this->authUser['user_id'],
            'action' => $action,
            'entity_id' => $target_id,
            'ip' => $ip
        ]);
    }
}