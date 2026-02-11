<?php
/**
 * متحكم حالات البطاقات
 * يتعامل مع إدارة حالات البطاقات
 */

require_once __DIR__ . '/../helpers/Response.php';

class StatusController {
    private $pdo;
    private $authUser;

    // صلاحيات إدارة الحالات
    const PERMISSIONS = [
        'view' => [1, 2, 3, 4],    // الكل
        'create' => [1, 2],        // super_admin, admin
        'update' => [1, 2],        // super_admin, admin
        'delete' => [1, 2]         // super_admin, admin
    ];

    public function __construct(PDO $pdo, $authUser) {
        $this->pdo = $pdo;
        $this->authUser = $authUser;
    }

    /**
     * عرض كل الحالات
     */
    public function list() {
        $stmt = $this->pdo->query("
            SELECT status_id, name, description 
            FROM card_statuses 
            ORDER BY status_id ASC
        ");
        $statuses = $stmt->fetchAll();
        Response::success(['statuses' => $statuses]);
    }

    /**
     * إنشاء حالة جديدة
     */
    public function create($data) {
        if (!in_array($this->authUser['role_id'], self::PERMISSIONS['create'])) {
            Response::error("Forbidden", 403);
        }

        $name = $data['name'] ?? null;
        $description = $data['description'] ?? null;

        if (!$name) {
            Response::error("Status name required", 400);
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO card_statuses (name, description) 
                VALUES (:name, :description)
            ");
            $stmt->execute([
                'name' => $name,
                'description' => $description
            ]);

            $status_id = $this->pdo->lastInsertId();
            $this->logAction('create_status', $status_id);
            Response::success(['status_id' => $status_id], 201);
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                Response::error("Status name already exists", 409);
            }
            throw $e;
        }
    }

    /**
     * تحديث حالة
     */
    public function update($data) {
        if (!in_array($this->authUser['role_id'], self::PERMISSIONS['update'])) {
            Response::error("Forbidden", 403);
        }

        $status_id = $data['status_id'] ?? null;
        $name = $data['name'] ?? null;

        if (!$status_id || !$name) {
            Response::error("status_id and name required", 400);
        }

        // منع تعديل الحالات النظامية
        if (in_array($status_id, [1, 2, 3, 4])) {
            Response::error("Cannot modify system statuses", 403);
        }

        $description = $data['description'] ?? null;

        $stmt = $this->pdo->prepare("
            UPDATE card_statuses 
            SET name = :name, description = :description 
            WHERE status_id = :status_id
        ");
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'status_id' => $status_id
        ]);

        $this->logAction('update_status', $status_id);
        Response::success(['message' => 'Status updated successfully']);
    }

    /**
     * حذف حالة
     */
    public function delete($data) {
        if (!in_array($this->authUser['role_id'], self::PERMISSIONS['delete'])) {
            Response::error("Forbidden", 403);
        }

        $status_id = $data['status_id'] ?? null;
        if (!$status_id) {
            Response::error("status_id required", 400);
        }

        // منع حذف الحالات النظامية
        if (in_array($status_id, [1, 2, 3, 4])) {
            Response::error("Cannot delete system statuses", 403);
        }

        $stmt = $this->pdo->prepare("DELETE FROM card_statuses WHERE status_id = :status_id");
        $stmt->execute(['status_id' => $status_id]);

        $this->logAction('delete_status', $status_id);
        Response::success(['message' => 'Status deleted successfully']);
    }

    /**
     * تسجيل العملية
     */
    private function logAction($action, $entity_id) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $this->pdo->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address) 
            VALUES (:user_id, :action, 'status', :entity_id, :ip)
        ");
        $stmt->execute([
            'user_id' => $this->authUser['user_id'],
            'action' => $action,
            'entity_id' => $entity_id,
            'ip' => $ip
        ]);
    }
}