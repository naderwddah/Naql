<?php
/**
 * كلاس حالة البطاقة
 * مسؤول عن العمليات المتعلقة بحالات البطاقات
 */

require_once __DIR__ . '/../helpers/Response.php';

class Status {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * جلب كل الحالات
     * @return array قائمة الحالات
     */
    public function getAll() {
        $stmt = $this->pdo->query("
            SELECT status_id, name, description 
            FROM card_statuses 
            ORDER BY status_id ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * جلب حالة محددة
     * @param int $status_id معرف الحالة
     * @return array بيانات الحالة
     */
    public function getById($status_id) {
        $stmt = $this->pdo->prepare("
            SELECT status_id, name, description 
            FROM card_statuses 
            WHERE status_id = :status_id
        ");
        $stmt->execute(['status_id' => $status_id]);
        return $stmt->fetch();
    }

    /**
     * إنشاء حالة جديدة
     * @param string $name اسم الحالة
     * @param string|null $description الوصف
     * @return int معرف الحالة الجديد
     */
    public function create($name, $description = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO card_statuses (name, description) 
            VALUES (:name, :description)
        ");
        $stmt->execute([
            'name' => $name,
            'description' => $description
        ]);
        return $this->pdo->lastInsertId();
    }

    /**
     * تحديث حالة
     * @param int $status_id معرف الحالة
     * @param string $name الاسم الجديد
     * @param string|null $description الوصف الجديد
     * @return bool نجاح العملية
     */
    public function update($status_id, $name, $description = null) {
        $stmt = $this->pdo->prepare("
            UPDATE card_statuses 
            SET name = :name, description = :description 
            WHERE status_id = :status_id
        ");
        return $stmt->execute([
            'name' => $name,
            'description' => $description,
            'status_id' => $status_id
        ]);
    }

    /**
     * حذف حالة
     * @param int $status_id معرف الحالة
     * @return bool نجاح العملية
     */
    public function delete($status_id) {
        $stmt = $this->pdo->prepare("DELETE FROM card_statuses WHERE status_id = :status_id");
        return $stmt->execute(['status_id' => $status_id]);
    }

    /**
     * التحقق من وجود بطاقات بهذه الحالة
     * @param int $status_id معرف الحالة
     * @return bool هل يوجد بطاقات؟
     */
    public function hasCards($status_id) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM cards 
            WHERE status_id = :status_id
        ");
        $stmt->execute(['status_id' => $status_id]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * التحقق من أن الحالة نظامية (لا يمكن حذفها)
     * @param int $status_id معرف الحالة
     * @return bool هل هي حالة نظامية؟
     */
    public function isSystemStatus($status_id) {
        return in_array($status_id, [1, 2, 3, 4]);
    }

    /**
     * جلب الحالة الافتراضية (draft)
     * @return int معرف الحالة الافتراضية
     */
    public function getDefaultStatus() {
        return 1; // draft
    }

    /**
     * جلب حالة الموافقة (approved)
     * @return int معرف حالة الموافقة
     */
    public function getApprovedStatus() {
        return 2; // approved
    }
}