<?php
/**
 * كلاس الصلاحية
 * مسؤول عن العمليات المتعلقة بأدوار المستخدمين
 */

require_once __DIR__ . '/../helpers/Response.php';

class Role {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * جلب كل الصلاحيات
     * @return array قائمة الصلاحيات
     */
    public function getAll() {
        $stmt = $this->pdo->query("
            SELECT role_id, name, description, created_at 
            FROM roles 
            ORDER BY role_id ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * جلب صلاحية محددة
     * @param int $role_id معرف الصلاحية
     * @return array بيانات الصلاحية
     */
    public function getById($role_id) {
        $stmt = $this->pdo->prepare("
            SELECT role_id, name, description, created_at 
            FROM roles 
            WHERE role_id = :role_id
        ");
        $stmt->execute(['role_id' => $role_id]);
        return $stmt->fetch();
    }

    /**
     * إنشاء صلاحية جديدة
     * @param string $name اسم الصلاحية
     * @param string|null $description الوصف
     * @return int معرف الصلاحية الجديد
     */
    public function create($name, $description = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO roles (name, description) 
            VALUES (:name, :description)
        ");
        $stmt->execute([
            'name' => $name,
            'description' => $description
        ]);
        return $this->pdo->lastInsertId();
    }

    /**
     * تحديث صلاحية
     * @param int $role_id معرف الصلاحية
     * @param string $name الاسم الجديد
     * @param string|null $description الوصف الجديد
     * @return bool نجاح العملية
     */
    public function update($role_id, $name, $description = null) {
        $stmt = $this->pdo->prepare("
            UPDATE roles 
            SET name = :name, description = :description 
            WHERE role_id = :role_id
        ");
        return $stmt->execute([
            'name' => $name,
            'description' => $description,
            'role_id' => $role_id
        ]);
    }

    /**
     * حذف صلاحية
     * @param int $role_id معرف الصلاحية
     * @return bool نجاح العملية
     */
    public function delete($role_id) {
        $stmt = $this->pdo->prepare("DELETE FROM roles WHERE role_id = :role_id");
        return $stmt->execute(['role_id' => $role_id]);
    }

    /**
     * التحقق من وجود مستخدمين بهذه الصلاحية
     * @param int $role_id معرف الصلاحية
     * @return bool هل يوجد مستخدمين؟
     */
    public function hasUsers($role_id) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE role_id = :role_id
        ");
        $stmt->execute(['role_id' => $role_id]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * التحقق من أن الصلاحية نظامية (لا يمكن حذفها)
     * @param int $role_id معرف الصلاحية
     * @return bool هل هي صلاحية نظامية؟
     */
    public function isSystemRole($role_id) {
        return in_array($role_id, [1, 2, 3, 4]);
    }
}