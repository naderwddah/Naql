<?php
/**
 * كلاس تفاصيل البطاقة
 * مسؤول عن العمليات المتعلقة بتفاصيل البطاقات
 */

require_once __DIR__ . '/../helpers/Response.php';

class CardDetail {
    private $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * جلب تفاصيل بطاقة
     * @param string $card_id معرف البطاقة
     * @return array بيانات التفاصيل
     */
    public function getByCardId($card_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM card_details 
            WHERE card_id = :card_id
        ");
        $stmt->execute(['card_id' => $card_id]);
        return $stmt->fetch();
    }

    /**
     * إنشاء تفاصيل جديدة
     * @param string $card_id معرف البطاقة
     * @param array $data بيانات التفاصيل
     * @return bool نجاح العملية
     */
    public function create($card_id, $data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO card_details (
                card_id, 
                driver_name_ar, driver_name_en, driver_id,
                issue_date, expiry_date,
                card_category_ar, card_category_en,
                license_number, license_city_ar, license_city_en,
                license_issue_date, license_expiry_date,
                activity_type_ar, activity_type_en,
                moi_number, company_name_ar, company_name_en
            ) VALUES (
                :card_id,
                :driver_name_ar, :driver_name_en, :driver_id,
                :issue_date, :expiry_date,
                :card_category_ar, :card_category_en,
                :license_number, :license_city_ar, :license_city_en,
                :license_issue_date, :license_expiry_date,
                :activity_type_ar, :activity_type_en,
                :moi_number, :company_name_ar, :company_name_en
            )
        ");

        return $stmt->execute([
            'card_id' => $card_id,
            'driver_name_ar' => $data['driver_name_ar'] ?? null,
            'driver_name_en' => $data['driver_name_en'] ?? null,
            'driver_id' => $data['driver_id'] ?? null,
            'issue_date' => $data['issue_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'card_category_ar' => $data['card_category_ar'] ?? null,
            'card_category_en' => $data['card_category_en'] ?? null,
            'license_number' => $data['license_number'] ?? null,
            'license_city_ar' => $data['license_city_ar'] ?? null,
            'license_city_en' => $data['license_city_en'] ?? null,
            'license_issue_date' => $data['license_issue_date'] ?? null,
            'license_expiry_date' => $data['license_expiry_date'] ?? null,
            'activity_type_ar' => $data['activity_type_ar'] ?? null,
            'activity_type_en' => $data['activity_type_en'] ?? null,
            'moi_number' => $data['moi_number'] ?? null,
            'company_name_ar' => $data['company_name_ar'] ?? null,
            'company_name_en' => $data['company_name_en'] ?? null
        ]);
    }

    /**
     * تحديث تفاصيل البطاقة
     * @param string $card_id معرف البطاقة
     * @param array $data البيانات الجديدة
     * @return bool نجاح العملية
     */
    public function update($card_id, $data) {
        $updates = [];
        $params = ['card_id' => $card_id];
        
        $allowedFields = [
            'driver_name_ar', 'driver_name_en', 'driver_id',
            'issue_date', 'expiry_date',
            'card_category_ar', 'card_category_en',
            'license_number', 'license_city_ar', 'license_city_en',
            'license_issue_date', 'license_expiry_date',
            'activity_type_ar', 'activity_type_en',
            'moi_number', 'company_name_ar', 'company_name_en'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            return true;
        }

        $sql = "UPDATE card_details SET " . implode(', ', $updates) . " WHERE card_id = :card_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * حذف تفاصيل البطاقة
     * @param string $card_id معرف البطاقة
     * @return bool نجاح العملية
     */
    public function delete($card_id) {
        $stmt = $this->pdo->prepare("DELETE FROM card_details WHERE card_id = :card_id");
        return $stmt->execute(['card_id' => $card_id]);
    }

    /**
     * البحث في تفاصيل البطاقات
     * @param string $search كلمة البحث
     * @return array نتائج البحث
     */
    public function search($search) {
        $search = "%$search%";
        $stmt = $this->pdo->prepare("
            SELECT d.*, c.card_number, c.status_id
            FROM card_details d
            JOIN cards c ON c.card_id = d.card_id
            WHERE d.driver_name_ar LIKE :search 
               OR d.driver_name_en LIKE :search
               OR d.company_name_ar LIKE :search
               OR d.company_name_en LIKE :search
               OR d.driver_id LIKE :search
               OR d.license_number LIKE :search
               OR d.moi_number LIKE :search
               OR c.card_number LIKE :search
            ORDER BY d.created_at DESC
        ");
        $stmt->execute(['search' => $search]);
        return $stmt->fetchAll();
    }

    /**
     * التحقق من وجود بطاقة برقم السائق
     * @param string $driver_id رقم السائق
     * @return bool هل الرقم موجود؟
     */
    public function checkDriverIdExists($driver_id) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM card_details 
            WHERE driver_id = :driver_id
        ");
        $stmt->execute(['driver_id' => $driver_id]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * التحقق من وجود بطاقة برقم MOI
     * @param string $moi_number رقم MOI
     * @return bool هل الرقم موجود؟
     */
    public function checkMoiNumberExists($moi_number) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM card_details 
            WHERE moi_number = :moi_number
        ");
        $stmt->execute(['moi_number' => $moi_number]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
}