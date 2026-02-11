<?php
/**
 * كلاس سجل التدقيق
 * مسؤول عن تسجيل جميع العمليات في النظام
 */

require_once __DIR__ . '/../helpers/Response.php';

class AuditLog {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * تسجيل عملية جديدة
     * @param string $user_id معرف المستخدم
     * @param string $action نوع العملية
     * @param string $entity_type نوع الكيان
     * @param string|null $entity_id معرف الكيان
     * @param string|null $ip_address عنوان IP
     * @return bool نجاح العملية
     */
    public function log($user_id, $action, $entity_type, $entity_id = null, $ip_address = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address) 
            VALUES (:user_id, :action, :entity_type, :entity_id, :ip_address)
        ");
        return $stmt->execute([
            'user_id' => $user_id,
            'action' => $action,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'ip_address' => $ip_address
        ]);
    }

    /**
     * جلب سجل عمليات مستخدم
     * @param string $user_id معرف المستخدم
     * @param int $limit عدد السجلات
     * @return array قائمة العمليات
     */
    public function getByUser($user_id, $limit = 100) {
        $stmt = $this->pdo->prepare("
            SELECT l.*, u.username
            FROM audit_logs l
            JOIN users u ON u.user_id = l.user_id
            WHERE l.user_id = :user_id
            ORDER BY l.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue('user_id', $user_id);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * جلب سجل عمليات كيان
     * @param string $entity_type نوع الكيان
     * @param string $entity_id معرف الكيان
     * @return array قائمة العمليات
     */
    public function getByEntity($entity_type, $entity_id) {
        $stmt = $this->pdo->prepare("
            SELECT l.*, u.username
            FROM audit_logs l
            JOIN users u ON u.user_id = l.user_id
            WHERE l.entity_type = :entity_type AND l.entity_id = :entity_id
            ORDER BY l.created_at DESC
        ");
        $stmt->execute([
            'entity_type' => $entity_type,
            'entity_id' => $entity_id
        ]);
        return $stmt->fetchAll();
    }

    /**
     * جلب آخر العمليات
     * @param int $limit عدد السجلات
     * @return array قائمة العمليات
     */
    public function getRecent($limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT l.*, u.username
            FROM audit_logs l
            JOIN users u ON u.user_id = l.user_id
            ORDER BY l.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * جلب العمليات حسب النوع
     * @param string $action نوع العملية
     * @param int $limit عدد السجلات
     * @return array قائمة العمليات
     */
    public function getByAction($action, $limit = 100) {
        $stmt = $this->pdo->prepare("
            SELECT l.*, u.username
            FROM audit_logs l
            JOIN users u ON u.user_id = l.user_id
            WHERE l.action = :action
            ORDER BY l.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue('action', $action);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * جلب العمليات حسب التاريخ
     * @param string $start_date تاريخ البداية
     * @param string $end_date تاريخ النهاية
     * @return array قائمة العمليات
     */
    public function getByDateRange($start_date, $end_date) {
        $stmt = $this->pdo->prepare("
            SELECT l.*, u.username
            FROM audit_logs l
            JOIN users u ON u.user_id = l.user_id
            WHERE DATE(l.created_at) BETWEEN :start_date AND :end_date
            ORDER BY l.created_at DESC
        ");
        $stmt->execute([
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
        return $stmt->fetchAll();
    }

    /**
     * حذف السجلات القديمة
     * @param int $days عدد الأيام للاحتفاظ بها
     * @return int عدد السجلات المحذوفة
     */
    public function cleanOldLogs($days = 30) {
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        $stmt = $this->pdo->prepare("DELETE FROM audit_logs WHERE created_at < :date");
        $stmt->execute(['date' => $date]);
        return $stmt->rowCount();
    }

    /**
     * إحصائيات العمليات
     * @return array إحصائيات
     */
    public function getStats() {
        $stats = [];
        
        // عدد العمليات اليوم
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as count, DATE(created_at) as date
            FROM audit_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stats['daily'] = $stmt->fetchAll();
        
        // أكثر المستخدمين نشاطاً
        $stmt = $this->pdo->query("
            SELECT u.username, COUNT(*) as count
            FROM audit_logs l
            JOIN users u ON u.user_id = l.user_id
            GROUP BY l.user_id
            ORDER BY count DESC
            LIMIT 10
        ");
        $stats['top_users'] = $stmt->fetchAll();
        
        // أكثر العمليات شيوعاً
        $stmt = $this->pdo->query("
            SELECT action, COUNT(*) as count
            FROM audit_logs
            GROUP BY action
            ORDER BY count DESC
            LIMIT 10
        ");
        $stats['top_actions'] = $stmt->fetchAll();
        
        return $stats;
    }
}