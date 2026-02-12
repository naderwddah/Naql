<?php
/**
 * متحكم الصلاحيات
 * يتعامل مع إدارة أدوار المستخدمين
 */

require_once __DIR__ . '/../helpers/Response.php';

class RolesController {
    private $pdo;
    private $authUser;

    public function __construct(PDO $pdo, $authUser) {
        $this->pdo = $pdo;
        $this->authUser = $authUser;
    }

    /**
     * عرض كل الصلاحيات
     */
    public function list() {
        $stmt = $this->pdo->query("
            SELECT role_id, name, description, created_at 
            FROM roles 
            ORDER BY role_id ASC
        ");
        $roles = $stmt->fetchAll();
        Response::success(['roles' => $roles]);
    }

    /**
     * إنشاء صلاحية جديدة
     * فقط super_admin
     */
    public function create($data) {
        if ($this->authUser['role_id'] != 1) {
            Response::error("لايمكنك القيام بذالك يجب ترقيه صلاحيتك", 403);
        }

        $name = $data['name'] ?? null;
        $description = $data['description'] ?? null;

        if (!$name) {
            Response::error("رقم الصلاحية مطلوب", 400);
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO roles (name, description) 
                VALUES (:name, :description)
            ");
            $stmt->execute([
                'name' => $name,
                'description' => $description
            ]);

            $role_id = $this->pdo->lastInsertId();
            $this->logAction('create_role', $role_id);
            Response::success(['role_id' => $role_id], 201);
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                Response::error("رقم الصلاحية موجود", 409);
            }
            throw $e;
        }
    }

    /**
     * تحديث صلاحية
     */
    public function update($data) {
        if ($this->authUser['role_id'] != 1) {
            Response::error("لايمكنك القيام بذالك يجب ترقيه صلاحيتك", 403);
        }

        $role_id = $data['role_id'] ?? null;
        $name = $data['name'] ?? null;

        if (!$role_id || !$name) {
            Response::error("معرف الصلاحيه ورقم الصلاحيه مطلوب", 400);
        }

        $description = $data['description'] ?? null;

        $stmt = $this->pdo->prepare("
            UPDATE roles 
            SET name = :name, description = :description 
            WHERE role_id = :role_id
        ");
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'role_id' => $role_id
        ]);

        $this->logAction('update_role', $role_id);
        Response::success(['message' => 'تم تحديث الصلاحية']);
    }

    /**
     * حذف صلاحية
     */
    public function delete($data) {
        if ($this->authUser['role_id'] != 1) {
            Response::error("لايمكنك القيام بذالك يجب ترقيه صلاحيتك", 403);
        }

        $role_id = $data['role_id'] ?? null;
        if (!$role_id) {
            Response::error("role_id required", 400);
        }

        // منع حذف الصلاحيات الأساسية
        if (in_array($role_id, [1, 2, 3, 4])) {
            Response::error(" لايمكنك حذف صلاحيات النظام الاساسية", 403);
        }

        $stmt = $this->pdo->prepare("DELETE FROM roles WHERE role_id = :role_id");
        $stmt->execute(['role_id' => $role_id]);

        $this->logAction('delete_role', $role_id);
        Response::success(['message' => 'تم حذف الصلاحيه']);
    }

    /**
     * تسجيل العملية
     */
    private function logAction($action, $entity_id) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $this->pdo->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address) 
            VALUES (:user_id, :action, 'role', :entity_id, :ip)
        ");
        $stmt->execute([
            'user_id' => $this->authUser['user_id'],
            'action' => $action,
            'entity_id' => $entity_id,
            'ip' => $ip
        ]);
    }
}